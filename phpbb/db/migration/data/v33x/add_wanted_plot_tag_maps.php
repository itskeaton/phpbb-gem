<?php
/**
 * Gem - Wanted Plots: tag maps (umbrella + specific, both many-to-many)
 */

namespace phpbb\db\migration\data\v33x;

class add_wanted_plot_tag_maps extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'wanted_plot_tag_map');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_wanted_plot_tags');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'wanted_plot_tag_map' => array(
					'COLUMNS' => array(
						'ad_id'  => array('UINT', 0),
						'tag_id' => array('UINT', 0),
					),
					'KEYS' => array(
						'ad_id'  => array('INDEX', 'ad_id'),
						'tag_id' => array('INDEX', 'tag_id'),
					),
				),
				$this->table_prefix . 'wanted_plot_umbrella_map' => array(
					'COLUMNS' => array(
						'ad_id'  => array('UINT', 0),
						'tag_id' => array('UINT', 0), // FK -> wanted_umbrella_tags.tag_id
					),
					'KEYS' => array(
						'ad_id'  => array('INDEX', 'ad_id'),
						'tag_id' => array('INDEX', 'tag_id'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'wanted_plot_tag_map',
				$this->table_prefix . 'wanted_plot_umbrella_map',
			),
		);
	}
}
