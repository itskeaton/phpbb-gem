<?php
/**
 * Gem - Shop: purchases
 *
 * Deliberately the ONLY place perk bonuses live - no separate "perks"
 * table duplicating this. A player's effective cap bonus for a given
 * item_type is computed as SUM(effect_amount) across their purchases of
 * items with that type, joined at query time. Single source of truth,
 * nothing to keep in sync.
 */

namespace phpbb\db\migration\data\v33x;

class add_shop_purchases extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'shop_purchases');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_shop_items');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'shop_purchases' => array(
					'COLUMNS' => array(
						'purchase_id' => array('UINT', NULL, 'auto_increment'),
						'user_id'     => array('UINT', 0),
						'item_id'     => array('UINT', 0),
						'created_at'  => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'purchase_id',
					'KEYS' => array(
						'user_id' => array('INDEX', 'user_id'),
						'item_id' => array('INDEX', 'item_id'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'shop_purchases',
			),
		);
	}
}
