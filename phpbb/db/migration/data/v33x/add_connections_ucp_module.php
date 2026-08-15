<?php
/**
 * Gem - registers the Connections UCP module under the existing Gem
 * UCP category, alongside Character Management and Tickets.
 */

namespace phpbb\db\migration\data\v33x;

class add_connections_ucp_module extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_connection_categories_acp_mode');
	}

	public function update_data()
	{
		return array(
			array('module.add', array('ucp', 'UCP_CAT_GEM', array(
				'module_basename' => 'ucp_connections',
				'modes'           => array('connections'),
			))),
		);
	}

	public function revert_data()
	{
		return array(
			array('module.remove', array('ucp', 'UCP_CAT_GEM', array(
				'module_basename' => 'ucp_connections',
				'modes'           => array('connections'),
			))),
		);
	}
}
