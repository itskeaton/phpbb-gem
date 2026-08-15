<?php
/**
 * Gem - Post/Sidebar Image link
 *
 * LiveJournal-icon-style: which sidebar-album image (if any) was chosen for
 * this specific post. image_id = 0 means no per-post choice was made - the
 * character's default sidebar image (character_gallery.is_default) renders
 * instead, per the spec.
 */

namespace phpbb\db\migration\data\v33x;

class add_post_sidebar_image_link extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'post_sidebar_image');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_post_character_link');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'post_sidebar_image' => array(
					'COLUMNS' => array(
						'post_id'  => array('UINT', 0),
						'image_id' => array('UINT', 0), // 0 = no per-post choice, use the character's default
					),
					'PRIMARY_KEY' => 'post_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'post_sidebar_image',
			),
		);
	}
}
