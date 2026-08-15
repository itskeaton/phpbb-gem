<?php
/**
 * Gem - Points & Shop core logic
 *
 * Every balance change goes through gem_points_transaction() so the
 * ledger and the cached wallet balance never drift apart. Nothing else in
 * this codebase should write to phpbb_points_wallets or phpbb_points_ledger
 * directly.
 */

/**
 * The one function that actually moves points. Positive amount = earn/
 * grant, negative = spend/deduct. Writes a ledger entry and updates the
 * cached wallet balance together.
 *
 * @param int    $user_id
 * @param int    $amount      Positive or negative. Never zero (caller's job to check).
 * @param string $reason      Human-readable, shown to staff (and eventually the player).
 * @param string $entry_type  'earn_post' | 'earn_application' | 'earn_registration' | 'manual_grant' | 'manual_deduct' | 'spend'
 * @param int    $related_id  Contextual - post_id, ticket_id, purchase_id, or 0.
 * @param int    $changed_by  0 = system/automatic, else the staff user_id for manual entries.
 * @return int the new balance
 */
function gem_points_transaction($user_id, $amount, $reason, $entry_type, $related_id = 0, $changed_by = 0)
{
	global $db, $table_prefix;

	$wallets_table = $table_prefix . 'points_wallets';
	$ledger_table  = $table_prefix . 'points_ledger';

	$sql = 'INSERT INTO ' . $ledger_table . ' ' . $db->sql_build_array('INSERT', array(
		'user_id'    => (int) $user_id,
		'amount'     => (int) $amount,
		'reason'     => $reason,
		'entry_type' => $entry_type,
		'related_id' => (int) $related_id,
		'changed_by' => (int) $changed_by,
		'created_at' => time(),
	));
	$db->sql_query($sql);

	$sql = 'SELECT user_id FROM ' . $wallets_table . ' WHERE user_id = ' . (int) $user_id;
	$result = $db->sql_query($sql);
	$exists = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if ($exists)
	{
		$sql = 'UPDATE ' . $wallets_table . ' SET balance = balance + ' . (int) $amount . ', updated_at = ' . time() . '
				WHERE user_id = ' . (int) $user_id;
		$db->sql_query($sql);
	}
	else
	{
		$sql = 'INSERT INTO ' . $wallets_table . ' ' . $db->sql_build_array('INSERT', array(
			'user_id'    => (int) $user_id,
			'balance'    => (int) $amount,
			'updated_at' => time(),
		));
		$db->sql_query($sql);
	}

	return gem_get_balance($user_id);
}

function gem_get_balance($user_id)
{
	global $db, $table_prefix;

	$sql = 'SELECT balance FROM ' . $table_prefix . 'points_wallets WHERE user_id = ' . (int) $user_id;
	$result = $db->sql_query($sql);
	$balance = $db->sql_fetchfield('balance');
	$db->sql_freeresult($result);

	return $balance !== false ? (int) $balance : 0;
}

/**
 * Strips BBCode tags and HTML, collapses whitespace, and counts words.
 * Used for per-word forum earning rules - deliberately counts against the
 * visible/readable text, not raw stored markup, so a post padded with
 * BBCode doesn't inflate its word count.
 */
function gem_count_words($raw_text)
{
	$text = preg_replace('/\[.*?\]/s', ' ', $raw_text); // strip BBCode tags
	$text = strip_tags($text);
	$text = trim(preg_replace('/\s+/', ' ', $text));

	if ($text === '')
	{
		return 0;
	}

	return count(explode(' ', $text));
}

/**
 * Awards points for a post according to that post's forum's rule, if any.
 * Whitelist model - a forum with no row in phpbb_points_forum_rules earns
 * nothing at all. Self-contained: looks up everything it needs from
 * phpbb_posts by post_id rather than depending on posting.php's local
 * variables, so the call site only ever needs to pass $post_id.
 *
 * Guards against double-awarding the same post (e.g. if this ever gets
 * called again on a post edit) by checking the ledger for an existing
 * earn_post entry with this related_id first.
 */
