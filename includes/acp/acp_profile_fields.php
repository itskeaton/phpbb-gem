<?php
/**
 * Gem - Profile Fields ACP controller
 *
 * Two modes, both handled here: 'sections' and 'fields'. Each supports:
 *   - list (default view)
 *   - add / edit (same form, populated if editing)
 *   - delete (via phpBB's standard confirm_box - deleting a section reassigns
 *     its fields to "ungrouped" rather than deleting them; deleting a field
 *     cascades to delete its stored values, since orphaned values are dead
 *     weight with no field definition left to render them)
 *   - reorder, two ways per the spec: drag-and-drop (AJAX, posts the full
 *     ordered id list) and a plain numeric sort_order box per row for
 *     quick manual edits without dragging anything
 */

class acp_profile_fields
{
	var $u_action;

	/** @var string */
	private $sections_table;
	/** @var string */
	private $fields_table;
	/** @var string */
	private $values_table;
	/** @var string */
	private $ticket_categories_table;
	/** @var string */
	private $ticket_category_fields_table;

	private $allowed_field_types = array('text', 'textarea', 'select', 'multiselect', 'date', 'url', 'checkbox', 'image', 'songlist');
	private $allowed_applies_to = array(1 => 'PROFILE_APPLIES_PLAYER', 2 => 'PROFILE_APPLIES_CHARACTER', 3 => 'PROFILE_APPLIES_BOTH');
	private $allowed_enforcement = array('creation' => 'PROFILE_ENFORCE_CREATION', 'approval' => 'PROFILE_ENFORCE_APPROVAL', 'both' => 'PROFILE_ENFORCE_BOTH');

	function main($id, $mode)
	{
		global $db, $user, $template, $request, $phpbb_root_path, $phpEx, $table_prefix;

		$user->add_lang('acp/profile_fields');
		$this->tpl_name = 'acp_profile_fields';
		$this->page_title = ($mode == 'sections') ? 'ACP_PROFILE_SECTIONS_MANAGE' : 'ACP_PROFILE_FIELDS_MANAGE';

		$this->sections_table = $table_prefix . 'profile_sections';
		$this->fields_table   = $table_prefix . 'profile_fields';
		$this->values_table   = $table_prefix . 'profile_values';
		$this->ticket_categories_table = $table_prefix . 'ticket_categories';
		$this->ticket_category_fields_table = $table_prefix . 'ticket_category_fields';

		$action = $request->variable('action', 'list');

		// AJAX drag-and-drop reorder is handled before anything else touches
		// the template - it returns its own JSON response and exits.
		if ($action === 'reorder_drag' && $request->is_ajax())
		{
			$this->handle_drag_reorder($mode);
			return;
		}

		add_form_key('acp_profile_fields');

		$template->assign_vars(array(
			'U_ACTION'      => $this->u_action,
			'S_SECTIONS_MODE' => ($mode == 'sections'),
			'S_SETTINGS_MODE_TAB' => ($mode == 'settings'),
			'U_FIELDS_MODE'   => $this->u_action . '&amp;mode=fields',
			'U_SECTIONS_MODE' => $this->u_action . '&amp;mode=sections',
			'U_SETTINGS_MODE' => $this->u_action . '&amp;mode=settings',
			'U_TICKET_CATEGORIES_MODE' => $this->u_action . '&amp;mode=ticket_categories',
			'S_TICKET_CATEGORIES_MODE' => ($mode == 'ticket_categories'),
		));

		if ($mode == 'sections')
		{
			$this->handle_sections($action, $id);
		}
		else if ($mode == 'settings')
		{
			$this->handle_settings();
		}
		else if ($mode == 'ticket_categories')
		{
			$this->handle_ticket_categories($action);
		}
		else
		{
			$this->handle_fields($action, $id);
		}
	}

	// -------------------------------------------------------------------
	// Settings
	// -------------------------------------------------------------------

