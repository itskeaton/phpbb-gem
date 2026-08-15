<?php
/**
 * Gem - Ticketing System: replies
 *
 * The private conversation thread on a ticket - visible to the submitter
 * and any staff with ticket-management permission, per spec. Plain text
 * storage (no BBCode parsing) to keep this simple; upgrade later if
 * formatting turns out to matter for staff/player conversations.
 */

namespace phpbb\db\migration\data\v33x;

class add_ticket_replies extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'ticket_replies');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_tickets');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'ticket_replies' => array(
					'COLUMNS' => array(
						'reply_id'   => array('UINT', NULL, 'auto_increment'),
						'ticket_id'  => array('UINT', 0),
						'user_id'    => array('UINT', 0),
						'message'    => array('MTEXT', ''),
						'is_staff'   => array('BOOL', 0), // for display styling - staff replies vs submitter replies
						'created_at' => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'reply_id',
					'KEYS' => array(
						'ticket_id' => array('INDEX', 'ticket_id'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'ticket_replies',
			),
		);
	}
}
