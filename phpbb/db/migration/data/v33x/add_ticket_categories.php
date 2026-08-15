<?php
/**
 * Gem - Ticketing System: categories
 *
 * ACP-configurable list, not hardcoded to "Application"/"General Inquiry".
 * Character Application is just one category among however many exist -
 * identified by is_character_application (bool), not by name matching,
 * so renaming the category in ACP doesn't break the approve/decline ->
 * character-status mechanic.
 *
 * required_group: 0 = anyone logged in can submit to this category;
 * otherwise restricts submission to members of that group.
 */

namespace phpbb\db\migration\data\v33x;

class add_ticket_categories extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'ticket_categories');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_gallery_quota_config');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'ticket_categories' => array(
					'COLUMNS' => array(
						'category_id'                 => array('UINT', NULL, 'auto_increment'),
						'category_name'                => array('VCHAR:255', ''),
						'is_character_application'    => array('BOOL', 0),
						'required_group'               => array('UINT', 0), // 0 = any logged-in player
						'sort_order'                   => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'category_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'ticket_categories',
			),
		);
	}
}
