<?php
/**
 * Gem - adds the 'ticket_categories' mode to the existing Gem ACP module,
 * rather than registering a whole new module - same "Gem" category,
 * consistent with how fields/sections/settings were added incrementally.
 */

namespace phpbb\db\migration\data\v33x;

class add_ticket_categories_acp_mode extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_ticket_replies');
	}

	public function update_data()
	{
		return array(
			array('module.add', array('acp', 'ACP_CAT_GEM', array(
				'module_basename' => 'acp_profile_fields',
				'modes'           => array('ticket_categories'),
			))),
		);
	}

	public function revert_data()
	{
		return array(
			array('module.remove', array('acp', 'ACP_CAT_GEM', array(
				'module_basename' => 'acp_profile_fields',
				'modes'           => array('ticket_categories'),
			))),
		);
	}
}
