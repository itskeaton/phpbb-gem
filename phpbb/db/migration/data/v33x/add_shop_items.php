<?php
/**
 * Gem - Shop: items
 *
 * item_type drives what happens when purchased:
 *   'character_slot'  - effect_amount added to the buyer's effective
 *                        character cap (on top of the global ACP default)
 *   'gallery_quota'    - effect_amount added to the buyer's effective
 *                        gallery image quota
 *   'wanted_ad_slot'   - effect_amount added to the buyer's effective
 *                        wanted-character-ad cap
 *   'cosmetic'          - no functional effect, effect_amount unused -
 *                        purchase is just recorded as owned. Foundation
 *                        for future cosmetic hooks, not wired to any
 *                        visual effect yet - flagged, not pretended.
 *
 * repeatable: can this be bought more than once (stacking slot/quota
 * bonuses) or is it a one-time unlock (typical for cosmetics)?
 * active: lets an item be retired from the shop without deleting purchase
 * history or breaking the effective-cap calculation for past buyers.
 */

namespace phpbb\db\migration\data\v33x;

class add_shop_items extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'shop_items');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_points_ledger');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'shop_items' => array(
					'COLUMNS' => array(
						'item_id'       => array('UINT', NULL, 'auto_increment'),
						'name'          => array('VCHAR:255', ''),
						'description'   => array('MTEXT', ''),
						'cost'          => array('UINT', 0),
						'item_type'     => array('VCHAR:32', 'cosmetic'),
						'effect_amount' => array('UINT', 0),
						'repeatable'    => array('BOOL', 0),
						'active'        => array('BOOL', 1),
						'sort_order'    => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'item_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'shop_items',
			),
		);
	}
}
