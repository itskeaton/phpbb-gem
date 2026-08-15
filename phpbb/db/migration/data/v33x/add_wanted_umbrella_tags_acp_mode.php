<?php
/**
 * Gem - adds the 'wanted_umbrella_tags' mode to the existing Gem ACP
 * module, same incremental pattern as ticket/connection categories.
 */

namespace phpbb\db\migration\data\v33x;

class add_wanted_umbrella_tags_acp_mode extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_wanted_config');
	}

	public function update_data()
	{
		return array(
			array('module.add', array('acp', 'ACP_CAT_GEM', array(
				'module_basename' => 'acp_profile_fields',
				'modes'           => array('wanted_umbrella_tags'),
			))),
		);
	}

	public function revert_data()
	{
		return array(
			array('module.remove', array('acp', 'ACP_CAT_GEM', array(
				'module_basename' => 'acp_profile_fields',
				'modes'           => array('wanted_umbrella_tags'),
			))),
		);
	}
}
