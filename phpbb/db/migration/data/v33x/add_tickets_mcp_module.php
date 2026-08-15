<?php
/**
 * Gem - registers the Tickets MCP module. Auth is 'acl_m_' (any moderator
 * permission in any forum) as a stand-in - same known simplification
 * already flagged elsewhere in this build (see ucp_characters.php's
 * unarchive gating). Replace with a dedicated permission before relying on
 * this for anything beyond testing.
 */

namespace phpbb\db\migration\data\v33x;

class add_tickets_mcp_module extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_tickets_ucp_module');
	}

	public function update_data()
	{
		return array(
			array('module.add', array('mcp', 0, 'MCP_GEM')),
			array('module.add', array('mcp', 'MCP_GEM', array(
				'module_basename' => 'mcp_tickets',
				'modes'           => array('queue'),
			))),
		);
	}

	public function revert_data()
	{
		return array(
			array('module.remove', array('mcp', 'MCP_GEM', array(
				'module_basename' => 'mcp_tickets',
				'modes'           => array('queue'),
			))),
			array('module.remove', array('mcp', 0, 'MCP_GEM')),
		);
	}
}
