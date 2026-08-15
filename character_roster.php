<?php
/**
 * Gem - Public Character Roster
 *
 * Standalone front controller, same pattern as phpBB's own memberlist.php -
 * not an ACP/UCP module, reached directly by URL:
 *   character_roster.php                          -> roster listing
 *   character_roster.php?mode=profile&character_id=N -> single character profile
 *
 * RULES ENFORCED HERE (per spec, not configurable):
 *   - Archived and deactivated characters are ALWAYS hidden, both from the
 *     listing and from direct profile access by ID. Pending/declined are
 *     hidden too - this is a public roster of who's actually playable.
 *   - Every applicable profile field is exposed as a {FIELD_<KEY>} template
 *     variable (uppercased field_key) on both the listing and the profile
 *     page, regardless of the show_on_roster/searchable flags - those flags
 *     only control what the DEFAULT template does with a field, they don't
 *     limit what's available to custom HTML.
 *
 * KNOWN LIMITATION: "recent posts" on a profile only has data for
 * characters with a legacy_user_id (migrated from a pre-existing standalone
 * account) - a character created natively in Gem has no post history to
 * show until the posting-side integration (component 7) lands and starts
 * recording post_character_id going forward. Not a bug, just not built yet.
 */

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
require_once($phpbb_root_path . 'includes/gem/song_embed.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();
$user->add_lang('character_roster');

$table_prefix = defined('PHPBB_TABLE_PREFIX') ? PHPBB_TABLE_PREFIX : 'phpbb_';
$characters_table = $table_prefix . 'characters';
$fields_table     = $table_prefix . 'profile_fields';
$sections_table   = $table_prefix . 'profile_sections';
$values_table     = $table_prefix . 'profile_values';

const STATUS_ACTIVE = 1;

$mode = $request->variable('mode', 'list');

if ($mode === 'profile')
{
	gem_roster_show_profile((int) $request->variable('character_id', 0));
}
else
{
	gem_roster_show_list();
}

// -------------------------------------------------------------------
// Listing
// -------------------------------------------------------------------

function gem_roster_show_list()
{
	global $db, $user, $template, $request, $characters_table, $fields_table, $values_table;

	page_header($user->lang('GEM_ROSTER_TITLE'));

	$template->set_filenames(array('body' => 'character_roster_list.html'));

	// Filter fields: searchable select/multiselect fields become dropdown
	// filters; searchable text/textarea/url fields feed the general search box.
	$sql = 'SELECT * FROM ' . $fields_table . ' WHERE applies_to IN (2, 3) AND searchable = 1';
	$result = $db->sql_query($sql);
	$filter_fields = array();
	$text_search_field_ids = array();
	while ($row = $db->sql_fetchrow($result))
	{
		if (in_array($row['field_type'], array('select', 'multiselect'), true))
		{
			$filter_fields[] = $row;
		}
		else if (in_array($row['field_type'], array('text', 'textarea', 'url'), true))
		{
			$text_search_field_ids[] = (int) $row['field_id'];
		}
	}
	$db->sql_freeresult($result);

	$search_query = $request->variable('q', '', true);
	$active_filters = array();

	foreach ($filter_fields as $field)
	{
		$selected = $request->variable('filter_' . $field['field_id'], '', true);
		$choices = json_decode($field['field_options'], true);

		$template->assign_block_vars('filters', array(
			'FIELD_ID' => $field['field_id'],
			'LABEL'    => $field['label'],
		));

		if (is_array($choices))
		{
			foreach ($choices as $choice)
			{
				$template->assign_block_vars('filters.choices', array(
					'VALUE'    => $choice,
					'LABEL'    => $choice,
					'SELECTED' => ($choice === $selected),
				));
			}
		}

		if ($selected !== '')
		{
			$active_filters[(int) $field['field_id']] = $selected;
		}
	}

	// Candidate character_ids from filters/search, intersected together
	$matching_ids = null; // null = no restriction yet

	foreach ($active_filters as $field_id => $selected_value)
	{
		$sql = 'SELECT owner_id FROM ' . $values_table . '
				WHERE field_id = ' . (int) $field_id . ' AND owner_type = 2
				AND (value = \'' . $db->sql_escape($selected_value) . '\'
					OR value LIKE \'%"' . $db->sql_escape($selected_value) . '"%\')'; // plain match or inside a multiselect JSON array
		$result = $db->sql_query($sql);
		$ids = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$ids[] = (int) $row['owner_id'];
		}
		$db->sql_freeresult($result);

		$matching_ids = ($matching_ids === null) ? $ids : array_intersect($matching_ids, $ids);
	}

	if ($search_query !== '' && !empty($text_search_field_ids))
	{
		$sql = 'SELECT DISTINCT owner_id FROM ' . $values_table . '
				WHERE owner_type = 2
				AND ' . $db->sql_in_set('field_id', $text_search_field_ids) . '
				AND value LIKE \'%' . $db->sql_escape($search_query) . '%\'';
		$result = $db->sql_query($sql);
		$ids = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$ids[] = (int) $row['owner_id'];
		}
		$db->sql_freeresult($result);

		// Search also matches on character_name directly, not just custom fields
		$sql = 'SELECT character_id FROM ' . $characters_table . '
				WHERE status = ' . STATUS_ACTIVE . '
				AND character_name LIKE \'%' . $db->sql_escape($search_query) . '%\'';
		$name_result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($name_result))
		{
			$ids[] = (int) $row['character_id'];
		}
		$db->sql_freeresult($name_result);

		$matching_ids = ($matching_ids === null) ? $ids : array_intersect($matching_ids, $ids);
	}

	$where = 'status = ' . STATUS_ACTIVE;
	if ($matching_ids !== null)
	{
		if (empty($matching_ids))
		{
			$matching_ids = array(0); // no results
		}
		$where .= ' AND ' . $db->sql_in_set('character_id', $matching_ids);
	}

	$per_page = 24;
	$start = $request->variable('start', 0);

	$sql = 'SELECT COUNT(*) AS cnt FROM ' . $characters_table . ' WHERE ' . $where;
	$result = $db->sql_query($sql);
	$total = (int) $db->sql_fetchfield('cnt');
	$db->sql_freeresult($result);

	$sql = 'SELECT * FROM ' . $characters_table . ' WHERE ' . $where . ' ORDER BY character_name ASC';
	$result = $db->sql_query_limit($sql, $per_page, $start);

	$has_results = false;

	// Roster-listing fields (show_on_roster = 1) for the default template's compact view
	$sql_roster_fields = 'SELECT * FROM ' . $fields_table . ' WHERE applies_to IN (2, 3) AND show_on_roster = 1 ORDER BY sort_order ASC';
	$roster_fields_result = $db->sql_query($sql_roster_fields);
	$roster_fields = array();
	while ($row = $db->sql_fetchrow($roster_fields_result))
	{
		$roster_fields[] = $row;
	}
	$db->sql_freeresult($roster_fields_result);

	while ($character = $db->sql_fetchrow($result))
	{
		$has_results = true;
		$values = gem_roster_get_field_values((int) $character['character_id']);

		$block = array(
			'CHARACTER_ID'   => $character['character_id'],
			'CHARACTER_NAME' => $character['character_name'],
			'AVATAR_URL'     => $character['avatar'],
			'U_PROFILE'      => append_sid("{$GLOBALS['phpbb_root_path']}character_roster.{$GLOBALS['phpEx']}", 'mode=profile&amp;character_id=' . $character['character_id']),
		);

		// Every field available as a named variable, regardless of show_on_roster
		foreach ($values as $field_key => $value)
		{
			$block['FIELD_' . strtoupper($field_key)] = $value;
		}

		$template->assign_block_vars('characters', $block);

		// The default compact-view fields, in admin-configured order
		foreach ($roster_fields as $field)
		{
			$template->assign_block_vars('characters.roster_fields', array(
				'LABEL' => $field['label'],
				'VALUE' => isset($values[$field['field_key']]) ? $values[$field['field_key']] : '',
			));
		}
	}
	$db->sql_freeresult($result);

	$base_url = append_sid("{$GLOBALS['phpbb_root_path']}character_roster.{$GLOBALS['phpEx']}");
	$pagination = $GLOBALS['phpbb_container']->get('pagination');
	$pagination->generate_template_pagination($base_url, 'pagination', 'start', $total, $per_page, $start);

	$template->assign_vars(array(
		'SEARCH_QUERY'  => $search_query,
		'TOTAL_RESULTS' => $total,
		'S_HAS_RESULTS' => $has_results,
		'U_ROSTER'      => $base_url,
	));

	page_footer();
}

