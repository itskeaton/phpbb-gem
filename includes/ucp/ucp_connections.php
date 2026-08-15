<?php
/**
 * Gem - Connections UCP controller
 *
 * Single mode ('connections'), action-routed. Directed/one-sided per spec:
 * a connection is owned by whichever character (and therefore player)
 * created it. There's no approval step and no requirement that the target
 * character's player reciprocate - an unreciprocated connection is a valid,
 * meaningful end state, not an incomplete one.
 */

class ucp_connections
{
	var $u_action;

	private $characters_table;
	private $categories_table;
	private $connections_table;

	const CHAR_STATUS_ACTIVE = 1;

	function main($id, $mode)
	{
		global $db, $user, $template, $request, $table_prefix;

		$user->add_lang('ucp/connections');
		$this->tpl_name = 'ucp_connections';
		$this->page_title = 'UCP_CONNECTIONS_MANAGE';

		$this->characters_table  = $table_prefix . 'characters';
		$this->categories_table  = $table_prefix . 'connection_categories';
		$this->connections_table = $table_prefix . 'connections';

		add_form_key('ucp_connections');

		$action = $request->variable('action', 'list');
		$character_id = $request->variable('character_id', 0);
		$connection_id = $request->variable('connection_id', 0);

		switch ($action)
		{
			case 'add':
				$this->add_form($character_id);
				return;

			case 'save':
				$this->save_connection($character_id);
				return;

			case 'delete':
				$this->delete_connection($connection_id);
				return;
		}

		$this->list_connections();
	}

	// -------------------------------------------------------------------
	// List
	// -------------------------------------------------------------------

	private function list_connections()
	{
		global $db, $user, $template;

		$my_user_id = (int) $user->data['user_id'];

		$sql = 'SELECT character_id, character_name FROM ' . $this->characters_table . '
				WHERE user_id = ' . $my_user_id . ' AND status = ' . self::CHAR_STATUS_ACTIVE . '
				ORDER BY character_name ASC';
		$result = $db->sql_query($sql);
		while ($character = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('characters', array(
				'CHARACTER_ID'   => $character['character_id'],
				'CHARACTER_NAME' => $character['character_name'],
				'U_ADD'          => $this->u_action . '&amp;action=add&amp;character_id=' . $character['character_id'],
			));

			$sql2 = 'SELECT c.*, ch.character_name AS target_name, ch.avatar AS target_avatar, cat.category_name, cat.color
					FROM ' . $this->connections_table . ' c
					LEFT JOIN ' . $this->characters_table . ' ch ON c.connected_character_id = ch.character_id
					LEFT JOIN ' . $this->categories_table . ' cat ON c.category_id = cat.category_id
					WHERE c.character_id = ' . (int) $character['character_id'] . '
					ORDER BY c.created_at DESC';
			$result2 = $db->sql_query($sql2);
			while ($conn = $db->sql_fetchrow($result2))
			{
				$template->assign_block_vars('characters.connections', array(
					'CONNECTION_ID' => $conn['connection_id'],
					'TARGET_NAME'   => $conn['target_name'],
					'TARGET_AVATAR' => $conn['target_avatar'],
					'CATEGORY_NAME' => $conn['category_name'],
					'COLOR'         => $conn['color'],
					'DESCRIPTION'   => $conn['description'],
					'U_DELETE'      => $this->u_action . '&amp;action=delete&amp;connection_id=' . $conn['connection_id'],
					'U_TARGET_PROFILE' => append_sid("{$GLOBALS['phpbb_root_path']}character_roster.{$GLOBALS['phpEx']}", 'mode=profile&amp;character_id=' . $conn['connected_character_id']),
				));
			}
			$db->sql_freeresult($result2);
		}
		$db->sql_freeresult($result);
	}

	// -------------------------------------------------------------------
	// Add
	// -------------------------------------------------------------------

	private function owned_active_character_or_die($character_id, $user_id)
	{
		global $db;

		$sql = 'SELECT * FROM ' . $this->characters_table . '
				WHERE character_id = ' . (int) $character_id . '
				AND user_id = ' . (int) $user_id . '
				AND status = ' . self::CHAR_STATUS_ACTIVE;
		$result = $db->sql_query($sql);
		$character = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$character)
		{
			trigger_error('GEM_CHARACTER_NOT_FOUND', E_USER_WARNING);
		}

