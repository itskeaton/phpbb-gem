<?php
/**
 * Gem - Ticketing System UCP controller
 *
 * Single mode ('tickets'), action-routed, same pattern as ucp_characters.php.
 *
 * Character Application tickets: creating one is NOT done through the
 * generic "new ticket" flow here - it happens automatically from
 * ucp_characters.php's character creation, when the "require approval"
 * setting is on. This controller only needs to READ Character Application
 * tickets (for the player's own ticket list/view), not create them -
 * that's intentional, not a gap. See ucp_characters.php for that side.
 *
 * KNOWN LIMITATION: no notification hook (phpBB's notification system
 * integration) - flagged as deferred in the original spec, still deferred
 * here. New tickets/replies don't currently notify anyone; players/staff
 * have to check back manually.
 */

require_once(__DIR__ . '/../gem/points_helper.php');

class ucp_tickets
{
	var $u_action;

	private $categories_table;
	private $category_fields_table;
	private $tickets_table;
	private $replies_table;
	private $fields_table;
	private $values_table;
	private $characters_table;
	private $status_log_table;

	const STATUS_OPEN        = 1;
	const STATUS_IN_PROGRESS = 2;
	const STATUS_RESOLVED    = 3;

	// keep in sync with ucp_characters.php's character status enum
	const CHAR_STATUS_ACTIVE   = 1;
	const CHAR_STATUS_PENDING  = 4;
	const CHAR_STATUS_DECLINED = 5;

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

		$user->add_lang('ucp/tickets');
		$this->tpl_name = 'ucp_tickets';
		$this->page_title = 'UCP_TICKETS_MANAGE';

		$this->categories_table      = $table_prefix . 'ticket_categories';
		$this->category_fields_table = $table_prefix . 'ticket_category_fields';
		$this->tickets_table         = $table_prefix . 'tickets';
		$this->replies_table         = $table_prefix . 'ticket_replies';
		$this->fields_table          = $table_prefix . 'profile_fields';
		$this->values_table          = $table_prefix . 'profile_values';
		$this->characters_table      = $table_prefix . 'characters';
		$this->status_log_table      = $table_prefix . 'character_status_log';

		add_form_key('ucp_tickets');

		$action = $request->variable('action', 'list');
		$ticket_id = $request->variable('ticket_id', 0);

		switch ($action)
		{
			case 'new':
				$this->category_picker();
				return;

			case 'submit':
				$this->submission_form($request->variable('category_id', 0));
				return;

			case 'create':
				$this->create_ticket($request->variable('category_id', 0));
				return;

			case 'view':
				$this->view_ticket($ticket_id);
				return;

			case 'reply':
				$this->post_reply($ticket_id);
				return;
		}

