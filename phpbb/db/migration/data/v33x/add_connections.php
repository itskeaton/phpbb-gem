<?php
/**
 * Gem - Connections
 *
 * Directed, one-sided per the spec decision - character_id is the owner
 * (whoever created this entry), connected_character_id is the target. Not
 * a symmetric pair - if both sides want to describe the relationship, each
 * creates their own independent row, potentially with different
 * categories/descriptions. This is a feature, not a data integrity gap:
 * an unreciprocated connection is a real, meaningful state.
 */

namespace phpbb\db\migration\data\v33x;

class add_connections extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'connections');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_connection_categories');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'connections' => array(
					'COLUMNS' => array(
						'connection_id'          => array('UINT', NULL, 'auto_increment'),
						'character_id'           => array('UINT', 0), // owner - whose "view" this connection represents
						'connected_character_id' => array('UINT', 0), // target
						'category_id'            => array('UINT', 0),
						'description'            => array('MTEXT', ''),
						'created_at'             => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'connection_id',
					'KEYS' => array(
						'character_id'           => array('INDEX', 'character_id'),
						'connected_character_id' => array('INDEX', 'connected_character_id'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'connections',
			),
		);
	}
}