// -------------------------------------------------------------------
// Single profile
// -------------------------------------------------------------------

function gem_roster_show_profile($character_id)
{
	global $db, $user, $template, $characters_table, $sections_table;

	if (!$character_id)
	{
		send_status_line(404, 'Not Found');
		trigger_error('GEM_CHARACTER_NOT_FOUND', E_USER_WARNING);
	}

	$sql = 'SELECT * FROM ' . $characters_table . '
			WHERE character_id = ' . (int) $character_id . ' AND status = ' . STATUS_ACTIVE;
	$result = $db->sql_query($sql);
	$character = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if (!$character)
	{
		// Also covers archived/deactivated/pending/declined characters -
		// direct-by-ID access follows the same "always hidden" rule as the listing.
		send_status_line(404, 'Not Found');
		trigger_error('GEM_CHARACTER_NOT_FOUND', E_USER_WARNING);
	}

	page_header($character['character_name']);

	$template->set_filenames(array('body' => 'character_roster_profile.html'));

	$parsed_signature = $character['signature'];
	if ($parsed_signature !== '')
	{
		$parsed_signature = generate_text_for_display($parsed_signature, $character['signature_bbcode_uid'], $character['signature_bbcode_bitfield'], OPTION_FLAG_BBCODE | OPTION_FLAG_SMILIES | OPTION_FLAG_LINKS);
	}

	$template->assign_vars(array(
		'CHARACTER_ID'   => $character['character_id'],
		'CHARACTER_NAME' => $character['character_name'],
		'AVATAR_URL'     => $character['avatar'],
		'SIGNATURE'      => $parsed_signature,
	));

	gem_roster_assign_profile_fields($character_id);
	gem_roster_assign_recent_posts($character);
	gem_roster_assign_linked_player($character);
	gem_roster_assign_sibling_characters($character);
	gem_roster_assign_connections($character_id);

	page_footer();
}

