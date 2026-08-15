<?php
/**
 * Gem - Public Wanted Characters roster
 *
 * Standalone front controller, same pattern as character_roster.php.
 *
 * DELIBERATE DEVIATION FROM TGG: TGG's version did client-side JS
 * filtering (extract all ad data into a JS array, filter/sort in the
 * browser with zero page reloads). This does server-side filtering via
 * query params instead - consistent with how character_roster.php already
 * works, and one fewer JS system to maintain. Trade-off: a page reload per
 * filter change instead of instant client-side filtering. Worth revisiting
 * if that UX matters enough to you to justify the extra JS.
 */

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();
$user->add_lang('wanted');

$table_prefix = defined('PHPBB_TABLE_PREFIX') ? PHPBB_TABLE_PREFIX : 'phpbb_';
$wanted_characters_table = $table_prefix . 'wanted_characters';
$characters_table        = $table_prefix . 'characters';
$fields_table             = $table_prefix . 'profile_fields';
$values_table             = $table_prefix . 'profile_values';

const OWNER_WANTED_CHARACTER = 4;

page_header($user->lang('GEM_WANTED_CHARACTERS_TITLE'));
$template->set_filenames(array('body' => 'wanted_characters_body.html'));

// Searchable wanted-character fields become filter dropdowns, same pattern
// as character_roster.php.
$sql = 'SELECT * FROM ' . $fields_table . ' WHERE wanted_character_field = 1 AND searchable = 1';
$result = $db->sql_query($sql);
$filter_fields = array();
while ($row = $db->sql_fetchrow($result))
{
	$filter_fields[] = $row;
}
$db->sql_freeresult($result);

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

$matching_ids = null;
foreach ($active_filters as $field_id => $selected_value)
{
	$sql = 'SELECT owner_id FROM ' . $values_table . '
			WHERE field_id = ' . (int) $field_id . ' AND owner_type = ' . OWNER_WANTED_CHARACTER . "
			AND value = '" . $db->sql_escape($selected_value) . "'";
	$result = $db->sql_query($sql);
	$ids = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$ids[] = (int) $row['owner_id'];
	}
	$db->sql_freeresult($result);
	$matching_ids = ($matching_ids === null) ? $ids : array_intersect($matching_ids, $ids);
}

$where = 'w.ad_status = 1';
if ($matching_ids !== null)
{
	$matching_ids = empty($matching_ids) ? array(0) : $matching_ids;
	$where .= ' AND ' . $db->sql_in_set('w.ad_id', $matching_ids);
}

$sql = 'SELECT w.*, ch.character_name, ch.avatar AS char_avatar, ch.character_colour
		FROM ' . $wanted_characters_table . ' w
		LEFT JOIN ' . $characters_table . ' ch ON w.character_id = ch.character_id
		WHERE ' . $where . '
		ORDER BY w.created_at DESC';
$result = $db->sql_query($sql);

$has_results = false;
while ($row = $db->sql_fetchrow($result))
{
	$has_results = true;

	$sql2 = 'SELECT f.label, f.field_key, v.value FROM ' . $values_table . ' v
			JOIN ' . $fields_table . ' f ON v.field_id = f.field_id
			WHERE v.owner_type = ' . OWNER_WANTED_CHARACTER . ' AND v.owner_id = ' . (int) $row['ad_id'];
	$result2 = $db->sql_query($sql2);
	$field_values = array();
	while ($fv = $db->sql_fetchrow($result2))
	{
		$field_values[] = $fv;
	}
	$db->sql_freeresult($result2);

	$image_url = !empty($row['image_url']) ? $row['image_url'] : '';

	$blurb = generate_text_for_display($row['blurb'], $row['signature_bbcode_uid'], $row['signature_bbcode_bitfield'], OPTION_FLAG_BBCODE | OPTION_FLAG_SMILIES | OPTION_FLAG_LINKS);

	$block = array(
		'AD_ID'          => $row['ad_id'],
		'CHAR_NAME'      => $row['char_name'],
		'IMAGE_URL'      => $image_url,
		'BLURB'          => $blurb,
		'IS_RESERVED'    => (bool) $row['is_reserved'],
		'CONNECTED_TO'   => $row['character_name'],
		'U_CONNECTED_TO' => append_sid("{$phpbb_root_path}character_roster.{$phpEx}", 'mode=profile&amp;character_id=' . $row['character_id']),
	);

	foreach ($field_values as $fv)
	{
		$block['FIELD_' . strtoupper($fv['field_key'])] = $fv['value'];
	}

	$template->assign_block_vars('wanted_ads', $block);

	foreach ($field_values as $fv)
	{
		$template->assign_block_vars('wanted_ads.details', array(
			'LABEL' => $fv['label'],
			'VALUE' => $fv['value'],
		));
	}
}
$db->sql_freeresult($result);

$template->assign_vars(array(
	'S_HAS_RESULTS' => $has_results,
	'U_ACTION'      => append_sid("{$phpbb_root_path}wanted_characters.{$phpEx}"),
));

page_footer();
