<?php
/**
 * Gem - registers the Wanted Ads UCP module (submission dashboard for
 * both character ads and plots) under the existing Gem UCP category.
 */

namespace phpbb\db\migration\data\v33x;

class add_wanted_ucp_module extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_wanted_umbrella_tags_acp_mode');
	}

	public function update_data()
	{
		return array(
			array('module.add', array('ucp', 'UCP_CAT_GEM', array(
				'module_basename' => 'ucp_wanted',
				'modes'           => array('wanted'),
			))),
		);
	}

	public function revert_data()
	{
		return array(
			array('module.remove', array('ucp', 'UCP_CAT_GEM', array(
				'module_basename' => 'ucp_wanted',
				'modes'           => array('wanted'),
			))),
		);
	}
}
