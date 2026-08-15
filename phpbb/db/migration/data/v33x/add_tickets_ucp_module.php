<?php
/**
 * Gem - registers the Ticketing System UCP module, nested under the
 * existing "Gem" UCP category alongside Character Management.
 */

namespace phpbb\db\migration\data\v33x;

class add_tickets_ucp_module extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_ticket_categories_acp_mode');
	}

	public function update_data()
	{
		return array(
			array('module.add', array('ucp', 'UCP_CAT_GEM', array(
				'module_basename' => 'ucp_tickets',
				'modes'           => array('tickets'),
			))),
		);
	}

	public function revert_data()
	{
		return array(
			array('module.remove', array('ucp', 'UCP_CAT_GEM', array(
				'module_basename' => 'ucp_tickets',
				'modes'           => array('tickets'),
			))),
		);
	}
}
