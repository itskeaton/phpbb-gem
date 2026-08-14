<?php
/**
 * Gem - Character Management UCP controller
 *
 * Single mode ('manage'), internally routed by an 'action' request var -
 * same pattern as the ACP profile fields controller.
 *
 * KNOWN SIMPLIFICATIONS (flagged rather than silently done):
 *   - Avatar is a plain remote-image-URL field, not phpBB's full avatar
 *     driver framework (upload/gravatar/remote picker). Stored as
 *     avatar_type = 'avatar.driver.remote' so it should render correctly
 *     through phpBB's existing avatar pipeline, but there's no upload UI.
 *   - Dynamic fields of type 'image' and 'songlist' render as a disabled
 *     placeholder ("coming soon") rather than functioning - the gallery
 *     storage and song-embed subsystems haven't been built yet. Nothing
 *     here silently pretends they work.
 *   - Staff-only actions (unarchive when self-serve is off) are gated on
 *     $auth->acl_get('a_') as a stand-in for a real "staff" permission.
 *     Replace with a dedicated ACL permission before relying on this for
 *     anything beyond testing.
 */

class ucp_characters
{
	var $u_action;

	private $characters_table;
	private $characters_active_table;
	private $status_log_table;
	private $fields_table;
	private $values_table;
	private $sections_table;

	// keep in sync with the enum documented in add_player_character_split.php
	const STATUS_ACTIVE      = 1;
	const STATUS_ARCHIVED    = 2;
	const STATUS_DEACTIVATED = 3;
	const STATUS_PENDING     = 4;
	const STATUS_DECLINED    = 5;

	function main($id, $mode)
	{
		global $db, $user, $template, $request, $config, $table_prefix;

		$user->add_lang('ucp/characters');
		$this->tpl_name = 'ucp_characters';
		$this->page_title = 'UCP_CHARACTERS_MANAGE';

		$this->characters_table        = $table_prefix . 'characters';
		$this->characters_active_table = $table_prefix . 'characters_active';
		$this->status_log_table        = $table_prefix . 'character_status_log';
		$this->fields_table            = $table_prefix . 'profile_fields';
		$this->values_table            = $table_prefix . 'profile_values';
		$this->sections_table          = $table_prefix . 'profile_sections';

		add_form_key('ucp_characters');

		$action = $request->variable('action', 'list');
		$character_id = $request->variable('character_id', 0);

		switch ($action)
		{
			case 'add':
			case 'edit':
				$this->character_form($action, $character_id);
				return;

			case 'save':
				$this->character_save($character_id);
				return;

			case 'archive':
				$this->character_archive($character_id);
				return;

			case 'unarchive':
				$this->character_unarchive($character_id);
				return;
		}

		$this->list_characters();
	}

	// -------------------------------------------------------------------
	// List
	// -------------------------------------------------------------------

	private function list_characters()
	{
		global $db, $user, $template, $config;

		$my_user_id = (int) $user->data['user_id'];

		$sql = 'SELECT * FROM ' . $this->characters_table . '
				WHERE user_id = ' . $my_user_id . '
				ORDER BY (status = ' . self::STATUS_ACTIVE . ') DESC, character_name ASC';
		$result = $db->sql_query($sql);
		$has_characters = false;
		while ($row = $db->sql_fetchrow($result))
		{
			$has_characters = true;
			$template->assign_block_vars('characters', array(
				'CHARACTER_ID'   => $row['character_id'],
				'CHARACTER_NAME' => $row['character_name'],
				'STATUS'         => (int) $row['status'],
				'STATUS_LABEL'   => $this->status_label($row['status']),
				'S_ACTIVE'       => ($row['status'] == self::STATUS_ACTIVE),
				'S_ARCHIVED'     => ($row['status'] == self::STATUS_ARCHIVED),
				'S_PENDING'      => ($row['status'] == self::STATUS_PENDING),
				'S_DECLINED'     => ($row['status'] == self::STATUS_DECLINED),
				'S_DEACTIVATED'  => ($row['status'] == self::STATUS_DEACTIVATED),
				'U_EDIT'         => $this->u_action . "&amp;action=edit&amp;character_id={$row['character_id']}",
				'U_ARCHIVE'      => $this->u_action . "&amp;action=archive&amp;character_id={$row['character_id']}",
				'U_UNARCHIVE'    => $this->u_action . "&amp;action=unarchive&amp;character_id={$row['character_id']}",
			));
		}
		$db->sql_freeresult($result);

		$active_or_pending = $this->count_active_or_pending($my_user_id);
		$max = (int) $config['gem_max_characters'];
		$cap_reached = ($max > 0 && $active_or_pending >= $max);

		$template->assign_vars(array(
			'S_CAP_REACHED'   => $cap_reached,
			'S_HAS_CHARACTERS' => $has_characters,
			'CHARACTER_COUNT' => $active_or_pending,
			'CHARACTER_MAX'   => $max,
			'U_ADD_CHARACTER' => $this->u_action . '&amp;action=add',
			'S_SELF_UNARCHIVE' => (bool) $config['gem_self_unarchive'],
		));
	}

