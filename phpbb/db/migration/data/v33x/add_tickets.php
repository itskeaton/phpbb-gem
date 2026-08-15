<?php
/**
 * Gem - Ticketing System: tickets
 *
 * status: 1=open, 2=in_progress, 3=resolved (per spec's simple lifecycle -
 * "resolved" covers closed/approved/declined, distinguished by `resolution`).
 * resolution: null while unresolved; 'closed' | 'approved' | 'declined' once
 * status = resolved. approved/declined only ever apply to Character
 * Application category tickets.
 *
 * character_id: set automatically for Character Application tickets (the
 * pending character this ticket is reviewing) - this is what makes
 * approve/decline able to cascade into the character's own status.
 *
 * claimed_by: lightweight, not formal routing - any permitted staff member
 * sees every open ticket regardless of claim state, this is just "who's on
 * it" visibility, not an access restriction.
 */

namespace phpbb\db\migration\data\v33x;

class add_tickets extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'tickets');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_ticket_category_fields');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'tickets' => array(
					'COLUMNS' => array(
						'ticket_id'         => array('UINT', NULL, 'auto_increment'),
						'category_id'       => array('UINT', 0),
						'user_id'           => array('UINT', 0), // submitter
						'character_id'      => array('UINT', 0), // 0 = not linked (only set for Character Application tickets)
						'linked_topic_id'   => array('UINT', 0), // optional, non-application categories
						'status'            => array('TINT:2', 1),
						'resolution'        => array('VCHAR:16', ''), // '' | 'closed' | 'approved' | 'declined'
						'resolution_reason' => array('MTEXT', ''), // staff-only, per the declined-reason spec decision
						'claimed_by'        => array('UINT', 0),
						'created_at'        => array('UINT', 0),
						'updated_at'        => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'ticket_id',
					'KEYS' => array(
						'category_id' => array('INDEX', 'category_id'),
						'user_id'     => array('INDEX', 'user_id'),
						'status'      => array('INDEX', 'status'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'tickets',
			),
		);
	}
}
