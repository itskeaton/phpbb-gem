<?php
/**
 * Gem - Wanted Plots: specific tags (Tier 3)
 *
 * Freeform, reusable vocabulary - matched case-insensitively and
 * auto-created on first use, same behavior as TGG's version. Distinct
 * from the fixed, admin-managed umbrella tags (Tier 1).
 */

namespace phpbb\db\migration\data\v33x;

class add_wanted_plot_tags extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'wanted_plot_tags');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_wanted_plots');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'wanted_plot_tags' => array(
					'COLUMNS' => array(
						'tag_id'   => array('UINT', NULL, 'auto_increment'),
						'tag_name' => array('VCHAR:50', ''),
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
				$this->table_prefix . 'wanted_plot_tags',
			),
		);
	}
}
