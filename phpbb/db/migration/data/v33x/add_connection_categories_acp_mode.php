<?php
/**
 * Gem - adds the 'connection_categories' mode to the existing Gem ACP
 * module, same incremental pattern used for ticket_categories.
 */

namespace phpbb\db\migration\data\v33x;

class add_connection_categories_acp_mode extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_connections');
	}

	public function update_data()
	{
		return array(
			array('module.add', array('acp', 'ACP_CAT_GEM', array(
				'module_basename' => 'acp_profile_fields',
				'modes'           => array('connection_categories'),
			))),
		);
	}

	public function revert_data()
	{
		return array(
			array('module.remove', array('acp', 'ACP_CAT_GEM', array(
				'module_basename' => 'acp_profile_fields',
				'modes'           => array('connection_categories'),
			))),
		);
	}
}
