<?php
/**
 * Gem - Character Status Log
 *
 * One row per status transition on a character - archiving (with the
 * player's reason), unarchiving, and eventually approve/decline once the
 * Ticketing System lands. Gives a full audit trail for characters that get
 * released and re-applied-for multiple times, rather than a single
 * overwritable "reason" column that only remembers the most recent change.
 */

namespace phpbb\db\migration\data\v33x;

class add_character_status_log extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'character_status_log');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_dynamic_profile_fields');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'character_status_log' => array(
					'COLUMNS' => array(
						'log_id'       => array('UINT', NULL, 'auto_increment'),
						'character_id' => array('UINT', 0),
						'old_status'   => array('TINT:2', 0),
						'new_status'   => array('TINT:2', 0),
						'reason'       => array('MTEXT', ''),
						'changed_by'   => array('UINT', 0), // user_id of whoever made the change - the player themselves, or staff
						'changed_at'   => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'log_id',
					'KEYS' => array(
						'character_id' => array('INDEX', 'character_id'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'character_status_log',
			),
		);
	}
}
