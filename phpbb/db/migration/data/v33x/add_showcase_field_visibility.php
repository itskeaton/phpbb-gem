<?php
/**
 * Gem - adds a per-field "show in player showcase hover" toggle. Separate
 * from show_on_roster (compact roster listing) and separate from the full
 * profile page (which shows everything) - this specifically controls which
 * field(s) appear in the hover/reveal on a player's character showcase
 * grid. Not limited to a single "wanted plot" field - an admin can flag as
 * many fields as they want to appear there.
 */

namespace phpbb\db\migration\data\v33x;

class add_showcase_field_visibility extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'profile_fields', 'show_in_showcase');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_character_showcase_image');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'profile_fields' => array(
					'show_in_showcase' => array('BOOL', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'profile_fields' => array(
					'show_in_showcase',
				),
			),
		);
	}
}