		$this->list_tickets();
	}

	// -------------------------------------------------------------------
	// Category eligibility
	// -------------------------------------------------------------------

	private function eligible_categories($user_id)
	{
		global $db;

		$sql = 'SELECT * FROM ' . $this->categories_table . ' ORDER BY sort_order ASC';
		$result = $db->sql_query($sql);
		$categories = array();
		while ($row = $db->sql_fetchrow($result))
		{
			if ($row['required_group'] && !$this->is_in_group($user_id, (int) $row['required_group']))
			{
				continue;
			}
			$categories[] = $row;
		}
		$db->sql_freeresult($result);

		return $categories;
	}

	private function is_in_group($user_id, $group_id)
	{
		global $db;

		$sql = 'SELECT user_id FROM ' . USER_GROUP_TABLE . '
				WHERE user_id = ' . (int) $user_id . '
				AND group_id = ' . (int) $group_id . '
				AND user_pending = 0';
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		return (bool) $row;
	}

	// -------------------------------------------------------------------
	// List / category picker
	// -------------------------------------------------------------------

	private function list_tickets()
	{
		global $db, $user, $template;

		$my_user_id = (int) $user->data['user_id'];

		$sql = 'SELECT t.*, c.category_name FROM ' . $this->tickets_table . ' t
				LEFT JOIN ' . $this->categories_table . ' c ON t.category_id = c.category_id
				WHERE t.user_id = ' . $my_user_id . '
				ORDER BY t.updated_at DESC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('tickets', array(
				'TICKET_ID'     => $row['ticket_id'],
				'CATEGORY_NAME' => $row['category_name'],
				'STATUS_LABEL'  => $this->status_label($row),
				'UPDATED'       => $user->format_date($row['updated_at']),
				'U_VIEW'        => $this->u_action . '&amp;action=view&amp;ticket_id=' . $row['ticket_id'],
			));
		}
		$db->sql_freeresult($result);

		$template->assign_vars(array(
			'U_NEW_TICKET' => $this->u_action . '&amp;action=new',
		));
	}

	private function status_label($ticket)
	{
		global $user;

		if ($ticket['status'] == self::STATUS_RESOLVED)
		{
			switch ($ticket['resolution'])
			{
				case 'approved': return $user->lang('GEM_TICKET_APPROVED');
				case 'declined': return $user->lang('GEM_TICKET_DECLINED');
				default:         return $user->lang('GEM_TICKET_CLOSED');
			}
		}
		return ($ticket['status'] == self::STATUS_IN_PROGRESS) ? $user->lang('GEM_TICKET_IN_PROGRESS') : $user->lang('GEM_TICKET_OPEN');
	}

	private function category_picker()
	{
		global $user, $template;

		$categories = $this->eligible_categories((int) $user->data['user_id']);

		foreach ($categories as $category)
		{
			$template->assign_block_vars('categories', array(
				'CATEGORY_ID'   => $category['category_id'],
				'CATEGORY_NAME' => $category['category_name'],
				'U_SUBMIT'      => $this->u_action . '&amp;action=submit&amp;category_id=' . $category['category_id'],
			));
		}

		$template->assign_var('S_CATEGORY_PICKER', true);
	}

	// -------------------------------------------------------------------
	// Submission
	// -------------------------------------------------------------------

	private function submission_form($category_id)
	{
		global $db, $user, $template;

		$category = $this->get_eligible_category_or_die($category_id, (int) $user->data['user_id']);

		$template->assign_vars(array(
			'S_SUBMIT_FORM' => true,
			'CATEGORY_ID'   => $category_id,
			'CATEGORY_NAME' => $category['category_name'],
			'U_CREATE'      => $this->u_action . '&amp;action=create&amp;category_id=' . (int) $category_id,
		));

		$this->assign_category_fields($category_id);
	}

	private function get_eligible_category_or_die($category_id, $user_id)
	{
		global $db;

		if (!$category_id)
		{
			trigger_error('GEM_TICKET_CATEGORY_NOT_FOUND', E_USER_WARNING);
		}

		$sql = 'SELECT * FROM ' . $this->categories_table . ' WHERE category_id = ' . (int) $category_id;
		$result = $db->sql_query($sql);
		$category = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$category)
		{
			trigger_error('GEM_TICKET_CATEGORY_NOT_FOUND', E_USER_WARNING);
		}

		if ($category['required_group'] && !$this->is_in_group($user_id, (int) $category['required_group']))
		{
			trigger_error('GEM_TICKET_CATEGORY_NOT_PERMITTED', E_USER_WARNING);
		}

		return $category;
	}

	private function assign_category_fields($category_id, $existing_values = array())
	{
		global $db, $template;

		$sql = 'SELECT f.* FROM ' . $this->category_fields_table . ' cf
				JOIN ' . $this->fields_table . ' f ON cf.field_id = f.field_id
				WHERE cf.category_id = ' . (int) $category_id . '
				ORDER BY cf.sort_order ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$raw_value = isset($existing_values[$row['field_id']]) ? $existing_values[$row['field_id']] : '';

			$template->assign_block_vars('ticket_fields', array(
				'FIELD_ID'      => $row['field_id'],
				'LABEL'         => $row['label'],
				'REQUIRED'      => (bool) $row['required'],
				'S_TEXT'        => ($row['field_type'] === 'text'),
				'S_TEXTAREA'    => ($row['field_type'] === 'textarea'),
				'S_SELECT'      => ($row['field_type'] === 'select'),
				'S_DATE'        => ($row['field_type'] === 'date'),
				'S_URL'         => ($row['field_type'] === 'url'),
				'S_CHECKBOX'    => ($row['field_type'] === 'checkbox'),
				'VALUE'         => $raw_value,
				'CHECKED'       => ($row['field_type'] === 'checkbox' && $raw_value === '1'),
			));

			if ($row['field_type'] === 'select')
			{
				$choices = json_decode($row['field_options'], true);
				if (is_array($choices))
				{
					foreach ($choices as $choice)
					{
						$template->assign_block_vars('ticket_fields.choices', array(
							'VALUE'    => $choice,
							'LABEL'    => $choice,
							'SELECTED' => ($choice === $raw_value),
						));
					}
				}
			}
		}
		$db->sql_freeresult($result);
	}

	private function create_ticket($category_id)
	{
		global $db, $user, $request;

		if (!check_form_key('ucp_tickets'))
		{
			trigger_error('FORM_INVALID', E_USER_WARNING);
		}

		$my_user_id = (int) $user->data['user_id'];
		$category = $this->get_eligible_category_or_die($category_id, $my_user_id);

		// Character Application tickets are never created through this
		// generic path - see class doc comment.
		if ($category['is_character_application'])
		{
			trigger_error('GEM_USE_CHARACTER_CREATION', E_USER_WARNING);
		}

		$sql = 'INSERT INTO ' . $this->tickets_table . ' ' . $db->sql_build_array('INSERT', array(
			'category_id' => (int) $category_id,
			'user_id'     => $my_user_id,
			'status'      => self::STATUS_OPEN,
			'created_at'  => time(),
			'updated_at'  => time(),
		));
		$db->sql_query($sql);
		$ticket_id = (int) $db->sql_nextid();

		$this->save_ticket_field_values($ticket_id, $category_id);

		trigger_error($user->lang('GEM_TICKET_CREATED') . adm_back_link($this->u_action));
	}

	private function save_ticket_field_values($ticket_id, $category_id)
	{
		global $db, $request;

		$sql = 'SELECT f.* FROM ' . $this->category_fields_table . ' cf
				JOIN ' . $this->fields_table . ' f ON cf.field_id = f.field_id
				WHERE cf.category_id = ' . (int) $category_id;
		$result = $db->sql_query($sql);
		while ($field = $db->sql_fetchrow($result))
		{
			$post_key = 'field_' . $field['field_id'];
			$value = ($field['field_type'] === 'checkbox')
				? ($request->variable($post_key, 0) ? '1' : '0')
				: $request->variable($post_key, '', true);

			if ($value === '')
			{
				continue;
			}

			$sql2 = 'INSERT INTO ' . $this->values_table . ' ' . $db->sql_build_array('INSERT', array(
				'field_id'   => (int) $field['field_id'],
				'owner_type' => 3, // ticket
				'owner_id'   => (int) $ticket_id,
				'value'      => $value,
			));
			$db->sql_query($sql2);
		}
		$db->sql_freeresult($result);
	}

	// -------------------------------------------------------------------
	// View / reply
	// -------------------------------------------------------------------

	private function view_ticket($ticket_id)
	{
		global $db, $user, $template;

		$ticket = $this->owned_ticket_or_die($ticket_id);

		$template->assign_vars(array(
			'S_VIEW_TICKET'  => true,
			'TICKET_ID'      => $ticket_id,
			'CATEGORY_NAME'  => $ticket['category_name'],
			'STATUS_LABEL'   => $this->status_label($ticket),
			'S_RESOLVED'     => ($ticket['status'] == self::STATUS_RESOLVED),
			'U_REPLY'        => $this->u_action . '&amp;action=reply&amp;ticket_id=' . (int) $ticket_id,
		));

		// Submitted field values, read-only display
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

		// Reply thread
		$sql = 'SELECT r.*, u.username FROM ' . $this->replies_table . ' r
				LEFT JOIN ' . USERS_TABLE . ' u ON r.user_id = u.user_id
				WHERE r.ticket_id = ' . (int) $ticket_id . '
				ORDER BY r.created_at ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('replies', array(
				'USERNAME'  => $row['username'],
				'MESSAGE'   => nl2br(htmlspecialchars($row['message'])),
				'TIME'      => $user->format_date($row['created_at']),
				'S_STAFF'   => (bool) $row['is_staff'],
			));
		}
		$db->sql_freeresult($result);
	}

	private function owned_ticket_or_die($ticket_id)
	{
		global $db, $user;

		$sql = 'SELECT t.*, c.category_name FROM ' . $this->tickets_table . ' t
				LEFT JOIN ' . $this->categories_table . ' c ON t.category_id = c.category_id
				WHERE t.ticket_id = ' . (int) $ticket_id . '
				AND t.user_id = ' . (int) $user->data['user_id'];
		$result = $db->sql_query($sql);
		$ticket = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$ticket)
		{
			trigger_error('GEM_TICKET_NOT_FOUND', E_USER_WARNING);
		}

		return $ticket;
	}

	private function post_reply($ticket_id)
	{
		global $db, $user, $request;

		if (!check_form_key('ucp_tickets'))
		{
			trigger_error('FORM_INVALID', E_USER_WARNING);
		}

		$ticket = $this->owned_ticket_or_die($ticket_id);

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
			'is_staff'   => 0,
			'created_at' => time(),
		));
		$db->sql_query($sql);

		$sql = 'UPDATE ' . $this->tickets_table . ' SET updated_at = ' . time() . ' WHERE ticket_id = ' . (int) $ticket_id;
		$db->sql_query($sql);

		trigger_error($user->lang('GEM_REPLY_POSTED') . adm_back_link($this->u_action . '&amp;action=view&amp;ticket_id=' . (int) $ticket_id));
	}
}
