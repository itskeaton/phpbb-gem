<?php
/**
 * Gem - Character Gallery
 *
 * Two fixed albums per character - 'sidebar' and 'misc' - per the spec.
 * Only 'sidebar' images ever appear in the per-post picker; 'misc' is for
 * general display on the character's public profile.
 *
 * SIMPLIFICATION, consistent with avatar/showcase_image: this stores plain
 * image URLs, not real file uploads. No storage quota control is built for
 * the same reason there's no file-size cost on our end to police - revisit
 * if/when real upload storage gets built.
 *
 * album is a plain string, not a hard enum, so adding a third category
 * later (if ever wanted) is a config/data change, not a schema migration -
 * same reasoning as field_type on profile_fields.
 */

namespace phpbb\db\migration\data\v33x;

class add_character_gallery extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'character_gallery');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_showcase_field_visibility');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'character_gallery' => array(
					'COLUMNS' => array(
						'image_id'     => array('UINT', NULL, 'auto_increment'),
						'character_id' => array('UINT', 0),
						'album'        => array('VCHAR:16', 'misc'), // 'sidebar' | 'misc'
						'image_url'    => array('VCHAR:255', ''),
						'label'        => array('VCHAR:255', ''),
						'is_default'   => array('BOOL', 0), // only meaningful within album = 'sidebar'
						'sort_order'   => array('UINT', 0),
						'created_at'   => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'image_id',
					'KEYS' => array(
						'character_id' => array('INDEX', 'character_id'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'character_gallery',
			),
		);
	}
}
