<?php
/**
 * Gem - Post/Character link
 *
 * One row per post, recording which character (if any) it was posted as.
 * Adjacent table rather than altering phpbb_posts directly, same approach
 * used everywhere else. character_id = 0 means posted as the player
 * directly (no character selected) - a valid, normal state, not an error.
 *
 * NOTE: this table only gets written to once the switcher UI is wired into
 * a real posting flow (posting.php) - see component 6 delivery notes. It
 * does not, by itself, change how any post currently displays - that's
 * component 7 (posting-side integration)'s job, reading this table.
 */

namespace phpbb\db\migration\data\v33x;

class add_post_character_link extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'post_character');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_character_gallery');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'post_character' => array(
					'COLUMNS' => array(
						'post_id'      => array('UINT', 0),
						'character_id' => array('UINT', 0), // 0 = posted as the player, no character
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
				$this->table_prefix . 'post_character',
			),
		);
	}
}
