<?php
/**
 * Gem - Registration Wizard
 *
 * Standalone front controller: register_wizard.php?step=N
 *
 * Progress tracked against phpBB's own session_id (phpBB tracks
 * anonymous/guest sessions, so this works pre-account). No-JS baseline:
 * each step is a real page with a real form POST - fully functional with
 * JS disabled. JS enhancement (see template) disables the acknowledgment
 * checkbox until the visitor scrolls the content to the bottom - a genuine
 * anti-skim measure, not decoration - and degrades gracefully (checkbox
 * just works normally without it).
 *
 * NOT hard-gated against phpBB's real registration form - this is the
 * primary, linked-to entry point, not an enforced checkpoint. See the
 * accompanying doc for what a hard-gate patch to ucp_register.php would
 * need, if you want to build that once that file's been shared.
 */

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();
$user->add_lang('registration_wizard');

$table_prefix = defined('PHPBB_TABLE_PREFIX') ? PHPBB_TABLE_PREFIX : 'phpbb_';
$steps_table    = $table_prefix . 'registration_steps';
$progress_table = $table_prefix . 'registration_progress';

$session_id = $user->data['session_id'];

// Load the full step sequence once - drives both the wizard flow and the progress bar.
$sql = 'SELECT * FROM ' . $steps_table . ' ORDER BY sort_order ASC';
$result = $db->sql_query($sql);
$steps = array();
while ($row = $db->sql_fetchrow($result))
{
	$steps[] = $row;
}
$db->sql_freeresult($result);

$total_steps = count($steps);

if ($total_steps === 0)
{
	// No steps configured - nothing to gate, send straight through.
	redirect(append_sid("{$phpbb_root_path}ucp.{$phpEx}", 'mode=register'));
}

$requested_step = max(1, $request->variable('step', 1));
$requested_step = min($requested_step, $total_steps);

if ($request->is_set_post('submit'))
{
	if (!check_form_key('gem_registration_wizard'))
	{
		trigger_error('FORM_INVALID', E_USER_WARNING);
	}

	$current_step_data = $steps[$requested_step - 1];

	if ($current_step_data['require_acknowledgment'] && !$request->variable('acknowledge', 0))
	{
		trigger_error($user->lang('GEM_MUST_ACKNOWLEDGE') . adm_back_link(append_sid("{$phpbb_root_path}register_wizard.{$phpEx}", 'step=' . $requested_step)), E_USER_WARNING);
	}

	$next_step = $requested_step + 1;
	$completed = ($next_step > $total_steps);

	// Upsert progress
	$sql = 'SELECT session_id FROM ' . $progress_table . " WHERE session_id = '" . $db->sql_escape($session_id) . "'";
	$check_result = $db->sql_query($sql);
	$exists = $db->sql_fetchrow($check_result);
	$db->sql_freeresult($check_result);

	$progress_ary = array(
		'current_step' => $completed ? $total_steps : $next_step,
		'completed'    => $completed ? 1 : 0,
		'updated_at'   => time(),
	);

	if ($exists)
	{
		$sql = 'UPDATE ' . $progress_table . ' SET ' . $db->sql_build_array('UPDATE', $progress_ary) . "
				WHERE session_id = '" . $db->sql_escape($session_id) . "'";
		$db->sql_query($sql);
	}
	else
	{
		$progress_ary['session_id'] = $session_id;
		$sql = 'INSERT INTO ' . $progress_table . ' ' . $db->sql_build_array('INSERT', $progress_ary);
		$db->sql_query($sql);
	}

	if ($completed)
	{
		redirect(append_sid("{$phpbb_root_path}ucp.{$phpEx}", 'mode=register'));
	}

	redirect(append_sid("{$phpbb_root_path}register_wizard.{$phpEx}", 'step=' . $next_step));
}

$current_step_data = $steps[$requested_step - 1];

page_header($user->lang('GEM_REGISTRATION_WIZARD_TITLE'));
$template->set_filenames(array('body' => 'register_wizard_body.html'));

add_form_key('gem_registration_wizard');

// Content is trusted, admin-authored plain text/HTML (ACP-only input,
// same trust level as a template) - rendered as-is, not routed through
// BBCode display processing, since it was never paired with a
// generate_text_for_storage() uid/bitfield at save time.
$template->assign_vars(array(
	'STEP_TITLE'      => $current_step_data['title'],
	'STEP_CONTENT'    => $current_step_data['content'],
	'S_REQUIRE_ACK'   => (bool) $current_step_data['require_acknowledgment'],
	'CURRENT_STEP'    => $requested_step,
	'TOTAL_STEPS'     => $total_steps,
	'S_LAST_STEP'     => ($requested_step === $total_steps),
	'U_SUBMIT'        => append_sid("{$phpbb_root_path}register_wizard.{$phpEx}", 'step=' . $requested_step),
));

page_footer();
