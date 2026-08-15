<?php
/**
 * Gem - Connections: categories
 *
 * ACP-configurable list (name + colour), same pattern as ticket categories.
 * Colour renders as an avatar border wherever connections are displayed -
 * the "category-colored avatar borders" concept.
 */

namespace phpbb\db\migration\data\v33x;

class add_connection_categories extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'connection_categories');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_tickets_mcp_module');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'connection_categories' => array(
					'COLUMNS' => array(
						'category_id'   => array('UINT', NULL, 'auto_increment'),
						'category_name' => array('VCHAR:255', ''),
						'color'         => array('VCHAR:6', '999999'), // hex, no leading #
						'sort_order'    => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'category_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'connection_categories',
			),
		);
	}
}
