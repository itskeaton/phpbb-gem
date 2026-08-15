<?php
/**
 * Gem - Registration Wizard: progress tracking
 *
 * Keyed on phpBB's own session_id (phpBB tracks anonymous/guest sessions
 * too, so this works before the visitor has any account) rather than raw
 * PHP $_SESSION - stays consistent with phpBB's own session lifecycle and
 * cleanup instead of a parallel mechanism.
 */

namespace phpbb\db\migration\data\v33x;

class add_registration_progress extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'registration_progress');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_registration_steps');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'registration_progress' => array(
					'COLUMNS' => array(
						'session_id'   => array('VCHAR:32', ''),
						'current_step' => array('UINT', 0),
						'completed'    => array('BOOL', 0),
						'updated_at'   => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'session_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'registration_progress',
			),
		);
	}
}