	private function handle_settings()
	{
		global $config, $template, $user, $request;

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('acp_profile_fields'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			$require_approval = $request->variable('gem_require_approval', 0);
			$max_characters   = $request->variable('gem_max_characters', 0);
			$self_unarchive   = $request->variable('gem_self_unarchive', 0);
			$gallery_quota    = $request->variable('gem_gallery_quota', 0);

			$config->set('gem_require_approval', $require_approval ? 1 : 0);
			$config->set('gem_max_characters', max(0, $max_characters));
			$config->set('gem_self_unarchive', $self_unarchive ? 1 : 0);
			$config->set('gem_gallery_quota', max(0, $gallery_quota));

			trigger_error($user->lang('ACP_GEM_SETTINGS_SAVED') . adm_back_link($this->u_action . '&amp;mode=settings'));
		}

		$template->assign_vars(array(
			'S_SETTINGS_MODE'       => true,
			'GEM_REQUIRE_APPROVAL'  => (bool) $config['gem_require_approval'],
			'GEM_MAX_CHARACTERS'    => (int) $config['gem_max_characters'],
			'GEM_SELF_UNARCHIVE'    => (bool) $config['gem_self_unarchive'],
			'GEM_GALLERY_QUOTA'     => (int) $config['gem_gallery_quota'],
		));
	}

	// -------------------------------------------------------------------
	// Sections
	// -------------------------------------------------------------------

