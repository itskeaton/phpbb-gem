<?php
/**
 * Gem - Tickets MCP controller
 *
 * Single mode ('queue'), action-routed. "Claim" is lightweight per spec -
 * it's visibility ("who's on this"), not an access restriction. Any staff
 * member with access to this module can see, reply to, and resolve any
 * ticket regardless of who (if anyone) claimed it.
 *
 * Approve/decline only apply to tickets in a Character Application
 * category, and are the one place this controller reaches outside its own
 * tables - updating phpbb_characters.status and writing to
 * phpbb_character_status_log, exactly the mechanic the original spec
 * described: "one action, both records update."
 */

class mcp_tickets
{
	var $u_action;

	private $categories_table;
	private $tickets_table;
	private $replies_table;
	private $fields_table;
	private $values_table;
	private $characters_table;
	private $status_log_table;
	private $characters_active_table;

	const STATUS_OPEN        = 1;
	const STATUS_IN_PROGRESS = 2;
	const STATUS_RESOLVED    = 3;

	const CHAR_STATUS_ACTIVE   = 1;
	const CHAR_STATUS_PENDING  = 4;
	const CHAR_STATUS_DECLINED = 5;

	function main($id, $mode)
	{
		global $db, $user, $template, $request, $table_prefix;

		$user->add_lang('mcp/tickets');
		$this->tpl_name = 'mcp_tickets';
		$this->page_title = 'MCP_GEM_TICKETS_QUEUE';

		$this->categories_table = $table_prefix . 'ticket_categories';
		$this->tickets_table    = $table_prefix . 'tickets';
		$this->replies_table    = $table_prefix . 'ticket_replies';
		$this->fields_table     = $table_prefix . 'profile_fields';
		$this->values_table     = $table_prefix . 'profile_values';
		$this->characters_table = $table_prefix . 'characters';
		$this->status_log_table = $table_prefix . 'character_status_log';
		$this->characters_active_table = $table_prefix . 'characters_active';

		add_form_key('mcp_tickets');
		$template->assign_var('U_ACTION', $this->u_action);

		$action = $request->variable('action', '');
		$ticket_id = $request->variable('ticket_id', 0);

		switch ($action)
		{
			case 'view':
				$this->view_ticket($ticket_id);
				return;

			case 'claim':
				$this->claim_ticket($ticket_id);
				return;

			case 'reply':
				$this->post_reply($ticket_id);
				return;

			case 'close':
				$this->resolve_ticket($ticket_id, 'closed');
				return;

			case 'approve':
				$this->approve_application($ticket_id);
				return;

			case 'decline':
				$this->decline_application($ticket_id);
				return;
		}

		$this->show_queue();
	}

	// -------------------------------------------------------------------
	// Queue
	// -------------------------------------------------------------------

	private function show_queue()
	{
		global $db, $user, $template, $request;

		$category_filter = $request->variable('category_id', 0);

		$where = 'status IN (' . self::STATUS_OPEN . ', ' . self::STATUS_IN_PROGRESS . ')';
		if ($category_filter)
		{
			$where .= ' AND category_id = ' . (int) $category_filter;
		}

		$sql = 'SELECT t.*, c.category_name, c.is_character_application, u.username, claimer.username AS claimed_by_username
				FROM ' . $this->tickets_table . ' t
				LEFT JOIN ' . $this->categories_table . ' c ON t.category_id = c.category_id
				LEFT JOIN ' . USERS_TABLE . ' u ON t.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' claimer ON t.claimed_by = claimer.user_id
				WHERE ' . $where . '
				ORDER BY t.created_at ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('tickets', array(
				'TICKET_ID'     => $row['ticket_id'],
				'CATEGORY_NAME' => $row['category_name'],
				'SUBMITTER'     => $row['username'],
				'STATUS_LABEL'  => ($row['status'] == self::STATUS_IN_PROGRESS) ? $user->lang('GEM_TICKET_IN_PROGRESS') : $user->lang('GEM_TICKET_OPEN'),
				'CLAIMED_BY'    => $row['claimed_by_username'],
				'CREATED'       => $user->format_date($row['created_at']),
				'S_IS_APPLICATION' => (bool) $row['is_character_application'],
				'U_VIEW'        => $this->u_action . '&amp;action=view&amp;ticket_id=' . $row['ticket_id'],
			));
		}
		$db->sql_freeresult($result);

