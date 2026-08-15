<?php
/**
 * Gem - Ticketing System: field-to-category assignment
 *
 * Many-to-many, since one field could reasonably be assigned to several
 * categories (e.g. a "Character Name" text field on both an Application
 * category and a Character Transfer category, if one existed). This is
 * the join table that makes the field-library-reuse decision real - fields
 * themselves still live in phpbb_profile_fields (component 1), this just
 * says which categories render which fields on their submission form.
 */

namespace phpbb\db\migration\data\v33x;

class add_ticket_category_fields extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'ticket_category_fields');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_ticket_categories');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'ticket_category_fields' => array(
					'COLUMNS' => array(
						'category_id' => array('UINT', 0),
						'field_id'    => array('UINT', 0),
						'sort_order'  => array('UINT', 0),
					),
					'KEYS' => array(
						'category_id' => array('INDEX', 'category_id'),
						'field_id'    => array('INDEX', 'field_id'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'ticket_category_fields',
			),
		);
	}
}
