<?php
/**
 * Gem - Wanted Ads: field visibility toggles
 *
 * Two more booleans on the same field-visibility pattern already used for
 * show_on_roster / show_in_showcase. Since there are only ever two wanted
 * ad types (character, plot), a full join-table management system is
 * unnecessary overhead - these are just two more checkboxes on the
 * existing field edit form.
 */

namespace phpbb\db\migration\data\v33x;

class add_wanted_field_visibility extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'profile_fields', 'wanted_character_field');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_connections_ucp_module');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'profile_fields' => array(
					'wanted_character_field' => array('BOOL', 0),
					'wanted_plot_field'       => array('BOOL', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'profile_fields' => array(
					'wanted_character_field',
					'wanted_plot_field',
				),
			),
		);
	}
}