	private function handle_sections($action, $id)
	{
		global $db, $user, $template, $request;

		$section_id = $request->variable('section_id', 0);

		switch ($action)
		{
			case 'add':
			case 'edit':
				$this->section_form($action, $section_id);
				return;

			case 'save':
				$this->section_save($section_id);
				return;

			case 'delete':
				$this->section_delete($section_id, $id);
				return;

			case 'reorder_numeric':
				$this->numeric_reorder($this->sections_table, 'section_id');
				trigger_error($user->lang('PROFILE_ORDER_UPDATED') . adm_back_link($this->u_action . '&amp;mode=sections'));
				return;
		}

		// list
		$sql = 'SELECT * FROM ' . $this->sections_table . ' ORDER BY sort_order ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('sections', array(
				'SECTION_ID'   => $row['section_id'],
				'SECTION_NAME' => $row['section_name'],
				'ANCHOR_SLUG'  => $row['anchor_slug'],
				'SORT_ORDER'   => $row['sort_order'],
				'U_EDIT'       => $this->u_action . "&amp;mode=sections&amp;action=edit&amp;section_id={$row['section_id']}",
				'U_DELETE'     => $this->u_action . "&amp;mode=sections&amp;action=delete&amp;section_id={$row['section_id']}",
			));
		}
		$db->sql_freeresult($result);

		$template->assign_vars(array(
			'U_ADD_SECTION' => $this->u_action . '&amp;mode=sections&amp;action=add',
		));
	}

	private function section_form($action, $section_id)
	{
		global $db, $template, $request;

		$section_name = '';
		$anchor_slug  = '';

		if ($action == 'edit' && $section_id)
		{
			$sql = 'SELECT * FROM ' . $this->sections_table . ' WHERE section_id = ' . (int) $section_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$row)
			{
				trigger_error('PROFILE_SECTION_NOT_FOUND', E_USER_WARNING);
			}

			$section_name = $row['section_name'];
			$anchor_slug  = $row['anchor_slug'];
		}

		$template->assign_vars(array(
			'S_EDIT_SECTION' => true,
			'SECTION_ID'     => $section_id,
			'SECTION_NAME'   => $section_name,
			'ANCHOR_SLUG'    => $anchor_slug,
			'U_SAVE'         => $this->u_action . '&amp;mode=sections&amp;action=save&amp;section_id=' . (int) $section_id,
		));
	}

	private function section_save($section_id)
	{
		global $db, $user, $request;

		if (!check_form_key('acp_profile_fields'))
		{
			trigger_error('FORM_INVALID', E_USER_WARNING);
		}

		$section_name = $request->variable('section_name', '', true);
		$anchor_slug  = $request->variable('anchor_slug', '', true);

		if ($section_name === '')
		{
			trigger_error($user->lang('PROFILE_SECTION_NAME_REQUIRED') . adm_back_link($this->u_action . '&amp;mode=sections'), E_USER_WARNING);
		}

		if ($anchor_slug === '')
		{
			$anchor_slug = $this->slugify($section_name);
		}
		else
		{
			$anchor_slug = $this->slugify($anchor_slug);
		}

		if ($section_id)
		{
			$sql_ary = array(
				'section_name' => $section_name,
				'anchor_slug'  => $anchor_slug,
			);
			$sql = 'UPDATE ' . $this->sections_table . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE section_id = ' . (int) $section_id;
			$db->sql_query($sql);
		}
		else
		{
			$sql = 'SELECT MAX(sort_order) AS max_order FROM ' . $this->sections_table;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			$next_order = ((int) $row['max_order']) + 1;

			$sql_ary = array(
				'section_name' => $section_name,
				'anchor_slug'  => $anchor_slug,
				'sort_order'   => $next_order,
			);
			$sql = 'INSERT INTO ' . $this->sections_table . ' ' . $db->sql_build_array('INSERT', $sql_ary);
			$db->sql_query($sql);
		}

		trigger_error($user->lang('PROFILE_SECTION_SAVED') . adm_back_link($this->u_action . '&amp;mode=sections'));
	}

	private function section_delete($section_id, $id)
	{
		global $db, $user, $request;

		if (!$section_id)
		{
			trigger_error('PROFILE_SECTION_NOT_FOUND', E_USER_WARNING);
		}

		if (confirm_box(true))
		{
			// Reassign any fields in this section to "ungrouped" (0) rather
			// than deleting them - a section is just an organizational
			// header, not a meaningful owner of the fields under it.
			$sql = 'UPDATE ' . $this->fields_table . ' SET section_id = 0 WHERE section_id = ' . (int) $section_id;
			$db->sql_query($sql);

			$sql = 'DELETE FROM ' . $this->sections_table . ' WHERE section_id = ' . (int) $section_id;
			$db->sql_query($sql);

			trigger_error($user->lang('PROFILE_SECTION_DELETED') . adm_back_link($this->u_action . '&amp;mode=sections'));
		}
		else
		{
			confirm_box(false, 'PROFILE_SECTION_DELETE_CONFIRM', build_hidden_fields(array(
				'section_id' => $section_id,
				'action'     => 'delete',
				'mode'       => 'sections',
			)));
		}
	}

	// -------------------------------------------------------------------
	// Fields
	// -------------------------------------------------------------------

	private function handle_fields($action, $id)
	{
		global $db, $user, $template, $request;

		$field_id = $request->variable('field_id', 0);

		switch ($action)
		{
			case 'add':
			case 'edit':
				$this->field_form($action, $field_id);
				return;

			case 'save':
				$this->field_save($field_id);
				return;

			case 'delete':
				$this->field_delete($field_id, $id);
				return;

			case 'reorder_numeric':
				$this->numeric_reorder($this->fields_table, 'field_id');
				trigger_error($user->lang('PROFILE_ORDER_UPDATED') . adm_back_link($this->u_action . '&amp;mode=fields'));
				return;
		}

		// list, grouped by section for readability - ungrouped (section_id 0) last
		$sql = 'SELECT f.*, s.section_name
				FROM ' . $this->fields_table . ' f
				LEFT JOIN ' . $this->sections_table . ' s ON f.section_id = s.section_id
				ORDER BY (f.section_id = 0) ASC, s.sort_order ASC, f.sort_order ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('fields', array(
				'FIELD_ID'      => $row['field_id'],
				'LABEL'         => $row['label'],
				'FIELD_KEY'     => $row['field_key'],
				'FIELD_TYPE'    => $row['field_type'],
				'SECTION_NAME'  => $row['section_name'] ?: $user->lang('PROFILE_UNGROUPED'),
				'APPLIES_TO'    => $user->lang($this->allowed_applies_to[(int) $row['applies_to']]),
				'REQUIRED'      => (bool) $row['required'],
				'SEARCHABLE'    => (bool) $row['searchable'],
				'SHOW_ON_ROSTER' => (bool) $row['show_on_roster'],
				'SHOW_IN_SHOWCASE' => (bool) $row['show_in_showcase'],
				'SORT_ORDER'    => $row['sort_order'],
				'U_EDIT'        => $this->u_action . "&amp;mode=fields&amp;action=edit&amp;field_id={$row['field_id']}",
				'U_DELETE'      => $this->u_action . "&amp;mode=fields&amp;action=delete&amp;field_id={$row['field_id']}",
			));
		}
		$db->sql_freeresult($result);

		$template->assign_vars(array(
			'U_ADD_FIELD' => $this->u_action . '&amp;mode=fields&amp;action=add',
		));

		$this->assign_field_type_options();
	}

	private function field_form($action, $field_id)
	{
		global $db, $template, $request;

		$field = array(
			'field_key'            => '',
			'label'                => '',
			'field_type'           => 'text',
			'field_options'        => '',
			'section_id'           => 0,
			'applies_to'           => 3,
			'required'             => 0,
			'required_enforcement' => 'creation',
			'searchable'           => 0,
			'show_on_roster'       => 0,
			'show_in_showcase'     => 0,
		);

		if ($action == 'edit' && $field_id)
		{
			$sql = 'SELECT * FROM ' . $this->fields_table . ' WHERE field_id = ' . (int) $field_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$row)
			{
				trigger_error('PROFILE_FIELD_NOT_FOUND', E_USER_WARNING);
			}

			$field = $row;
			// field_options is stored as JSON - present as one choice per line for editing
			$decoded = json_decode($field['field_options'], true);
			$field['field_options'] = is_array($decoded) ? implode("\n", $decoded) : '';
		}

		$template->assign_vars(array(
			'S_EDIT_FIELD'          => true,
			'FIELD_ID'              => $field_id,
			'FIELD_KEY'             => $field['field_key'],
			'LABEL'                 => $field['label'],
			'FIELD_OPTIONS_TEXT'    => $field['field_options'],
			'REQUIRED'              => (bool) $field['required'],
			'SEARCHABLE'            => (bool) $field['searchable'],
			'SHOW_ON_ROSTER'        => (bool) $field['show_on_roster'],
			'SHOW_IN_SHOWCASE'      => (bool) $field['show_in_showcase'],
			'U_SAVE'                => $this->u_action . '&amp;mode=fields&amp;action=save&amp;field_id=' . (int) $field_id,
		));

		$this->assign_field_type_options($field['field_type']);
		$this->assign_applies_to_options((int) $field['applies_to']);
		$this->assign_enforcement_options($field['required_enforcement']);
		$this->assign_section_options((int) $field['section_id']);
	}

	private function field_save($field_id)
	{
		global $db, $user, $request;

		if (!check_form_key('acp_profile_fields'))
		{
			trigger_error('FORM_INVALID', E_USER_WARNING);
		}

		$label       = $request->variable('label', '', true);
		$field_key   = $request->variable('field_key', '', true);
		$field_type  = $request->variable('field_type', 'text');
		$options_raw = $request->variable('field_options', '', true);
		$section_id  = $request->variable('section_id', 0);
		$applies_to  = $request->variable('applies_to', 3);
		$required    = $request->variable('required', 0);
		$enforcement = $request->variable('required_enforcement', 'creation');
		$searchable  = $request->variable('searchable', 0);
		$show_on_roster = $request->variable('show_on_roster', 0);
		$show_in_showcase = $request->variable('show_in_showcase', 0);

		if ($label === '')
		{
			trigger_error($user->lang('PROFILE_FIELD_LABEL_REQUIRED') . adm_back_link($this->u_action . '&amp;mode=fields'), E_USER_WARNING);
		}

		if (!in_array($field_type, $this->allowed_field_types, true))
		{
			trigger_error('PROFILE_INVALID_FIELD_TYPE', E_USER_WARNING);
		}

		if (!in_array($enforcement, array_keys($this->allowed_enforcement), true))
		{
			trigger_error('PROFILE_INVALID_ENFORCEMENT', E_USER_WARNING);
		}

		if ($field_key === '')
		{
			$field_key = $this->slugify($label);
		}
		else
		{
			$field_key = $this->slugify($field_key);
		}

		// field_options only means anything for select/multiselect - store as a JSON array either way for consistency
		$options_lines = array_filter(array_map('trim', explode("\n", $options_raw)));
		$field_options = json_encode(array_values($options_lines));

		// uniqueness check on field_key, excluding this row if editing
		$sql = 'SELECT field_id FROM ' . $this->fields_table . " WHERE field_key = '" . $db->sql_escape($field_key) . "'"
			. ($field_id ? ' AND field_id <> ' . (int) $field_id : '');
		$result = $db->sql_query($sql);
		$clash = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if ($clash)
		{
			trigger_error($user->lang('PROFILE_FIELD_KEY_TAKEN') . adm_back_link($this->u_action . '&amp;mode=fields'), E_USER_WARNING);
		}

		$sql_ary = array(
			'label'                => $label,
			'field_key'            => $field_key,
			'field_type'           => $field_type,
			'field_options'        => $field_options,
			'section_id'           => (int) $section_id,
			'applies_to'           => (int) $applies_to,
			'required'             => $required ? 1 : 0,
			'required_enforcement' => $enforcement,
			'searchable'           => $searchable ? 1 : 0,
			'show_on_roster'       => $show_on_roster ? 1 : 0,
			'show_in_showcase'     => $show_in_showcase ? 1 : 0,
		);

		if ($field_id)
		{
			$sql = 'UPDATE ' . $this->fields_table . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE field_id = ' . (int) $field_id;
			$db->sql_query($sql);
		}
		else
		{
			$sql = 'SELECT MAX(sort_order) AS max_order FROM ' . $this->fields_table . ' WHERE section_id = ' . (int) $section_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			$sql_ary['sort_order'] = ((int) $row['max_order']) + 1;

			$sql = 'INSERT INTO ' . $this->fields_table . ' ' . $db->sql_build_array('INSERT', $sql_ary);
			$db->sql_query($sql);
		}

		trigger_error($user->lang('PROFILE_FIELD_SAVED') . adm_back_link($this->u_action . '&amp;mode=fields'));
	}

	private function field_delete($field_id, $id)
	{
		global $db, $user;

		if (!$field_id)
		{
			trigger_error('PROFILE_FIELD_NOT_FOUND', E_USER_WARNING);
		}

		if (confirm_box(true))
		{
			// Cascades to stored values - an orphaned value with no field
			// definition left to render it is just dead weight, unlike a
			// section (which is purely organizational).
			$sql = 'DELETE FROM ' . $this->values_table . ' WHERE field_id = ' . (int) $field_id;
			$db->sql_query($sql);

			$sql = 'DELETE FROM ' . $this->fields_table . ' WHERE field_id = ' . (int) $field_id;
			$db->sql_query($sql);

			trigger_error($user->lang('PROFILE_FIELD_DELETED') . adm_back_link($this->u_action . '&amp;mode=fields'));
		}
		else
		{
			confirm_box(false, 'PROFILE_FIELD_DELETE_CONFIRM', build_hidden_fields(array(
				'field_id' => $field_id,
				'action'   => 'delete',
				'mode'     => 'fields',
			)));
		}
	}

	// -------------------------------------------------------------------
	// Reordering - numeric (plain form field per row) and drag (AJAX)
	// -------------------------------------------------------------------

	private function numeric_reorder($table, $id_column)
	{
		global $db, $request;

		if (!check_form_key('acp_profile_fields'))
		{
			trigger_error('FORM_INVALID', E_USER_WARNING);
		}

		$orders = $request->variable('sort_order', array(0 => 0));

		foreach ($orders as $row_id => $order)
		{
			$sql = 'UPDATE ' . $table . ' SET sort_order = ' . (int) $order . ' WHERE ' . $id_column . ' = ' . (int) $row_id;
			$db->sql_query($sql);
		}
	}

	private function handle_drag_reorder($mode)
	{
		global $db, $request;

		header('Content-Type: application/json; charset=utf-8');

		if (!check_form_key('acp_profile_fields'))
		{
			echo json_encode(array('status' => 'error', 'message' => 'Invalid form token'));
			exit;
		}

		$table = ($mode == 'sections') ? $this->sections_table : $this->fields_table;
		$id_column = ($mode == 'sections') ? 'section_id' : 'field_id';

		$ordered_ids = $request->variable('order', array(0));

		$position = 1;
		foreach ($ordered_ids as $row_id)
		{
			$sql = 'UPDATE ' . $table . ' SET sort_order = ' . $position . ' WHERE ' . $id_column . ' = ' . (int) $row_id;
			$db->sql_query($sql);
			$position++;
		}

		echo json_encode(array('status' => 'success'));
		exit;
	}

	// -------------------------------------------------------------------
	// Template option helpers
	// -------------------------------------------------------------------

	private function assign_field_type_options($selected = 'text')
	{
		global $template;

		foreach ($this->allowed_field_types as $type)
		{
			$template->assign_block_vars('field_types', array(
				'VALUE'    => $type,
				'LABEL'    => $type, // language keys can replace this with PROFILE_TYPE_* later
				'SELECTED' => ($type === $selected),
			));
		}
	}

	private function assign_applies_to_options($selected = 3)
	{
		global $template, $user;

		foreach ($this->allowed_applies_to as $value => $lang_key)
		{
			$template->assign_block_vars('applies_to_options', array(
				'VALUE'    => $value,
				'LABEL'    => $user->lang($lang_key),
				'SELECTED' => ($value === $selected),
			));
		}
	}

	private function assign_enforcement_options($selected = 'creation')
	{
		global $template, $user;

		foreach ($this->allowed_enforcement as $value => $lang_key)
		{
			$template->assign_block_vars('enforcement_options', array(
				'VALUE'    => $value,
				'LABEL'    => $user->lang($lang_key),
				'SELECTED' => ($value === $selected),
			));
		}
	}

	private function assign_section_options($selected = 0)
	{
		global $template, $db, $user;

		$template->assign_block_vars('section_options', array(
			'VALUE'    => 0,
			'LABEL'    => $user->lang('PROFILE_UNGROUPED'),
			'SELECTED' => ($selected === 0),
		));

		$sql = 'SELECT section_id, section_name FROM ' . $this->sections_table . ' ORDER BY sort_order ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('section_options', array(
				'VALUE'    => $row['section_id'],
				'LABEL'    => $row['section_name'],
				'SELECTED' => ($selected === (int) $row['section_id']),
			));
		}
		$db->sql_freeresult($result);
	}

	private function slugify($text)
	{
		$text = strtolower(trim($text));
		$text = preg_replace('/[^a-z0-9]+/', '_', $text);
		return trim($text, '_');
	}

	// -------------------------------------------------------------------
	// Ticket categories
	// -------------------------------------------------------------------

	private function handle_ticket_categories($action)
	{
		global $db, $user, $template, $request;

		$category_id = $request->variable('category_id', 0);

		switch ($action)
		{
			case 'add':
			case 'edit':
				$this->ticket_category_form($action, $category_id);
				return;

			case 'save':
				$this->ticket_category_save($category_id);
				return;

			case 'delete':
				$this->ticket_category_delete($category_id);
				return;
		}

		$sql = 'SELECT * FROM ' . $this->ticket_categories_table . ' ORDER BY sort_order ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('ticket_categories', array(
				'CATEGORY_ID'   => $row['category_id'],
				'CATEGORY_NAME' => $row['category_name'],
				'IS_APPLICATION' => (bool) $row['is_character_application'],
				'SORT_ORDER'    => $row['sort_order'],
				'U_EDIT'        => $this->u_action . "&amp;mode=ticket_categories&amp;action=edit&amp;category_id={$row['category_id']}",
				'U_DELETE'      => $this->u_action . "&amp;mode=ticket_categories&amp;action=delete&amp;category_id={$row['category_id']}",
			));
		}
		$db->sql_freeresult($result);

		$template->assign_vars(array(
			'U_ADD_TICKET_CATEGORY' => $this->u_action . '&amp;mode=ticket_categories&amp;action=add',
		));
	}

	private function ticket_category_form($action, $category_id)
	{
		global $db, $template;

		$category = array(
			'category_name'             => '',
			'is_character_application'  => 0,
			'required_group'            => 0,
		);
		$assigned_field_ids = array();

		if ($action == 'edit' && $category_id)
		{
			$sql = 'SELECT * FROM ' . $this->ticket_categories_table . ' WHERE category_id = ' . (int) $category_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$row)
			{
				trigger_error('GEM_TICKET_CATEGORY_NOT_FOUND', E_USER_WARNING);
			}
			$category = $row;

			$sql = 'SELECT field_id FROM ' . $this->ticket_category_fields_table . ' WHERE category_id = ' . (int) $category_id;
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$assigned_field_ids[] = (int) $row['field_id'];
			}
			$db->sql_freeresult($result);
		}

		$template->assign_vars(array(
			'S_EDIT_TICKET_CATEGORY' => true,
			'CATEGORY_ID'            => $category_id,
			'CATEGORY_NAME'          => $category['category_name'],
			'IS_APPLICATION'         => (bool) $category['is_character_application'],
			'REQUIRED_GROUP'         => (int) $category['required_group'],
			'U_SAVE'                 => $this->u_action . '&amp;mode=ticket_categories&amp;action=save&amp;category_id=' . (int) $category_id,
		));

		// Every field usable on a ticket form - applies_to is irrelevant here,
		// tickets are their own context, so all fields are eligible regardless
		// of whether they also apply to player/character.
		$sql = 'SELECT field_id, label FROM ' . $this->fields_table . ' ORDER BY sort_order ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('field_checkboxes', array(
				'FIELD_ID' => $row['field_id'],
				'LABEL'    => $row['label'],
				'CHECKED'  => in_array((int) $row['field_id'], $assigned_field_ids, true),
			));
		}
		$db->sql_freeresult($result);

		// Groups, for the "restrict to group" dropdown
		$this->assign_group_options((int) $category['required_group']);
	}

	private function assign_group_options($selected = 0)
	{
		global $db, $template, $user;

		$template->assign_block_vars('group_options', array(
			'VALUE'    => 0,
			'LABEL'    => $user->lang('GEM_ANY_LOGGED_IN'),
			'SELECTED' => ($selected === 0),
		));

		$sql = 'SELECT group_id, group_name FROM ' . GROUPS_TABLE . ' ORDER BY group_name ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('group_options', array(
				'VALUE'    => $row['group_id'],
				'LABEL'    => $row['group_name'],
				'SELECTED' => ($selected === (int) $row['group_id']),
			));
		}
		$db->sql_freeresult($result);
	}

	private function ticket_category_save($category_id)
	{
		global $db, $user, $request;

		if (!check_form_key('acp_profile_fields'))
		{
			trigger_error('FORM_INVALID', E_USER_WARNING);
		}

		$category_name = $request->variable('category_name', '', true);
		$is_application = $request->variable('is_character_application', 0);
		$required_group = $request->variable('required_group', 0);
		$field_ids = $request->variable('field_ids', array(0));

		if ($category_name === '')
		{
			trigger_error($user->lang('GEM_TICKET_CATEGORY_NAME_REQUIRED') . adm_back_link($this->u_action . '&amp;mode=ticket_categories'), E_USER_WARNING);
		}

		$sql_ary = array(
			'category_name'             => $category_name,
			'is_character_application'  => $is_application ? 1 : 0,
			'required_group'            => (int) $required_group,
		);

		if ($category_id)
		{
			$sql = 'UPDATE ' . $this->ticket_categories_table . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
					WHERE category_id = ' . (int) $category_id;
			$db->sql_query($sql);
		}
		else
		{
			$sql = 'SELECT MAX(sort_order) AS max_order FROM ' . $this->ticket_categories_table;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			$sql_ary['sort_order'] = ((int) $row['max_order']) + 1;

			$sql = 'INSERT INTO ' . $this->ticket_categories_table . ' ' . $db->sql_build_array('INSERT', $sql_ary);
			$db->sql_query($sql);
			$category_id = (int) $db->sql_nextid();
		}

		// Field assignment - full replace, simplest correct approach
		$sql = 'DELETE FROM ' . $this->ticket_category_fields_table . ' WHERE category_id = ' . (int) $category_id;
		$db->sql_query($sql);

		$sort = 0;
		foreach (array_filter($field_ids) as $field_id)
		{
			$sql = 'INSERT INTO ' . $this->ticket_category_fields_table . ' ' . $db->sql_build_array('INSERT', array(
				'category_id' => (int) $category_id,
				'field_id'    => (int) $field_id,
				'sort_order'  => $sort++,
			));
			$db->sql_query($sql);
		}

		trigger_error($user->lang('GEM_TICKET_CATEGORY_SAVED') . adm_back_link($this->u_action . '&amp;mode=ticket_categories'));
	}

	private function ticket_category_delete($category_id)
	{
		global $db, $user;

		if (!$category_id)
		{
			trigger_error('GEM_TICKET_CATEGORY_NOT_FOUND', E_USER_WARNING);
		}

		// KNOWN SIMPLIFICATION: doesn't check for or block deletion of a
		// category that still has tickets under it - existing tickets would
		// end up with an orphaned category_id. Fine for a category you're
		// certain is unused; avoid deleting ones with real ticket history
		// until this gets a proper safety check.
		if (confirm_box(true))
		{
			$sql = 'DELETE FROM ' . $this->ticket_category_fields_table . ' WHERE category_id = ' . (int) $category_id;
			$db->sql_query($sql);

			$sql = 'DELETE FROM ' . $this->ticket_categories_table . ' WHERE category_id = ' . (int) $category_id;
			$db->sql_query($sql);

			trigger_error($user->lang('GEM_TICKET_CATEGORY_DELETED') . adm_back_link($this->u_action . '&amp;mode=ticket_categories'));
		}
		else
		{
			confirm_box(false, 'GEM_TICKET_CATEGORY_DELETE_CONFIRM', build_hidden_fields(array(
				'category_id' => $category_id,
				'action'      => 'delete',
				'mode'        => 'ticket_categories',
			)));
		}
	}
}