function gem_roster_get_field_values($character_id)
{
	global $db, $values_table, $fields_table;

	$sql = 'SELECT f.field_key, f.field_type, v.value
			FROM ' . $values_table . ' v
			JOIN ' . $fields_table . ' f ON v.field_id = f.field_id
			WHERE v.owner_type = 2 AND v.owner_id = ' . (int) $character_id;
	$result = $db->sql_query($sql);

	$values = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$value = $row['value'];
		if ($row['field_type'] === 'multiselect')
		{
			$decoded = json_decode($value, true);
			$value = is_array($decoded) ? implode(', ', $decoded) : '';
		}
		else if ($row['field_type'] === 'songlist')
		{
			$value = gem_render_songlist($value); // raw embed HTML - same "trusted, unescaped" convention as SIGNATURE
		}
		$values[$row['field_key']] = $value;
	}
	$db->sql_freeresult($result);

	return $values;
}

/**
 * Renders the full profile field set grouped by section, with anchors -
 * this is what the sidebar-gallery-style jump navigation from the spec
 * hooks into. Also assigns every {FIELD_<KEY>} variable directly, same as
 * the listing, so custom HTML can pull any value without walking the loop.
 */
function gem_roster_assign_profile_fields($character_id)
{
	global $db, $template, $fields_table, $sections_table;

	$values = gem_roster_get_field_values($character_id);

	foreach ($values as $field_key => $value)
	{
		$template->assign_var('FIELD_' . strtoupper($field_key), $value);
	}

	$sql = 'SELECT f.*, s.section_name, s.anchor_slug
			FROM ' . $fields_table . ' f
			LEFT JOIN ' . $sections_table . ' s ON f.section_id = s.section_id
			WHERE f.applies_to IN (2, 3)
			ORDER BY (f.section_id = 0) ASC, s.sort_order ASC, f.sort_order ASC';
	$result = $db->sql_query($sql);

	$current_section = null;
	while ($row = $db->sql_fetchrow($result))
	{
		if (!isset($values[$row['field_key']]) || $values[$row['field_key']] === '')
		{
			continue; // don't render empty fields on the public profile
		}

		if ($row['section_name'] !== $current_section)
		{
			$current_section = $row['section_name'];
			$template->assign_block_vars('profile_sections', array(
				'SECTION_NAME' => $row['section_name'] ?: '',
				'ANCHOR_SLUG'  => $row['anchor_slug'] ?: '',
			));
		}

		$template->assign_block_vars('profile_sections.profile_fields', array(
			'LABEL' => $row['label'],
			'VALUE' => $values[$row['field_key']],
		));
	}
	$db->sql_freeresult($result);
}

