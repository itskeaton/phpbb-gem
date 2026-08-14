<?php
/**
 * Gem - adds the per-field "show on roster listing" toggle. Distinct from
 * `searchable` (which controls filter availability) and separate from the
 * full character profile page, which renders every applicable field
 * regardless of this flag - this only controls what appears on the
 * roster's compact list view. Defaults to off, keeping the listing pared
 * down to name + avatar unless an admin opts a field in.
 */

namespace phpbb\db\migration\data\v33x;

class add_roster_field_visibility extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'profile_fields', 'show_on_roster');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_characters_ucp_module');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'profile_fields' => array(
					'show_on_roster' => array('BOOL', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'profile_fields' => array(
					'show_on_roster',
				),
			),
		);
	}
}
