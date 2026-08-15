<?php
/**
 * Gem - Poster Identity Resolver
 *
 * This is the one piece of posting-side integration that's safe to build
 * without seeing your actual core files - it's pure logic, no template or
 * specific-file dependency. Everything that actually CALLS this (post
 * display, quoting, search results, staff/mod tools) lives inside core
 * phpBB files this build has never seen the content of, across at least
 * four separate areas per your scope answer. Rather than guess at any of
 * them, this ships as the reusable resolver + a template-variable mapping
 * plan, ready to wire in file-by-file as each real core file gets shared -
 * same pattern that worked for memberlist.php.
 *
 * SCOPE (per your answers):
 *   - Character identity applies to: post display, quoting, search results.
 *   - Staff/mod tools ALWAYS show both character name and real player
 *     identity - never character-only in a staff context. This resolver
 *     always returns both; it's the CALLER's job to decide which to show
 *     based on the viewer's permissions (gem_can_view_real_player()).
 */

/**
 * Given a post, resolves what identity should render for it: the character
 * (if one was selected via the switcher) or the player's own account.
 * Always returns the real player identity too, regardless of which was
 * used for display - staff visibility is a permissions decision made by
 * the caller, not something this function hides data to enforce.
 *
 * @param int $poster_id  The post's actual poster_id (phpbb_posts.poster_id) - always a real phpbb_users.user_id, unchanged by any of this.
 * @param int $post_id    The post_id, used to look up which character (if any) was selected.
 * @return array
 */
function gem_resolve_poster_identity($poster_id, $post_id)
{
	global $db, $table_prefix;

	$characters_table = $table_prefix . 'characters';
	$post_character_table = $table_prefix . 'post_character';

	$identity = array(
		'is_character'      => false,
		'character_id'      => 0,
		'display_name'      => null,
		'avatar_type'       => null,
		'avatar'            => null,
		'avatar_width'      => null,
		'avatar_height'     => null,
		'colour'            => null,
		'signature'         => null,
		'signature_uid'     => null,
		'signature_bitfield' => null,
		'profile_url_mode'  => 'player', // 'player' or 'character' - tells the caller which profile link to build
		'real_player_id'    => (int) $poster_id,
		'real_player_name'  => null, // ALWAYS populated, regardless of is_character - staff visibility handled by the caller
	);

	// Real player data - always fetched, always returned, regardless of
	// whether a character ends up being displayed.
	$sql = 'SELECT username, user_colour, user_avatar, user_avatar_type, user_avatar_width, user_avatar_height,
					user_sig, user_sig_bbcode_uid, user_sig_bbcode_bitfield
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $poster_id;
	$result = $db->sql_query($sql);
	$player = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if (!$player)
	{
		// Deleted/anonymous poster - nothing sensible to resolve, return as-is.
		return $identity;
	}

	$identity['real_player_name'] = $player['username'];

	// Which character (if any) was this post made as?
	$sql = 'SELECT character_id FROM ' . $post_character_table . ' WHERE post_id = ' . (int) $post_id;
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	$character_id = $row ? (int) $row['character_id'] : 0;

	if (!$character_id)
	{
		// No character selected for this post - display the player's own identity.
		$identity['display_name']       = $player['username'];
		$identity['avatar_type']        = $player['user_avatar_type'];
		$identity['avatar']             = $player['user_avatar'];
		$identity['avatar_width']       = $player['user_avatar_width'];
		$identity['avatar_height']      = $player['user_avatar_height'];
		$identity['colour']             = $player['user_colour'];
		$identity['signature']          = $player['user_sig'];
		$identity['signature_uid']      = $player['user_sig_bbcode_uid'];
		$identity['signature_bitfield'] = $player['user_sig_bbcode_bitfield'];
		return $identity;
	}

	// A character was selected - load it. Falls back to the player's own
	// identity if the character record is gone (deleted/data inconsistency)
	// rather than showing a broken/empty post.
	$sql = 'SELECT * FROM ' . $characters_table . '
			WHERE character_id = ' . $character_id . '
			AND user_id = ' . (int) $poster_id; // defensive - a character can only ever represent its own owner
	$result = $db->sql_query($sql);
	$character = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if (!$character)
	{
		$identity['display_name']       = $player['username'];
		$identity['avatar_type']        = $player['user_avatar_type'];
		$identity['avatar']             = $player['user_avatar'];
		$identity['avatar_width']       = $player['user_avatar_width'];
		$identity['avatar_height']      = $player['user_avatar_height'];
		$identity['colour']             = $player['user_colour'];
		$identity['signature']          = $player['user_sig'];
		$identity['signature_uid']      = $player['user_sig_bbcode_uid'];
		$identity['signature_bitfield'] = $player['user_sig_bbcode_bitfield'];
		return $identity;
	}

	$identity['is_character']       = true;
	$identity['character_id']       = $character_id;
	$identity['display_name']       = $character['character_name'];
	$identity['avatar_type']        = $character['avatar_type'];
	$identity['avatar']             = $character['avatar'];
	$identity['avatar_width']       = $character['avatar_width'];
	$identity['avatar_height']      = $character['avatar_height'];
	$identity['colour']             = $character['character_colour'];
	$identity['signature']          = $character['signature'];
	$identity['signature_uid']      = $character['signature_bbcode_uid'];
	$identity['signature_bitfield'] = $character['signature_bbcode_bitfield'];
	$identity['profile_url_mode']   = 'character';

	return $identity;
}

/**
 * Per your answer: staff/mod tools always show both. This is the single
 * place that decision is encoded, so it can't drift between whichever
 * staff-facing files end up calling it.
 */
function gem_can_view_real_player($auth)
{
	return $auth->acl_get('m_') || $auth->acl_get('a_');
}

/**
 * Convenience for building the right profile link depending on what was
 * resolved - character_roster.php for a character, memberlist.php for a
 * player, matching how character_roster.php and player_showcase.php
 * already link back to each other.
 */
function gem_resolve_profile_url($identity, $phpbb_root_path, $phpEx)
{
	if ($identity['profile_url_mode'] === 'character' && $identity['character_id'])
	{
		return append_sid("{$phpbb_root_path}character_roster.{$phpEx}", 'mode=profile&amp;character_id=' . $identity['character_id']);
	}

	return append_sid("{$phpbb_root_path}memberlist.{$phpEx}", 'mode=viewprofile&amp;u=' . $identity['real_player_id']);
}
