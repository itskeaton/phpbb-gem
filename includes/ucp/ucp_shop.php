<?php
/**
 * Gem - Shop UCP controller
 *
 * Single mode ('shop'). Lists active items, shows balance, handles
 * purchases. Effects (character_slot / gallery_quota / wanted_ad_slot)
 * apply automatically the moment a purchase is recorded, since
 * gem_get_effective_cap() computes bonuses live from phpbb_shop_purchases -
 * nothing further needs to happen here after the INSERT.
 */

require_once(__DIR__ . '/../gem/points_helper.php');

class ucp_shop
{
	var $u_action;

	private $shop_items_table;
	private $shop_purchases_table;
	private $points_ledger_table;

	function main($id, $mode)
	{
		global $db, $user, $template, $request, $table_prefix;

		// Registration welcome bonus - idempotent, safe to check on every
		// Gem UCP page visit. See points_helper.php for why this fires here
		// instead of at the literal moment of registration.
		if (!empty($user->data['is_registered']))
		{
			gem_maybe_award_registration_bonus((int) $user->data['user_id']);
		}

		$user->add_lang('ucp/shop');
		$this->tpl_name = 'ucp_shop';
		$this->page_title = 'UCP_SHOP_BROWSE';

		$this->shop_items_table = $table_prefix . 'shop_items';
		$this->shop_purchases_table = $table_prefix . 'shop_purchases';
		$this->points_ledger_table = $table_prefix . 'points_ledger';

		add_form_key('ucp_shop');

		$action = $request->variable('action', 'list');

		if ($action === 'purchase')
		{
			$this->purchase($request->variable('item_id', 0));
			return;
		}

		$this->list_shop();
	}

	private function list_shop()
	{
		global $db, $user, $template;

		$my_user_id = (int) $user->data['user_id'];
		$balance = gem_get_balance($my_user_id);

		$sql = 'SELECT * FROM ' . $this->shop_items_table . ' WHERE active = 1 ORDER BY sort_order ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$already_owned = false;
			if (!$row['repeatable'])
			{
				$sql2 = 'SELECT purchase_id FROM ' . $this->shop_purchases_table . '
						WHERE user_id = ' . $my_user_id . ' AND item_id = ' . (int) $row['item_id'];
				$result2 = $db->sql_query($sql2);
				$already_owned = (bool) $db->sql_fetchrow($result2);
				$db->sql_freeresult($result2);
			}

			$template->assign_block_vars('shop_items', array(
				'ITEM_ID'       => $row['item_id'],
				'NAME'          => $row['name'],
				'DESCRIPTION'   => $row['description'],
				'COST'          => $row['cost'],
				'S_CAN_AFFORD'  => ($balance >= $row['cost']),
				'S_ALREADY_OWNED' => $already_owned,
				'U_PURCHASE'    => $this->u_action . '&amp;action=purchase&amp;item_id=' . $row['item_id'],
			));
		}
		$db->sql_freeresult($result);

		$sql = 'SELECT l.*, changer.username AS changed_by_username FROM ' . $this->points_ledger_table . ' l
				LEFT JOIN ' . USERS_TABLE . ' changer ON l.changed_by = changer.user_id
				WHERE l.user_id = ' . $my_user_id . '
				ORDER BY l.created_at DESC';
		$result = $db->sql_query_limit($sql, 20);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('ledger', array(
				'AMOUNT' => (int) $row['amount'],
				'REASON' => $row['reason'],
				'TIME'   => $user->format_date($row['created_at']),
			));
		}
		$db->sql_freeresult($result);

		$template->assign_vars(array(
			'BALANCE' => $balance,
		));
	}

	private function purchase($item_id)
	{
		global $db, $user, $request;

		if (!check_form_key('ucp_shop'))
		{
			trigger_error('FORM_INVALID', E_USER_WARNING);
		}

		$sql = 'SELECT * FROM ' . $this->shop_items_table . ' WHERE item_id = ' . (int) $item_id . ' AND active = 1';
		$result = $db->sql_query($sql);
		$item = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$item)
		{
			trigger_error('GEM_SHOP_ITEM_NOT_FOUND', E_USER_WARNING);
		}

		$my_user_id = (int) $user->data['user_id'];

		if (!$item['repeatable'])
		{
			$sql = 'SELECT purchase_id FROM ' . $this->shop_purchases_table . '
					WHERE user_id = ' . $my_user_id . ' AND item_id = ' . (int) $item_id;
			$result = $db->sql_query($sql);
			$owned = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if ($owned)
			{
				trigger_error($user->lang('GEM_ALREADY_OWNED') . adm_back_link($this->u_action), E_USER_WARNING);
			}
		}

		$balance = gem_get_balance($my_user_id);
		if ($balance < $item['cost'])
		{
			trigger_error($user->lang('GEM_INSUFFICIENT_BALANCE') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$sql = 'INSERT INTO ' . $this->shop_purchases_table . ' ' . $db->sql_build_array('INSERT', array(
			'user_id'    => $my_user_id,
			'item_id'    => (int) $item_id,
			'created_at' => time(),
		));
		$db->sql_query($sql);
		$purchase_id = (int) $db->sql_nextid();

		if ($item['cost'] > 0)
		{
			gem_points_transaction($my_user_id, -abs((int) $item['cost']), 'Purchased: ' . $item['name'], 'spend', $purchase_id, 0);
		}

		trigger_error($user->lang('GEM_PURCHASE_SUCCESSFUL') . adm_back_link($this->u_action));
	}
}
