<?php
/**
 * Gem - adds 'shop_items', 'points_award', and 'points_forum_rules' modes
 * to the existing Gem ACP module, same incremental pattern as every other
 * mode.
 */

namespace phpbb\db\migration\data\v33x;

class add_points_shop_acp_modes extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_points_forum_rules');
	}

	public function update_data()
	{
		return array(
			array('module.add', array('acp', 'ACP_CAT_GEM', array(
				'module_basename' => 'acp_profile_fields',
				'modes'           => array('shop_items', 'points_award', 'points_forum_rules'),
			))),
		);
	}

	public function revert_data()
	{
		return array(
			array('module.remove', array('acp', 'ACP_CAT_GEM', array(
				'module_basename' => 'acp_profile_fields',
				'modes'           => array('shop_items', 'points_award', 'points_forum_rules'),
			))),
		);
	}
}