	private function status_label($status)
	{
		global $user;

		switch ((int) $status)
		{
			case self::STATUS_ACTIVE:      return $user->lang('GEM_STATUS_ACTIVE');
			case self::STATUS_ARCHIVED:    return $user->lang('GEM_STATUS_ARCHIVED');
			case self::STATUS_DEACTIVATED: return $user->lang('GEM_STATUS_DEACTIVATED');
			case self::STATUS_PENDING:     return $user->lang('GEM_STATUS_PENDING');
			case self::STATUS_DECLINED:    return $user->lang('GEM_STATUS_DECLINED');
			default:                       return '';
		}
	}

	private function count_active_or_pending($user_id)
	{
		global $db;

		$sql = 'SELECT COUNT(*) AS cnt FROM ' . $this->characters_table . '
				WHERE user_id = ' . (int) $user_id . '
				AND status IN (' . self::STATUS_ACTIVE . ', ' . self::STATUS_PENDING . ')';
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		return (int) $row['cnt'];
	}

	// -------------------------------------------------------------------
	// Create / edit
	// -------------------------------------------------------------------

	private function character_form($action, $character_id)
	{
		global $db, $user, $template, $config;

		$my_user_id = (int) $user->data['user_id'];
		$character = array(
			'character_name' => '',
			'avatar'         => '',
			'avatar_width'   => 120,
			'avatar_height'  => 120,
			'signature'      => '',
		);

		if ($action == 'edit' && $character_id)
		{
			$sql = 'SELECT * FROM ' . $this->characters_table . '
					WHERE character_id = ' . (int) $character_id . '
					AND user_id = ' . $my_user_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$row)
			{
				trigger_error('GEM_CHARACTER_NOT_FOUND', E_USER_WARNING);
			}

			$character = $row;
		}
		else
		{
			// Creating new - enforce the cap here too (not just hiding the button),
			// since the button being hidden client-side is not a real guard.
			$active_or_pending = $this->count_active_or_pending($my_user_id);
			$max = (int) $config['gem_max_characters'];
			if ($max > 0 && $active_or_pending >= $max)
			{
				trigger_error($user->lang('GEM_CHARACTER_CAP_REACHED') . adm_back_link($this->u_action), E_USER_WARNING);
			}
		}

		$template->assign_vars(array(
			'S_EDIT_CHARACTER' => true,
			'S_IS_NEW'         => ($action == 'add'),
			'CHARACTER_ID'     => $character_id,
			'CHARACTER_NAME'   => $character['character_name'],
			'AVATAR_URL'       => $character['avatar'],
			'AVATAR_WIDTH'     => $character['avatar_width'],
			'AVATAR_HEIGHT'    => $character['avatar_height'],
			'SIGNATURE'        => $character['signature'],
			'U_SAVE'           => $this->u_action . '&amp;action=save&amp;character_id=' . (int) $character_id,
		));

		$this->assign_custom_fields($character_id ?: 0);
	}

	/**
	 * Renders every profile field with applies_to IN (character, both) into
	 * the 'custom_fields' template loop, pre-filled with existing values if
	 * this is an edit.
	 */
	private function assign_custom_fields($character_id)
	{
		global $db, $template, $user;

		$existing_values = array();
		if ($character_id)
		{
			$sql = 'SELECT field_id, value FROM ' . $this->values_table . '
					WHERE owner_type = 2 AND owner_id = ' . (int) $character_id;
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$existing_values[$row['field_id']] = $row['value'];
			}
			$db->sql_freeresult($result);
		}

