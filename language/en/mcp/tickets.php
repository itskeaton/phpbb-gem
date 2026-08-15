<?php
/**
 * Gem - Tickets MCP language file (en)
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
	'MCP_GEM'                => 'Gem',
	'MCP_GEM_TICKETS'        => 'Tickets',
	'MCP_GEM_TICKETS_QUEUE'  => 'Ticket Queue',

	'GEM_ALL_CATEGORIES'     => 'All categories',
	'GEM_CATEGORY_NAME'      => 'Category',
	'GEM_SUBMITTER'          => 'Submitted by',
	'GEM_STATUS'             => 'Status',
	'GEM_CLAIMED_BY'         => 'Claimed by',
	'GEM_CREATED'            => 'Created',
	'GEM_APPLICATION'        => 'Application',
	'GEM_LINKED_CHARACTER'   => 'Character',

	'GEM_TICKET_OPEN'        => 'Open',
	'GEM_TICKET_IN_PROGRESS' => 'In progress',

	'GEM_CLAIM'              => 'Claim',
	'GEM_TICKET_CLAIMED'     => 'Ticket claimed.',
	'GEM_APPROVE'            => 'Approve',
	'GEM_DECLINE'            => 'Decline',
	'GEM_CLOSE_TICKET'       => 'Close ticket',
	'GEM_DECLINE_REASON'     => 'Decline reason',
	'GEM_DECLINE_REASON_HINT' => 'Staff-only - not shown to the applicant automatically.',
	'GEM_DECLINE_REASON_REQUIRED' => 'A reason is required to decline an application.',
	'GEM_CONFIRM_DECLINE'    => 'Decline application',
	'GEM_APPLICATION_APPROVED' => 'Application approved - the character is now active.',
	'GEM_APPLICATION_DECLINED' => 'Application declined.',
	'GEM_NOT_AN_APPLICATION' => 'This ticket isn\'t a Character Application, so it can\'t be approved or declined.',

	'GEM_REPLY'              => 'Reply',
	'GEM_POST_REPLY'         => 'Post reply',
	'GEM_REPLY_REQUIRED'     => 'A reply message is required.',
	'GEM_REPLY_POSTED'       => 'Reply posted.',
	'GEM_TICKET_ALREADY_RESOLVED' => 'This ticket is already resolved.',
	'GEM_TICKET_NOT_FOUND'   => 'That ticket could not be found.',
));