function gem_award_points_for_post($post_id)
{
	global $db, $table_prefix;

	$sql = 'SELECT poster_id, forum_id, post_text, bbcode_uid FROM ' . POSTS_TABLE . ' WHERE post_id = ' . (int) $post_id;
	$result = $db->sql_query($sql);
	$post = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if (!$post)
	{
		return;
	}

	$sql = 'SELECT * FROM ' . $table_prefix . 'points_forum_rules WHERE forum_id = ' . (int) $post['forum_id'];
	$result = $db->sql_query($sql);
	$rule = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if (!$rule)
	{
		return; // forum not whitelisted - earns nothing
	}

	// Don't double-award if this post already has an earn_post entry
	$sql = 'SELECT entry_id FROM ' . $table_prefix . "points_ledger
			WHERE entry_type = 'earn_post' AND related_id = " . (int) $post_id;
	$result = $db->sql_query($sql);
	$already_awarded = (bool) $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if ($already_awarded)
	{
		return;
	}

	if ($rule['rule_type'] === 'per_words')
	{
		$word_count = gem_count_words($post['post_text']);
		$words_per_point = max(1, (int) $rule['words_per_point']);
		$points = intdiv($word_count, $words_per_point) * (int) $rule['amount'];
	}
	else
	{
		$points = (int) $rule['amount'];
	}

	if ($points <= 0)
	{
		return;
	}

	gem_points_transaction((int) $post['poster_id'], $points, 'Post made', 'earn_post', $post_id, 0);
}

/**
 * Awards points for an approved Character Application, if the rate is
 * non-zero. Called from mcp_tickets.php's approve_application() - code
 * this build owns directly, not a blind patch.
 */
function gem_award_points_for_approval($user_id, $ticket_id)
{
	global $config;

	$rate = (int) $config['gem_points_per_approved_application'];
	if ($rate <= 0)
	{
		return;
	}

	gem_points_transaction($user_id, $rate, 'Character application approved', 'earn_application', $ticket_id, 0);
}

/**
 * Awards the one-time registration bonus, if not already given. Idempotent
 * (checks the ledger first) and safe to call from every Gem UCP module's
 * entry point - whichever one a new player happens to visit first is the
 * one that actually grants it.
 *
 * By default, fires on first visit to any Gem UCP page rather than the
 * literal moment of account creation - a precise hook now exists (see
 * docs/tier3-registration-hardgate.md, patches ucp_register.php right
 * after user_add() succeeds) but requires that patch to actually be
 * applied to your install. This function stays idempotent either way -
 * once the precise hook fires, this becomes a harmless no-op for that
 * player, so both can safely coexist. Keep this fallback even after
 * applying that patch (see the doc for why - OAuth registration isn't
 * confirmed to go through the same code path).
 */
function gem_maybe_award_registration_bonus($user_id)
{
	global $db, $config, $table_prefix;

	$rate = (int) $config['gem_points_per_registration'];
	if ($rate <= 0)
	{
		return;
	}

	$sql = 'SELECT entry_id FROM ' . $table_prefix . "points_ledger
			WHERE user_id = " . (int) $user_id . " AND entry_type = 'earn_registration'";
	$result = $db->sql_query($sql);
	$already_awarded = (bool) $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if ($already_awarded)
	{
		return;
	}

	gem_points_transaction((int) $user_id, $rate, 'Welcome bonus', 'earn_registration', 0, 0);
}

/**
 * A player's effective cap for a given base config value, boosted by
 * whatever they've purchased. If the base cap is 0 (unlimited), it stays
 * unlimited regardless of purchases - there's nothing to boost.
 *
 * @param int    $user_id
 * @param int    $base_cap   The global ACP setting (e.g. gem_max_characters).
 * @param string $item_type  'character_slot' | 'gallery_quota' | 'wanted_ad_slot'
 */
function gem_get_effective_cap($user_id, $base_cap, $item_type)
{
	global $db, $table_prefix;

	if ($base_cap <= 0)
	{
		return 0; // unlimited stays unlimited
	}

	$sql = 'SELECT SUM(si.effect_amount) AS bonus
			FROM ' . $table_prefix . 'shop_purchases sp
			JOIN ' . $table_prefix . "shop_items si ON sp.item_id = si.item_id
			WHERE sp.user_id = " . (int) $user_id . "
			AND si.item_type = '" . $db->sql_escape($item_type) . "'";
	$result = $db->sql_query($sql);
	$bonus = (int) $db->sql_fetchfield('bonus');
	$db->sql_freeresult($result);

	return $base_cap + $bonus;
}
