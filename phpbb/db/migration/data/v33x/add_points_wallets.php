<?php
/**
 * Gem - Points: wallets
 *
 * One row per player (not character - points are per-player per spec).
 * balance is a cached/denormalized value, kept in sync by every ledger
 * write (see points_helper.php) - not the source of truth by itself, the
 * ledger is. Fast reads without needing to SUM the whole ledger every time.
 */

namespace phpbb\db\migration\data\v33x;

class add_points_wallets extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'points_wallets');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_registration_steps_acp_mode');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'points_wallets' => array(
					'COLUMNS' => array(
						'user_id'    => array('UINT', 0),
						'balance'    => array('INT', 0),
						'updated_at' => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'user_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'points_wallets',
			),
		);
	}
}
