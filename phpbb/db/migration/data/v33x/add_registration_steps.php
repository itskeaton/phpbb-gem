<?php
/**
 * Gem - Registration Wizard: steps
 *
 * ACP-configurable sequence of notices a new registrant must page through
 * (and optionally acknowledge) before reaching phpBB's real registration
 * form. require_acknowledgment is per-step - some steps might be pure
 * informational chrome (a welcome banner), others (content warnings, site
 * rules) need an explicit "I have read this" checkbox before continuing.
 */

namespace phpbb\db\migration\data\v33x;

class add_registration_steps extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'registration_steps');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_wanted_ucp_module');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'registration_steps' => array(
					'COLUMNS' => array(
						'step_id'                => array('UINT', NULL, 'auto_increment'),
						'title'                  => array('VCHAR:255', ''),
						'content'                => array('MTEXT', ''),
						'require_acknowledgment' => array('BOOL', 1),
						'sort_order'             => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'step_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'registration_steps',
			),
		);
	}
}