		return $character;
	}

	private function add_form($character_id)
	{
		global $db, $user, $template;

		$character = $this->owned_active_character_or_die($character_id, (int) $user->data['user_id']);

		$template->assign_vars(array(
			'S_ADD_CONNECTION' => true,
			'CHARACTER_ID'     => $character_id,
			'CHARACTER_NAME'   => $character['character_name'],
			'U_SAVE'           => $this->u_action . '&amp;action=save&amp;character_id=' . (int) $character_id,
		));

		$sql = 'SELECT category_id, category_name FROM ' . $this->categories_table . ' ORDER BY sort_order ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('category_options', array(
				'VALUE' => $row['category_id'],
				'LABEL' => $row['category_name'],
			));
		}
		$db->sql_freeresult($result);
	}

	private function save_connection($character_id)
	{
		global $db, $user, $request;

		if (!check_form_key('ucp_connections'))
		{
			trigger_error('FORM_INVALID', E_USER_WARNING);
		}

		$my_user_id = (int) $user->data['user_id'];
		$character = $this->owned_active_character_or_die($character_id, $my_user_id);

		$target_name = $request->variable('target_name', '', true);
		$category_id = $request->variable('category_id', 0);
		$description = $request->variable('description', '', true);

		if ($target_name === '')
		{
			trigger_error($user->lang('GEM_TARGET_NAME_REQUIRED') . adm_back_link($this->u_action . '&amp;action=add&amp;character_id=' . (int) $character_id), E_USER_WARNING);
		}

		$sql = 'SELECT character_id, character_name FROM ' . $this->characters_table . "
				WHERE character_name = '" . $db->sql_escape($target_name) . "'
				AND status = " . self::CHAR_STATUS_ACTIVE;
		$result = $db->sql_query($sql);
		$target = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$target)
		{
			trigger_error($user->lang('GEM_TARGET_CHARACTER_NOT_FOUND', $target_name) . adm_back_link($this->u_action . '&amp;action=add&amp;character_id=' . (int) $character_id), E_USER_WARNING);
		}

		if ((int) $target['character_id'] === (int) $character_id)
		{
			trigger_error($user->lang('GEM_CANNOT_CONNECT_SELF') . adm_back_link($this->u_action . '&amp;action=add&amp;character_id=' . (int) $character_id), E_USER_WARNING);
		}

		if (!$category_id)
		{
			trigger_error($user->lang('GEM_CATEGORY_REQUIRED') . adm_back_link($this->u_action . '&amp;action=add&amp;character_id=' . (int) $character_id), E_USER_WARNING);
		}

		$sql = 'INSERT INTO ' . $this->connections_table . ' ' . $db->sql_build_array('INSERT', array(
			'character_id'           => (int) $character_id,
			'connected_character_id' => (int) $target['character_id'],
			'category_id'            => (int) $category_id,
			'description'            => $description,
			'created_at'             => time(),
		));
		$db->sql_query($sql);

		trigger_error($user->lang('GEM_CONNECTION_ADDED') . adm_back_link($this->u_action));
	}

	// -------------------------------------------------------------------
	// Delete
	// -------------------------------------------------------------------

	private function delete_connection($connection_id)
	{
		global $db, $user;

		if (!$connection_id)
		{
			trigger_error('GEM_CONNECTION_NOT_FOUND', E_USER_WARNING);
		}

		// Ownership check joins through the owning character, not a direct
		// user_id column on connections - the connection belongs to whoever
		// owns character_id.
		$sql = 'SELECT c.connection_id FROM ' . $this->connections_table . ' c
				JOIN ' . $this->characters_table . ' ch ON c.character_id = ch.character_id
				WHERE c.connection_id = ' . (int) $connection_id . '
				AND ch.user_id = ' . (int) $user->data['user_id'];
		$result = $db->sql_query($sql);
		$owned = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$owned)
		{
			trigger_error('GEM_CONNECTION_NOT_FOUND', E_USER_WARNING);
		}

		$sql = 'DELETE FROM ' . $this->connections_table . ' WHERE connection_id = ' . (int) $connection_id;
		$db->sql_query($sql);

		trigger_error($user->lang('GEM_CONNECTION_DELETED') . adm_back_link($this->u_action));
	}
}