		$sql = 'SELECT f.*, s.section_name FROM ' . $this->fields_table . ' f
				LEFT JOIN ' . $this->sections_table . ' s ON f.section_id = s.section_id
				WHERE f.applies_to IN (2, 3)
				ORDER BY (f.section_id = 0) ASC, s.sort_order ASC, f.sort_order ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$raw_value = isset($existing_values[$row['field_id']]) ? $existing_values[$row['field_id']] : '';
			$is_unsupported = in_array($row['field_type'], array('image', 'songlist'), true);

			$block = array(
				'FIELD_ID'      => $row['field_id'],
				'FIELD_KEY'     => $row['field_key'],
				'LABEL'         => $row['label'],
				'FIELD_TYPE'    => $row['field_type'],
				'SECTION_NAME'  => $row['section_name'],
				'REQUIRED'      => (bool) $row['required'],
				'S_UNSUPPORTED' => $is_unsupported,
				'S_TEXT'        => ($row['field_type'] === 'text'),
				'S_TEXTAREA'    => ($row['field_type'] === 'textarea'),
				'S_SELECT'      => ($row['field_type'] === 'select'),
				'S_MULTISELECT' => ($row['field_type'] === 'multiselect'),
				'S_DATE'        => ($row['field_type'] === 'date'),
				'S_URL'         => ($row['field_type'] === 'url'),
				'S_CHECKBOX'    => ($row['field_type'] === 'checkbox'),
				'VALUE'         => ($row['field_type'] !== 'multiselect') ? $raw_value : '',
				'CHECKED'       => ($row['field_type'] === 'checkbox' && $raw_value === '1'),
			);

			$template->assign_block_vars('custom_fields', $block);

