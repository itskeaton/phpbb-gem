<?php
/**
 * Gem - Post Switcher (character selector + sidebar-image picker)
 *
 * NOT auto-loaded or auto-wired into posting.php - this file provides two
 * functions meant to be called from your real posting flow:
 *
 *   gem_render_post_switcher($user_id)
 *     Call before page_header()/template rendering on the posting page.
 *     Assigns everything gem_post_switcher.html needs. Populates the
 *     sidebar-image picker with the DEFAULT/currently-active character's
 *     images at page load - switching the character dropdown does not
 *     live-refresh the image list without additional JS/AJAX that hasn't
 *     been built. Flagging this now rather than pretending it's seamless.
 *
 *   gem_save_post_selection($post_id)
 *     Call once, right after a post is successfully created/edited (i.e.
 *     after submit_post() returns and you have a real $post_id). Reads the
 *     submitted selection, validates ownership server-side (never trust
 *     the client-submitted character_id blindly), and writes to
 *     phpbb_post_character / phpbb_post_sidebar_image. Also updates
 *     phpbb_characters_active if "set as default" was checked.
 *
 * See the component 6 delivery notes for the exact two-line + one-include
 * integration this expects in posting.php / posting_body.html.
 */

function gem_render_post_switcher($user_id)
{
	global $db, $template, $request, $table_prefix;

	$characters_table = $table_prefix . 'characters';
	$characters_active_table = $table_prefix . 'characters_active';
	$gallery_table = $table_prefix . 'character_gallery';

	// If editing an existing post, prefer that post's existing selection as
	// the "currently selected" state; otherwise fall back to the player's
	// persistent default.
	$post_id = $request->variable('p', 0);
	$currently_selected_id = 0;

	if ($post_id)
	{
		$sql = 'SELECT character_id FROM ' . $table_prefix . 'post_character WHERE post_id = ' . (int) $post_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		if ($row)
		{
			$currently_selected_id = (int) $row['character_id'];
		}
	}

	if (!$currently_selected_id)
	{
		$sql = 'SELECT character_id FROM ' . $characters_active_table . ' WHERE user_id = ' . (int) $user_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		if ($row)
		{
			$currently_selected_id = (int) $row['character_id'];
		}
	}

	$sql = 'SELECT character_id, character_name FROM ' . $characters_table . '
			WHERE user_id = ' . (int) $user_id . ' AND status = 1
			ORDER BY character_name ASC';
	$result = $db->sql_query($sql);
	$has_characters = false;
	while ($row = $db->sql_fetchrow($result))
	{
		$has_characters = true;
		$template->assign_block_vars('gem_characters', array(
			'CHARACTER_ID'   => $row['character_id'],
			'CHARACTER_NAME' => $row['character_name'],
			'SELECTED'       => ((int) $row['character_id'] === $currently_selected_id),
		));
	}
	$db->sql_freeresult($result);

	if ($currently_selected_id)
	{
		$sql = 'SELECT image_id, image_url, label, is_default FROM ' . $gallery_table . '
				WHERE character_id = ' . $currently_selected_id . "
				AND album = 'sidebar'
				ORDER BY sort_order ASC";
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('gem_sidebar_images', array(
				'IMAGE_ID'  => $row['image_id'],
				'IMAGE_URL' => $row['image_url'],
				'LABEL'     => $row['label'],
				'SELECTED'  => (bool) $row['is_default'],
			));
		}
		$db->sql_freeresult($result);
	}

	$template->assign_vars(array(
		'GEM_S_HAS_CHARACTERS' => $has_characters,
	));
}

function gem_save_post_selection($post_id)
{
	global $db, $user, $request, $table_prefix;

	if (!$post_id)
	{
		return;
	}

	$characters_table = $table_prefix . 'characters';
	$characters_active_table = $table_prefix . 'characters_active';
	$gallery_table = $table_prefix . 'character_gallery';
	$post_character_table = $table_prefix . 'post_character';
	$post_sidebar_table = $table_prefix . 'post_sidebar_image';

	$submitted_character_id = $request->variable('gem_character_id', 0);
	$submitted_image_id = $request->variable('gem_sidebar_image_id', 0);
	$set_default = $request->variable('gem_set_default', 0);
	$my_user_id = (int) $user->data['user_id'];

	// Validate ownership server-side - never trust the client-submitted id blindly.
	$character_id = 0;
	if ($submitted_character_id)
	{
		$sql = 'SELECT character_id FROM ' . $characters_table . '
				WHERE character_id = ' . (int) $submitted_character_id . '
				AND user_id = ' . $my_user_id . ' AND status = 1';
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		$character_id = $row ? (int) $row['character_id'] : 0;
	}

	// Same for the sidebar image - must actually belong to the validated character's sidebar album.
	$image_id = 0;
	if ($submitted_image_id && $character_id)
	{
		$sql = 'SELECT image_id FROM ' . $gallery_table . '
				WHERE image_id = ' . (int) $submitted_image_id . '
				AND character_id = ' . $character_id . "
				AND album = 'sidebar'";
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		$image_id = $row ? (int) $row['image_id'] : 0;
	}

	// post_character: upsert
	$sql = 'DELETE FROM ' . $post_character_table . ' WHERE post_id = ' . (int) $post_id;
	$db->sql_query($sql);
	$sql = 'INSERT INTO ' . $post_character_table . ' ' . $db->sql_build_array('INSERT', array(
		'post_id'      => (int) $post_id,
		'character_id' => $character_id, // 0 is valid - posted as the player directly
	));
	$db->sql_query($sql);

	// post_sidebar_image: upsert
	$sql = 'DELETE FROM ' . $post_sidebar_table . ' WHERE post_id = ' . (int) $post_id;
	$db->sql_query($sql);
	$sql = 'INSERT INTO ' . $post_sidebar_table . ' ' . $db->sql_build_array('INSERT', array(
		'post_id'  => (int) $post_id,
		'image_id' => $image_id, // 0 = no per-post choice, character default renders instead
	));
	$db->sql_query($sql);

	// "Set as default" - per the spec, this NEVER happens implicitly, only
	// when the player explicitly checked the box.
	if ($set_default && $character_id)
	{
		$sql = 'DELETE FROM ' . $characters_active_table . ' WHERE user_id = ' . $my_user_id;
		$db->sql_query($sql);
		$sql = 'INSERT INTO ' . $characters_active_table . ' ' . $db->sql_build_array('INSERT', array(
			'user_id'      => $my_user_id,
			'character_id' => $character_id,
			'updated_at'   => time(),
		));
		$db->sql_query($sql);
	}
}
