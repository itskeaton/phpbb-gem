<?php
/**
 * Gem - registers the Shop UCP module (browse catalog, purchase, view
 * balance/history) under the existing Gem UCP category.
 */

namespace phpbb\db\migration\data\v33x;

class add_points_shop_ucp_module extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_points_shop_acp_modes');
	}

	public function update_data()
	{
		return array(
			array('module.add', array('ucp', 'UCP_CAT_GEM', array(
				'module_basename' => 'ucp_shop',
				'modes'           => array('shop'),
			))),
		);
	}

	public function revert_data()
	{
		return array(
			array('module.remove', array('ucp', 'UCP_CAT_GEM', array(
				'module_basename' => 'ucp_shop',
				'modes'           => array('shop'),
			))),
		);
	}
}
