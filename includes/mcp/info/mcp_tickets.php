<?php
/**
 * Gem - Tickets MCP module info
 */

class mcp_tickets_info
{
	function module()
	{
		return array(
			'filename' => 'mcp_tickets',
			'title'    => 'MCP_GEM_TICKETS',
			'version'  => '1.0.0',
			'modes'    => array(
				'queue' => array(
					'title' => 'MCP_GEM_TICKETS_QUEUE',
					'auth'  => 'acl_m_', // stand-in - see migration doc comment
					'cat'   => array('MCP_GEM'),
				),
			),
		);
	}
}
