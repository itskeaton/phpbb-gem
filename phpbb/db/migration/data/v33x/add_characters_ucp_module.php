<?php
/**
 * Gem - registers the "Gem" UCP category and the Character Management module.
 */

namespace phpbb\db\migration\data\v33x;

class add_characters_ucp_module extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_profile_fields_acp_module');
	}

	public function update_data()
	{
		return array(
			array('module.add', array('ucp', 0, 'UCP_CAT_GEM')),
			array('module.add', array('ucp', 'UCP_CAT_GEM', array(
				'module_basename' => 'ucp_characters',
				'modes'           => array('manage'),
			))),
		);
	}

	public function revert_data()
	{
		return array(
			array('module.remove', array('ucp', 'UCP_CAT_GEM', array(
				'module_basename' => 'ucp_characters',
				'modes'           => array('manage'),
			))),
			array('module.remove', array('ucp', 0, 'UCP_CAT_GEM')),
		);
	}
}
