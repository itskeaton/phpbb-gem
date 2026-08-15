<?php
/**
 * Gem - Points config
 *
 * gem_points_per_approved_application: awarded when a Character
 * Application ticket is approved (0 = disabled).
 * gem_points_per_registration: awarded once to a new player. Fires on
 * first Gem UCP visit by default; fires at actual account creation
 * instead once the ucp_register.php patch is applied - see
 * docs/tier3-registration-hardgate.md and points_helper.php's
 * gem_maybe_award_registration_bonus().
 *
 * Per-post/per-word earning is NOT a flat global rate - see
 * add_points_forum_rules.php. Different forums can have entirely
 * different rules (some per-post, some per-word, some earning nothing at
 * all) via a per-forum whitelist table instead of one global setting.
 *
 * KNOWN GAP, flagged rather than silently ignored: no anti-abuse
 * throttling on either per-post or per-word forum earning (e.g. daily
 * caps, minimum post length/quality checks). A player could technically
 * farm low-effort posts for points in a whitelisted forum. Worth a
 * follow-up if this becomes a real problem in practice - not solved here.
 */

namespace phpbb\db\migration\data\v33x;

class add_points_config extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->config->offsetExists('gem_points_per_approved_application');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_shop_purchases');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('gem_points_per_approved_application', 0)),
			array('config.add', array('gem_points_per_registration', 0)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('gem_points_per_approved_application')),
			array('config.remove', array('gem_points_per_registration')),
		);
	}
}