			if ($row['field_type'] === 'select' || $row['field_type'] === 'multiselect')
			{
				$choices = json_decode($row['field_options'], true);
				$selected = ($row['field_type'] === 'multiselect') ? (array) json_decode($raw_value, true) : array($raw_value);

				if (is_array($choices))
				{
					foreach ($choices as $choice)
					{
						$template->assign_block_vars('custom_fields.choices', array(
							'VALUE'    => $choice,
							'LABEL'    => $choice,
							'SELECTED' => in_array($choice, $selected, true),
						));
					}
				}
			}
		}
		$db->sql_freeresult($result);
	}

	private function character_save($character_id)
	{
		global $db, $user, $request, $config;

		if (!check_form_key('ucp_characters'))
		{
			trigger_error('FORM_INVALID', E_USER_WARNING);
		}

		$my_user_id = (int) $user->data['user_id'];
		$is_new = !$character_id;

		if (!$is_new)
		{
			// ownership check
			$sql = 'SELECT character_id FROM ' . $this->characters_table . '
					WHERE character_id = ' . (int) $character_id . ' AND user_id = ' . $my_user_id;
			$result = $db->sql_query($sql);
			$owned = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$owned)
			{
				trigger_error('GEM_CHARACTER_NOT_FOUND', E_USER_WARNING);
			}
		}
		else
		{
			$active_or_pending = $this->count_active_or_pending($my_user_id);
			$max = (int) $config['gem_max_characters'];
			if ($max > 0 && $active_or_pending >= $max)
			{
				trigger_error($user->lang('GEM_CHARACTER_CAP_REACHED') . adm_back_link($this->u_action), E_USER_WARNING);
			}
		}

		$character_name = $request->variable('character_name', '', true);
		$avatar_url     = $request->variable('avatar_url', '', true);
		$avatar_width   = $request->variable('avatar_width', 120);
		$avatar_height  = $request->variable('avatar_height', 120);
		$signature_raw  = $request->variable('signature', '', true);

		if ($character_name === '')
		{
			trigger_error($user->lang('GEM_CHARACTER_NAME_REQUIRED') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		// Parse signature through phpBB's normal BBCode storage pipeline
		$signature_uid = $signature_bitfield = '';
		$signature_options = OPTION_FLAG_BBCODE | OPTION_FLAG_SMILIES | OPTION_FLAG_LINKS;
		generate_text_for_storage($signature_raw, $signature_uid, $signature_bitfield, $signature_options, true, true, true);

		$sql_ary = array(
			'character_name'             => $character_name,
			'avatar'                     => $avatar_url,
			'avatar_type'                => $avatar_url ? 'avatar.driver.remote' : '',
			'avatar_width'               => (int) $avatar_width,
			'avatar_height'              => (int) $avatar_height,
			'signature'                  => $signature_raw,
			'signature_bbcode_uid'       => $signature_uid,
			'signature_bbcode_bitfield'  => $signature_bitfield,
			'updated_at'                 => time(),
		);

		if ($is_new)
		{
			$require_approval = (bool) $config['gem_require_approval'];
			$sql_ary['user_id']          = $my_user_id;
			$sql_ary['character_colour'] = $user->data['user_colour'];
			$sql_ary['status']           = $require_approval ? self::STATUS_PENDING : self::STATUS_ACTIVE;
			$sql_ary['created_at']       = time();

			$sql = 'INSERT INTO ' . $this->characters_table . ' ' . $db->sql_build_array('INSERT', $sql_ary);
			$db->sql_query($sql);
			$character_id = (int) $db->sql_nextid();

			$this->log_status_change($character_id, 0, $sql_ary['status'], null, $my_user_id);

			// If this is the player's first character, make it their default automatically.
			$sql = 'SELECT user_id FROM ' . $this->characters_active_table . ' WHERE user_id = ' . $my_user_id;
			$result = $db->sql_query($sql);
			$has_default = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$has_default && $sql_ary['status'] == self::STATUS_ACTIVE)
			{
				$sql = 'INSERT INTO ' . $this->characters_active_table . ' ' . $db->sql_build_array('INSERT', array(
					'user_id'      => $my_user_id,
					'character_id' => $character_id,
					'updated_at'   => time(),
				));
				$db->sql_query($sql);
			}
		}
		else
		{
			$sql = 'UPDATE ' . $this->characters_table . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
					WHERE character_id = ' . (int) $character_id;
			$db->sql_query($sql);
		}

		$this->save_custom_fields($character_id, $is_new);

		trigger_error($user->lang($is_new ? 'GEM_CHARACTER_CREATED' : 'GEM_CHARACTER_SAVED') . adm_back_link($this->u_action));
	}

	private function save_custom_fields($character_id, $is_new)
	{
		global $db, $user, $request;

		$sql = 'SELECT * FROM ' . $this->fields_table . ' WHERE applies_to IN (2, 3)';
		$result = $db->sql_query($sql);
		$fields = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$fields[] = $row;
		}
		$db->sql_freeresult($result);

		foreach ($fields as $field)
		{
			if (in_array($field['field_type'], array('image', 'songlist'), true))
			{
				continue; // not functional yet - see class doc comment
			}

			$post_key = 'field_' . $field['field_id'];

			if ($field['field_type'] === 'multiselect')
			{
				$submitted = $request->variable($post_key, array(''));
				$value = json_encode(array_values(array_filter($submitted, function ($v) { return $v !== ''; })));
			}
			else if ($field['field_type'] === 'checkbox')
			{
				$value = $request->variable($post_key, 0) ? '1' : '0';
			}
			else
			{
				$value = $request->variable($post_key, '', true);
			}

			// Required-field enforcement: only checked at creation here (and
			// only when enforcement includes 'creation') - 'approval'-timing
			// enforcement happens in the Ticketing System, not here.
			if ($is_new && $field['required'] && in_array($field['required_enforcement'], array('creation', 'both'), true))
			{
				$is_empty = ($field['field_type'] === 'multiselect') ? ($value === '[]') : ($value === '');
				if ($is_empty)
				{
					trigger_error($user->lang('GEM_FIELD_REQUIRED', $field['label']) . adm_back_link($this->u_action), E_USER_WARNING);
				}
			}

			// upsert
			$sql = 'SELECT value_id FROM ' . $this->values_table . '
					WHERE field_id = ' . (int) $field['field_id'] . '
					AND owner_type = 2 AND owner_id = ' . (int) $character_id;
			$existing_result = $db->sql_query($sql);
			$existing = $db->sql_fetchrow($existing_result);
			$db->sql_freeresult($existing_result);

			if ($existing)
			{
				$sql = 'UPDATE ' . $this->values_table . ' SET value = \'' . $db->sql_escape($value) . '\'
						WHERE value_id = ' . (int) $existing['value_id'];
				$db->sql_query($sql);
			}
			else if ($value !== '' && $value !== '[]')
			{
				$sql = 'INSERT INTO ' . $this->values_table . ' ' . $db->sql_build_array('INSERT', array(
					'field_id'   => (int) $field['field_id'],
					'owner_type' => 2,
					'owner_id'   => (int) $character_id,
					'value'      => $value,
				));
				$db->sql_query($sql);
			}
		}
	}

	// -------------------------------------------------------------------
	// Archive / unarchive
	// -------------------------------------------------------------------

	private function character_archive($character_id)
	{
		global $db, $user, $request, $template;

		$my_user_id = (int) $user->data['user_id'];

		$sql = 'SELECT * FROM ' . $this->characters_table . '
				WHERE character_id = ' . (int) $character_id . ' AND user_id = ' . $my_user_id;
		$result = $db->sql_query($sql);
		$character = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$character)
		{
			trigger_error('GEM_CHARACTER_NOT_FOUND', E_USER_WARNING);
		}

		if ($character['status'] != self::STATUS_ACTIVE)
		{
			trigger_error($user->lang('GEM_ONLY_ACTIVE_CAN_ARCHIVE') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('ucp_characters'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			$reason = $request->variable('reason', '', true);
			if ($reason === '')
			{
				trigger_error($user->lang('GEM_ARCHIVE_REASON_REQUIRED') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$sql = 'UPDATE ' . $this->characters_table . ' SET status = ' . self::STATUS_ARCHIVED . ', updated_at = ' . time() . '
					WHERE character_id = ' . (int) $character_id;
			$db->sql_query($sql);

			$this->log_status_change($character_id, self::STATUS_ACTIVE, self::STATUS_ARCHIVED, $reason, $my_user_id);

			// If this was the player's active default, clear it - an archived
			// character shouldn't stay the default going forward.
			$sql = 'DELETE FROM ' . $this->characters_active_table . '
					WHERE user_id = ' . $my_user_id . ' AND character_id = ' . (int) $character_id;
			$db->sql_query($sql);

			trigger_error($user->lang('GEM_CHARACTER_ARCHIVED') . adm_back_link($this->u_action));
		}

		// show the reason prompt
		$template->assign_vars(array(
			'S_ARCHIVE_PROMPT' => true,
			'CHARACTER_ID'     => $character_id,
			'CHARACTER_NAME'   => $character['character_name'],
			'U_ARCHIVE_SUBMIT' => $this->u_action . '&amp;action=archive&amp;character_id=' . (int) $character_id,
		));
	}

	private function character_unarchive($character_id)
	{
		global $db, $user, $auth, $request, $config;

		$sql = 'SELECT * FROM ' . $this->characters_table . ' WHERE character_id = ' . (int) $character_id;
		$result = $db->sql_query($sql);
		$character = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$character)
		{
			trigger_error('GEM_CHARACTER_NOT_FOUND', E_USER_WARNING);
		}

		$my_user_id = (int) $user->data['user_id'];
		$is_owner = ((int) $character['user_id'] === $my_user_id);
		$is_staff = (bool) $auth->acl_get('a_'); // stand-in for a real staff permission - see class doc comment
		$self_serve_allowed = (bool) $config['gem_self_unarchive'];

		if (!$is_staff && !($is_owner && $self_serve_allowed))
		{
			trigger_error('GEM_UNARCHIVE_NOT_PERMITTED', E_USER_WARNING);
		}

		if ($character['status'] != self::STATUS_ARCHIVED)
		{
			trigger_error($user->lang('GEM_ONLY_ARCHIVED_CAN_UNARCHIVE') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		// Low-risk, fully reversible action (can always re-archive), so this
		// is a single-click link rather than a confirm_box step.

		$sql = 'UPDATE ' . $this->characters_table . ' SET status = ' . self::STATUS_ACTIVE . ', updated_at = ' . time() . '
				WHERE character_id = ' . (int) $character_id;
		$db->sql_query($sql);

		$this->log_status_change($character_id, self::STATUS_ARCHIVED, self::STATUS_ACTIVE, null, $my_user_id);

		trigger_error($user->lang('GEM_CHARACTER_UNARCHIVED') . adm_back_link($this->u_action));
	}

	private function log_status_change($character_id, $old_status, $new_status, $reason, $changed_by)
	{
		global $db;

		$sql = 'INSERT INTO ' . $this->status_log_table . ' ' . $db->sql_build_array('INSERT', array(
			'character_id' => (int) $character_id,
			'old_status'   => (int) $old_status,
			'new_status'   => (int) $new_status,
			'reason'       => $reason ?: '',
			'changed_by'   => (int) $changed_by,
			'changed_at'   => time(),
		));
		$db->sql_query($sql);
	}
}
