<?php
/**
 * Gem - Ticketing System UCP language file (en)
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
	'UCP_CAT_GEM'          => 'Gem',
	'UCP_TICKETS'          => 'Tickets',
	'UCP_TICKETS_MANAGE'   => 'My Tickets',

	'GEM_NEW_TICKET'       => 'New ticket',
	'GEM_PICK_CATEGORY'    => 'What can we help with?',
	'GEM_CATEGORY_NAME'    => 'Category',
	'GEM_STATUS'           => 'Status',
	'GEM_UPDATED'          => 'Last updated',

	'GEM_TICKET_OPEN'        => 'Open',
	'GEM_TICKET_IN_PROGRESS' => 'In progress',
	'GEM_TICKET_CLOSED'      => 'Closed',
	'GEM_TICKET_APPROVED'    => 'Approved',
	'GEM_TICKET_DECLINED'    => 'Declined',

	'GEM_TICKET_CREATED'   => 'Ticket submitted.',
	'GEM_TICKET_NOT_FOUND' => 'That ticket could not be found.',
	'GEM_TICKET_ALREADY_RESOLVED' => 'This ticket is already resolved and can\'t accept new replies.',
	'GEM_TICKET_CATEGORY_NOT_FOUND' => 'That category could not be found.',
	'GEM_TICKET_CATEGORY_NOT_PERMITTED' => 'You don\'t have permission to submit to that category.',
	'GEM_USE_CHARACTER_CREATION' => 'Character Application tickets are created automatically when you create a character - you can\'t submit one directly.',

	'GEM_REPLY'         => 'Reply',
	'GEM_POST_REPLY'    => 'Post reply',
	'GEM_REPLY_REQUIRED' => 'A reply message is required.',
	'GEM_REPLY_POSTED'  => 'Reply posted.',
));
