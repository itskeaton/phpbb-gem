<?php
/**
 * Gem - Points: per-forum earning rules
 *
 * Whitelist model - a forum with no row here earns nothing. Each
 * whitelisted forum picks its own rule_type independently: some forums
 * might earn a flat amount per post, others per-word, mixed freely across
 * the board.
 *
 * rule_type = 'per_post':  award `amount` points per post, flat.
 * rule_type = 'per_words': award `amount` points per `words_per_point`
 *                          words in the post (floor-divided - a 250-word
 *                          post with words_per_point=100, amount=1 earns
 *                          2 points, not 2.5).
 */

namespace phpbb\db\migration\data\v33x;

class add_points_forum_rules extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'points_forum_rules');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_points_config');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'points_forum_rules' => array(
					'COLUMNS' => array(
						'forum_id'        => array('UINT', 0),
						'rule_type'       => array('VCHAR:16', 'per_post'), // 'per_post' | 'per_words'
						'amount'          => array('UINT', 0),
						'words_per_point' => array('UINT', 100), // only meaningful when rule_type = 'per_words'
					),
					'PRIMARY_KEY' => 'forum_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'points_forum_rules',
			),
		);
	}
}
