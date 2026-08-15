<?php
/**
 * Gem - Wanted Ads config
 *
 * gem_wanted_ad_cap: max active wanted-character ads PER CHARACTER
 * (not per player - matches how ads are now tied to a specific character,
 * not a flat account). 0 = unlimited. No cap on plots, matching TGG's
 * observed behavior (plots were never numerically capped there either).
 */

namespace phpbb\db\migration\data\v33x;

class add_wanted_config extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->config->offsetExists('gem_wanted_ad_cap');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_wanted_plot_tag_maps');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('gem_wanted_ad_cap', 10)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('gem_wanted_ad_cap')),
		);
	}
}
