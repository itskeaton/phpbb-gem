<?php
/**
 * Gem - adds the 'registration_steps' mode to the existing Gem ACP
 * module, same incremental pattern as every other category-style mode.
 */

namespace phpbb\db\migration\data\v33x;

class add_registration_steps_acp_mode extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_registration_progress');
	}

	public function update_data()
	{
		return array(
			array('module.add', array('acp', 'ACP_CAT_GEM', array(
				'module_basename' => 'acp_profile_fields',
				'modes'           => array('registration_steps'),
			))),
		);
	}

	public function revert_data()
	{
		return array(
			array('module.remove', array('acp', 'ACP_CAT_GEM', array(
				'module_basename' => 'acp_profile_fields',
				'modes'           => array('registration_steps'),
			))),
		);
	}
}
