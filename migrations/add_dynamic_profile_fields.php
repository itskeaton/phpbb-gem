<?php
/**
 * Gem - Dynamic Profile Fields
 *
 * Replaces CPF entirely. Bio/profile content for players and characters -
 * and, from the Ticketing System migration onward, per-category ticket
 * submission forms - all render through this same field library.
 *
 * Three tables:
 *   phpbb_profile_sections   grouping/header for fields, with a jump-anchor slug
 *   phpbb_profile_fields     the field definitions themselves
 *   phpbb_profile_values     the actual data, one row per (field, owner)
 *
 * DESIGN NOTES
 *
 * field_type is a VARCHAR, not a numeric enum. New types (a provider list
 * changes, a new embed kind, etc.) are a data change, not a schema migration.
 * Known values as of this migration: text, textarea, select, multiselect,
 * date, url, checkbox, image, songlist.
 *
 * field_options (MTEXT, JSON) only has meaning for select/multiselect - it
 * holds the choice list. Every other field_type leaves it empty.
 *
 * required_enforcement is deliberately separate from required (bool) - the
 * ACP requirement was "maximum flexibility", so *when* a required field
 * actually gets enforced (at creation, at approval, or both) is itself
 * per-field configurable rather than a single global rule.
 *
 * owner_type on phpbb_profile_values is forward-compatible with the
 * Ticketing System migration: 1=player, 2=character today, 3=ticket once
 * that migration lands. No schema change needed when that happens - it's
 * just a new value in an existing plain-integer column.
 *
 * section_id of 0 on phpbb_profile_fields means "ungrouped" (phpBB's usual
 * sentinel-over-NULL convention for optional FKs), not a real section row.
 */

namespace phpbb\db\migration\data\v33x;

class add_dynamic_profile_fields extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'profile_fields');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_player_character_split');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'profile_sections' => array(
					'COLUMNS' => array(
						'section_id'   => array('UINT', NULL, 'auto_increment'),
						'section_name' => array('VCHAR:255', ''),
						'anchor_slug'  => array('VCHAR:255', ''), // blank = auto-derive from section_name at render time
						'sort_order'   => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'section_id',
				),
				$this->table_prefix . 'profile_fields' => array(
					'COLUMNS' => array(
						'field_id'             => array('UINT', NULL, 'auto_increment'),
						'section_id'           => array('UINT', 0), // 0 = ungrouped
						'field_key'            => array('VCHAR:255', ''), // unique internal slug, used as the template variable name
						'label'                => array('VCHAR:255', ''),
						'field_type'           => array('VCHAR:32', 'text'),
						'field_options'        => array('MTEXT', ''), // JSON - select/multiselect choice list only
						'applies_to'           => array('TINT:2', 3), // 1=player, 2=character, 3=both
						'required'             => array('BOOL', 0),
						'required_enforcement' => array('VCHAR:16', 'creation'), // creation | approval | both
						'searchable'           => array('BOOL', 0), // usable as a roster/ticket filter
						'sort_order'           => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'field_id',
					'KEYS' => array(
						'section_id' => array('INDEX', 'section_id'),
						'field_key'  => array('UNIQUE', 'field_key'),
					),
				),
				$this->table_prefix . 'profile_values' => array(
					'COLUMNS' => array(
						'value_id'   => array('UINT', NULL, 'auto_increment'),
						'field_id'   => array('UINT', 0),
						'owner_type' => array('TINT:2', 1), // 1=player, 2=character, 3=ticket (from Ticketing System migration on)
						'owner_id'   => array('UINT', 0),
						'value'      => array('MTEXT', ''), // JSON-encoded for multiselect/songlist, plain text otherwise
					),
					'PRIMARY_KEY' => 'value_id',
					'KEYS' => array(
						'field_id' => array('INDEX', 'field_id'),
						'owner'    => array('INDEX', array('owner_type', 'owner_id')),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'profile_values',
				$this->table_prefix . 'profile_fields',
				$this->table_prefix . 'profile_sections',
			),
		);
	}
}
