<?php
/**
 * Gem - Player Character Showcase
 *
 * Standalone front controller, same pattern as character_roster.php:
 *   player_showcase.php?u=<user_id>
 *
 * NOT natively embedded into memberlist.php's profile view - that would
 * require editing core files this build has never seen the actual content
 * of, which is a real way to break something on a live install blindly.
 * This stands alone; link to it from the real profile page (or hand over
 * your actual memberlist_view.html if you want native embedding done
 * properly against the real file).
 *
 * Shows only the player's ACTIVE characters (status = 1) - same visibility
 * rule as the roster. A showcase is "what this player currently has going
 * on," not an archive.
 */

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();
$user->add_lang('character_roster'); // shares strings with the roster - no separate file needed

$table_prefix = defined('PHPBB_TABLE_PREFIX') ? PHPBB_TABLE_PREFIX : 'phpbb_';
$characters_table = $table_prefix . 'characters';
$fields_table     = $table_prefix . 'profile_fields';
$values_table     = $table_prefix . 'profile_values';

const SHOWCASE_STATUS_ACTIVE = 1;

$player_id = $request->variable('u', 0);

if (!$player_id)
{
	send_status_line(404, 'Not Found');
	trigger_error('GEM_CHARACTER_NOT_FOUND', E_USER_WARNING);
}

$sql = 'SELECT user_id, username, user_colour FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $player_id;
$result = $db->sql_query($sql);
$player = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

if (!$player)
{
	send_status_line(404, 'Not Found');
	trigger_error('GEM_CHARACTER_NOT_FOUND', E_USER_WARNING);
}

page_header($user->lang('GEM_SHOWCASE_TITLE', $player['username']));

$template->set_filenames(array('body' => 'player_showcase.html'));

$template->assign_vars(array(
	'PLAYER_USERNAME'  => $player['username'],
	'PLAYER_COLOUR'    => $player['user_colour'] ? '#' . $player['user_colour'] : '',
	'U_PLAYER_PROFILE' => append_sid("{$phpbb_root_path}memberlist.{$phpEx}", 'mode=viewprofile&amp;u=' . $player['user_id']),
));

// Which fields are flagged to appear in the hover reveal, in admin-configured order
$sql = 'SELECT * FROM ' . $fields_table . ' WHERE applies_to IN (2, 3) AND show_in_showcase = 1 ORDER BY sort_order ASC';
$result = $db->sql_query($sql);
$hover_fields = array();
while ($row = $db->sql_fetchrow($result))
{
	$hover_fields[] = $row;
}
$db->sql_freeresult($result);

$sql = 'SELECT * FROM ' . $characters_table . '
		WHERE user_id = ' . (int) $player_id . '
		AND status = ' . SHOWCASE_STATUS_ACTIVE . '
		ORDER BY character_name ASC';
$result = $db->sql_query($sql);

$has_characters = false;

while ($character = $db->sql_fetchrow($result))
{
	$has_characters = true;

	$sql_values = 'SELECT f.field_id, v.value FROM ' . $values_table . ' v
					JOIN ' . $fields_table . ' f ON v.field_id = f.field_id
					WHERE v.owner_type = 2 AND v.owner_id = ' . (int) $character['character_id'];
	$values_result = $db->sql_query($sql_values);
	$character_values = array();
	while ($v_row = $db->sql_fetchrow($values_result))
	{
		$character_values[$v_row['field_id']] = $v_row['value'];
	}
	$db->sql_freeresult($values_result);

	// Showcase image falls back to avatar if the player hasn't set one -
	// a showcase tile with nothing in it looks broken, this doesn't.
	$display_image = $character['showcase_image'] ?: $character['avatar'];

	$template->assign_block_vars('characters', array(
		'CHARACTER_ID'   => $character['character_id'],
		'CHARACTER_NAME' => $character['character_name'],
		'DISPLAY_IMAGE'  => $display_image,
		'U_PROFILE'      => append_sid("{$phpbb_root_path}character_roster.{$phpEx}", 'mode=profile&amp;character_id=' . $character['character_id']),
	));

	foreach ($hover_fields as $field)
	{
		$value = isset($character_values[$field['field_id']]) ? $character_values[$field['field_id']] : '';
		if ($value === '')
		{
			continue; // don't show an empty row in the hover for this character
		}

		if ($field['field_type'] === 'multiselect')
		{
			$decoded = json_decode($value, true);
			$value = is_array($decoded) ? implode(', ', $decoded) : $value;
		}

		$template->assign_block_vars('characters.hover_fields', array(
			'LABEL' => $field['label'],
			'VALUE' => $value,
		));
	}
}
$db->sql_freeresult($result);

$template->assign_var('S_HAS_CHARACTERS', $has_characters);

page_footer();