		$sql = 'SELECT category_id, category_name FROM ' . $this->categories_table . ' ORDER BY sort_order ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('category_filter', array(
				'VALUE'    => $row['category_id'],
				'LABEL'    => $row['category_name'],
				'SELECTED' => ($category_filter === (int) $row['category_id']),
			));
		}
		$db->sql_freeresult($result);

		$template->assign_var('U_QUEUE', $this->u_action);
	}

	// -------------------------------------------------------------------
	// View / claim / reply
	// -------------------------------------------------------------------

	private function get_ticket_or_die($ticket_id)
	{
		global $db;

		$sql = 'SELECT t.*, c.category_name, c.is_character_application
				FROM ' . $this->tickets_table . ' t
				LEFT JOIN ' . $this->categories_table . ' c ON t.category_id = c.category_id
				WHERE t.ticket_id = ' . (int) $ticket_id;
		$result = $db->sql_query($sql);
		$ticket = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$ticket)
		{
			trigger_error('GEM_TICKET_NOT_FOUND', E_USER_WARNING);
		}

		return $ticket;
	}

	private function view_ticket($ticket_id)
	{
		global $db, $user, $template;

		$ticket = $this->get_ticket_or_die($ticket_id);

		$sql = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $ticket['user_id'];
		$result = $db->sql_query($sql);
		$submitter = $db->sql_fetchfield('username');
		$db->sql_freeresult($result);

		$claimed_by_username = '';
		if ($ticket['claimed_by'])
		{
			$sql = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $ticket['claimed_by'];
			$result = $db->sql_query($sql);
			$claimed_by_username = $db->sql_fetchfield('username');
			$db->sql_freeresult($result);
		}

		$template->assign_vars(array(
			'S_VIEW_TICKET'      => true,
			'TICKET_ID'          => $ticket_id,
			'CATEGORY_NAME'      => $ticket['category_name'],
			'SUBMITTER'          => $submitter,
			'CLAIMED_BY'         => $claimed_by_username,
			'S_RESOLVED'         => ($ticket['status'] == self::STATUS_RESOLVED),
			'S_IS_APPLICATION'   => (bool) $ticket['is_character_application'],
			'U_CLAIM'            => $this->u_action . '&amp;action=claim&amp;ticket_id=' . (int) $ticket_id,
			'U_REPLY'            => $this->u_action . '&amp;action=reply&amp;ticket_id=' . (int) $ticket_id,
			'U_CLOSE'            => $this->u_action . '&amp;action=close&amp;ticket_id=' . (int) $ticket_id,
			'U_APPROVE'          => $this->u_action . '&amp;action=approve&amp;ticket_id=' . (int) $ticket_id,
			'U_DECLINE_SUBMIT'   => $this->u_action . '&amp;action=decline&amp;ticket_id=' . (int) $ticket_id,
		));

		if ($ticket['character_id'])
		{
			$sql = 'SELECT character_name FROM ' . $this->characters_table . ' WHERE character_id = ' . (int) $ticket['character_id'];
			$result = $db->sql_query($sql);
			$char_name = $db->sql_fetchfield('character_name');
			$db->sql_freeresult($result);

			$template->assign_vars(array(
				'S_HAS_CHARACTER'  => true,
				'LINKED_CHARACTER' => $char_name,
			));
		}

		$sql = 'SELECT f.label, v.value FROM ' . $this->values_table . ' v
				JOIN ' . $this->fields_table . ' f ON v.field_id = f.field_id
				WHERE v.owner_type = 3 AND v.owner_id = ' . (int) $ticket_id;
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('submitted_fields', array(
				'LABEL' => $row['label'],
				'VALUE' => $row['value'],
			));
		}
		$db->sql_freeresult($result);

		$sql = 'SELECT r.*, u.username FROM ' . $this->replies_table . ' r
				LEFT JOIN ' . USERS_TABLE . ' u ON r.user_id = u.user_id
				WHERE r.ticket_id = ' . (int) $ticket_id . '
				ORDER BY r.created_at ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('replies', array(
				'USERNAME' => $row['username'],
				'MESSAGE'  => nl2br(htmlspecialchars($row['message'])),
				'TIME'     => $user->format_date($row['created_at']),
				'S_STAFF'  => (bool) $row['is_staff'],
			));
		}
		$db->sql_freeresult($result);
	}

	private function claim_ticket($ticket_id)
	{
		global $db, $user;

		$this->get_ticket_or_die($ticket_id);

		$sql = 'UPDATE ' . $this->tickets_table . ' SET
					claimed_by = ' . (int) $user->data['user_id'] . ',
					status = ' . self::STATUS_IN_PROGRESS . ',
					updated_at = ' . time() . '
				WHERE ticket_id = ' . (int) $ticket_id;
		$db->sql_query($sql);

		trigger_error($user->lang('GEM_TICKET_CLAIMED') . adm_back_link($this->u_action . '&amp;action=view&amp;ticket_id=' . (int) $ticket_id));
	}

	private function post_reply($ticket_id)
	{
		global $db, $user, $request;

		if (!check_form_key('mcp_tickets'))
		{
			trigger_error('FORM_INVALID', E_USER_WARNING);
		}

		$ticket = $this->get_ticket_or_die($ticket_id);

		if ($ticket['status'] == self::STATUS_RESOLVED)
		{
			trigger_error('GEM_TICKET_ALREADY_RESOLVED', E_USER_WARNING);
		}

		$message = $request->variable('message', '', true);
		if ($message === '')
		{
			trigger_error($user->lang('GEM_REPLY_REQUIRED') . adm_back_link($this->u_action . '&amp;action=view&amp;ticket_id=' . (int) $ticket_id), E_USER_WARNING);
		}

		$sql = 'INSERT INTO ' . $this->replies_table . ' ' . $db->sql_build_array('INSERT', array(
			'ticket_id'  => (int) $ticket_id,
			'user_id'    => (int) $user->data['user_id'],
			'message'    => $message,
			'is_staff'   => 1,
			'created_at' => time(),
		));
		$db->sql_query($sql);

		$sql = 'UPDATE ' . $this->tickets_table . ' SET updated_at = ' . time() . ' WHERE ticket_id = ' . (int) $ticket_id;
		$db->sql_query($sql);

		trigger_error($user->lang('GEM_REPLY_POSTED') . adm_back_link($this->u_action . '&amp;action=view&amp;ticket_id=' . (int) $ticket_id));
	}

	// -------------------------------------------------------------------
	// Resolution
	// -------------------------------------------------------------------

	private function resolve_ticket($ticket_id, $resolution, $reason = '')
	{
		global $db, $user;

		$sql = 'UPDATE ' . $this->tickets_table . ' SET
					status = ' . self::STATUS_RESOLVED . ',
					resolution = \'' . $db->sql_escape($resolution) . '\',
					resolution_reason = \'' . $db->sql_escape($reason) . '\',
					updated_at = ' . time() . '
				WHERE ticket_id = ' . (int) $ticket_id;
		$db->sql_query($sql);
	}

	private function approve_application($ticket_id)
	{
		global $db, $user;

		$ticket = $this->get_ticket_or_die($ticket_id);

		if (!$ticket['is_character_application'] || !$ticket['character_id'])
		{
			trigger_error('GEM_NOT_AN_APPLICATION', E_USER_WARNING);
		}

		$sql = 'UPDATE ' . $this->characters_table . ' SET status = ' . self::CHAR_STATUS_ACTIVE . ', updated_at = ' . time() . '
				WHERE character_id = ' . (int) $ticket['character_id'];
		$db->sql_query($sql);

		$sql = 'INSERT INTO ' . $this->status_log_table . ' ' . $db->sql_build_array('INSERT', array(
			'character_id' => (int) $ticket['character_id'],
			'old_status'   => self::CHAR_STATUS_PENDING,
			'new_status'   => self::CHAR_STATUS_ACTIVE,
			'reason'       => '',
			'changed_by'   => (int) $user->data['user_id'],
			'changed_at'   => time(),
		));
		$db->sql_query($sql);

		// If this was the player's first/only character, make it their default.
		$sql = 'SELECT user_id FROM ' . $this->characters_active_table . ' WHERE user_id = ' . (int) $ticket['user_id'];
		$result = $db->sql_query($sql);
		$has_default = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$has_default)
		{
			$sql = 'INSERT INTO ' . $this->characters_active_table . ' ' . $db->sql_build_array('INSERT', array(
				'user_id'      => (int) $ticket['user_id'],
				'character_id' => (int) $ticket['character_id'],
				'updated_at'   => time(),
			));
			$db->sql_query($sql);
		}

		$this->resolve_ticket($ticket_id, 'approved');

		trigger_error($user->lang('GEM_APPLICATION_APPROVED') . adm_back_link($this->u_action));
	}

	private function decline_application($ticket_id)
	{
		global $db, $user, $request, $template;

		$ticket = $this->get_ticket_or_die($ticket_id);

		if (!$ticket['is_character_application'] || !$ticket['character_id'])
		{
			trigger_error('GEM_NOT_AN_APPLICATION', E_USER_WARNING);
		}

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('mcp_tickets'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			$reason = $request->variable('reason', '', true);
			if ($reason === '')
			{
				trigger_error($user->lang('GEM_DECLINE_REASON_REQUIRED') . adm_back_link($this->u_action . '&amp;action=view&amp;ticket_id=' . (int) $ticket_id), E_USER_WARNING);
			}

			$sql = 'UPDATE ' . $this->characters_table . ' SET status = ' . self::CHAR_STATUS_DECLINED . ', updated_at = ' . time() . '
					WHERE character_id = ' . (int) $ticket['character_id'];
			$db->sql_query($sql);

			// Staff-only visibility, per the original spec decision - this
			// reason lives in the character's status log and the ticket's
			// resolution_reason, never surfaced to the applicant automatically.
			$sql = 'INSERT INTO ' . $this->status_log_table . ' ' . $db->sql_build_array('INSERT', array(
				'character_id' => (int) $ticket['character_id'],
				'old_status'   => self::CHAR_STATUS_PENDING,
				'new_status'   => self::CHAR_STATUS_DECLINED,
				'reason'       => $reason,
				'changed_by'   => (int) $user->data['user_id'],
				'changed_at'   => time(),
			));
			$db->sql_query($sql);

			$this->resolve_ticket($ticket_id, 'declined', $reason);

			trigger_error($user->lang('GEM_APPLICATION_DECLINED') . adm_back_link($this->u_action));
		}

		// show the reason prompt
		$template->assign_vars(array(
			'S_DECLINE_PROMPT' => true,
			'TICKET_ID'        => $ticket_id,
		));
	}
}
