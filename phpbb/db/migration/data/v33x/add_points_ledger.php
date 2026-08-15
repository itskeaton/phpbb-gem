<?php
/**
 * Gem - Points: ledger
 *
 * Full transaction history - the actual source of truth, wallets.balance
 * is just a cache of this. entry_type distinguishes how points moved:
 * 'earn_post', 'earn_application', 'manual_grant', 'manual_deduct', 'spend'.
 * related_id is contextual (post_id for earn_post, ticket_id for
 * earn_application, shop purchase_id for spend) - not FK-enforced since it
 * means different things per entry_type.
 */

namespace phpbb\db\migration\data\v33x;

class add_points_ledger extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'points_ledger');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_points_wallets');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'points_ledger' => array(
					'COLUMNS' => array(
						'entry_id'   => array('UINT', NULL, 'auto_increment'),
						'user_id'    => array('UINT', 0),
						'amount'     => array('INT', 0), // positive = earned/granted, negative = spent/deducted
						'reason'     => array('VCHAR:255', ''),
						'entry_type' => array('VCHAR:32', ''),
						'related_id' => array('UINT', 0),
						'changed_by' => array('UINT', 0), // 0 = system/automatic, else staff user_id for manual entries
						'created_at' => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'entry_id',
					'KEYS' => array(
						'user_id' => array('INDEX', 'user_id'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'points_ledger',
			),
		);
	}
}
