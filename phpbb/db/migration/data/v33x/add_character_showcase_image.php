<?php
/**
 * Gem - adds a showcase image to characters, separate from avatar. This is
 * the larger, presentation-focused image used on a player's profile
 * showcase grid (component 5) - the avatar stays small/functional
 * (post display, roster thumbnails), this is deliberately a second,
 * independent image.
 */

namespace phpbb\db\migration\data\v33x;

class add_character_showcase_image extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'characters', 'showcase_image');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_roster_field_visibility');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'characters' => array(
					'showcase_image' => array('VCHAR:255', ''),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'characters' => array(
					'showcase_image',
				),
			),
		);
	}
}
