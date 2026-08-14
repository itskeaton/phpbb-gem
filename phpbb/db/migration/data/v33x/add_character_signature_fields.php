<?php
/**
 * Gem - adds the two columns phpBB's text-parsing pipeline needs alongside
 * a signature (uid/bitfield), which were missed on the original
 * phpbb_characters table. Without these, character signatures can't be
 * parsed/rendered through phpBB's normal generate_text_for_storage() /
 * generate_text_for_display() flow the way user signatures are.
 */

namespace phpbb\db\migration\data\v33x;

class add_character_signature_fields extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'characters', 'signature_bbcode_uid');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_gem_config');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'characters' => array(
					'signature_bbcode_uid'      => array('VCHAR:8', ''),
					'signature_bbcode_bitfield' => array('VCHAR:255', ''),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'characters' => array(
					'signature_bbcode_uid',
					'signature_bbcode_bitfield',
				),
			),
		);
	}
}
