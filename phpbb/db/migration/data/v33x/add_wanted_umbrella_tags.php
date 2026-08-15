<?php
/**
 * Gem - Wanted Plots: umbrella tags (Tier 1)
 *
 * ACP-configurable, replacing TGG's hardcoded array. Fixed vocabulary,
 * admin-managed - not the same as Tier 3 specific tags, which stay
 * freeform/reusable (see add_wanted_plot_tags.php).
 */

namespace phpbb\db\migration\data\v33x;

class add_wanted_umbrella_tags extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'wanted_umbrella_tags');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_wanted_field_visibility');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'wanted_umbrella_tags' => array(
					'COLUMNS' => array(
						'tag_id'     => array('UINT', NULL, 'auto_increment'),
						'tag_name'   => array('VCHAR:255', ''),
						'sort_order' => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'tag_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'wanted_umbrella_tags',
			),
		);
	}
}