function gem_roster_assign_recent_posts($character)
{
	global $db, $template, $phpbb_root_path, $phpEx;

	if (empty($character['legacy_user_id']))
	{
		return; // native Gem character - no post history until posting-side integration exists
	}

	$sql = 'SELECT p.post_id, p.post_subject, p.post_time, p.topic_id, p.forum_id
			FROM ' . POSTS_TABLE . ' p
			WHERE p.poster_id = ' . (int) $character['legacy_user_id'] . '
			AND p.post_visibility = ' . ITEM_APPROVED . '
			ORDER BY p.post_time DESC';
	$result = $db->sql_query_limit($sql, 10);
	while ($row = $db->sql_fetchrow($result))
	{
		$template->assign_block_vars('recent_posts', array(
			'SUBJECT' => $row['post_subject'],
			'TIME'    => $GLOBALS['user']->format_date($row['post_time']),
			'U_POST'  => append_sid("{$phpbb_root_path}viewtopic.{$phpEx}", "f={$row['forum_id']}&t={$row['topic_id']}&p={$row['post_id']}#p{$row['post_id']}"),
		));
	}
	$db->sql_freeresult($result);
}

function gem_roster_assign_linked_player($character)
{
	global $db, $template, $phpbb_root_path, $phpEx;

	$sql = 'SELECT user_id, username, user_colour, user_avatar FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $character['user_id'];
	$result = $db->sql_query($sql);
	$player = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if ($player)
	{
		$template->assign_vars(array(
			'PLAYER_USERNAME' => $player['username'],
			'PLAYER_COLOUR'   => $player['user_colour'] ? '#' . $player['user_colour'] : '',
			'U_PLAYER_PROFILE' => append_sid("{$phpbb_root_path}memberlist.{$phpEx}", 'mode=viewprofile&amp;u=' . $player['user_id']),
		));
	}
}

function gem_roster_assign_sibling_characters($character)
{
	global $db, $template, $characters_table;

	$sql = 'SELECT character_id, character_name, avatar FROM ' . $characters_table . '
			WHERE user_id = ' . (int) $character['user_id'] . '
			AND character_id <> ' . (int) $character['character_id'] . '
			AND status = ' . STATUS_ACTIVE . '
			ORDER BY character_name ASC';
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
		$template->assign_block_vars('sibling_characters', array(
			'CHARACTER_NAME' => $row['character_name'],
			'AVATAR_URL'     => $row['avatar'],
			'U_PROFILE'      => append_sid("{$GLOBALS['phpbb_root_path']}character_roster.{$GLOBALS['phpEx']}", 'mode=profile&amp;character_id=' . $row['character_id']),
		));
	}
	$db->sql_freeresult($result);
}

/**
 * Connections this character has created (i.e. this character's own view
 * of its relationships) - not connections OTHER characters have made
 * pointing at this one. Directed/one-sided per spec: what shows here is
 * "who this character says they're connected to," not a symmetric list.
 */
function gem_roster_assign_connections($character_id)
{
	global $db, $template, $table_prefix;

	$connections_table = $table_prefix . 'connections';
	$categories_table   = $table_prefix . 'connection_categories';
	$characters_table    = $table_prefix . 'characters';

	$sql = 'SELECT c.*, ch.character_name AS target_name, ch.avatar AS target_avatar, cat.category_name, cat.color
			FROM ' . $connections_table . ' c
			LEFT JOIN ' . $characters_table . ' ch ON c.connected_character_id = ch.character_id
			LEFT JOIN ' . $categories_table . ' cat ON c.category_id = cat.category_id
			WHERE c.character_id = ' . (int) $character_id . '
			AND ch.status = ' . STATUS_ACTIVE . '
			ORDER BY c.created_at DESC';
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
		$template->assign_block_vars('connections', array(
			'TARGET_NAME'   => $row['target_name'],
			'TARGET_AVATAR' => $row['target_avatar'],
			'CATEGORY_NAME' => $row['category_name'],
			'COLOR'         => $row['color'],
			'DESCRIPTION'   => $row['description'],
			'U_PROFILE'     => append_sid("{$GLOBALS['phpbb_root_path']}character_roster.{$GLOBALS['phpEx']}", 'mode=profile&amp;character_id=' . $row['connected_character_id']),
		));
	}
	$db->sql_freeresult($result);
}
