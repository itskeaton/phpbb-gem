<?php
/**
 * Gem - Shop UCP language file (en)
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'UCP_CAT_GEM'     => 'Gem',
	'UCP_SHOP'        => 'Shop',
	'UCP_SHOP_BROWSE' => 'Shop',

	'GEM_YOUR_BALANCE'  => 'Your balance:',
	'GEM_POINTS'        => 'points',
	'GEM_PURCHASE'      => 'Purchase',
	'GEM_OWNED'         => 'Owned',
	'GEM_CANT_AFFORD'   => 'Not enough points',
	'GEM_ALREADY_OWNED' => 'You already own this item.',
	'GEM_INSUFFICIENT_BALANCE' => 'You don\'t have enough points for this.',
	'GEM_PURCHASE_SUCCESSFUL' => 'Purchase complete!',
	'GEM_SHOP_ITEM_NOT_FOUND'  => 'That item could not be found.',
	'GEM_RECENT_ACTIVITY' => 'Recent Activity',
	'GEM_AMOUNT'  => 'Amount',
	'GEM_REASON'  => 'Reason',
	'GEM_WHEN'    => 'When',
));
