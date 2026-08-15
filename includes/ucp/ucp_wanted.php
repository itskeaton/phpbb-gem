<?php
/**
 * Gem - Wanted Ads UCP controller
 *
 * Single mode ('wanted'), action-routed. Every ad is posted AS one of the
 * player's own active characters - replaces TGG's free-text "connected to"
 * account name with a real character_id, reusing the same ownership
 * pattern as everything else in this build.
 *
 * Dynamic Profile Fields flagged wanted_character_field/wanted_plot_field
 * supply the vocab-driven fields (FC status, age range, etc.) - this
 * controller only owns the structural columns (name, image, blurb, status)
 * plus the tag/linking mechanics specific to plots.
 */

class ucp_wanted
{
	var $u_action;

	private $characters_table;
	private $wanted_characters_table;
	private $wanted_plots_table;
	private $fields_table;
	private $values_table;
	private $umbrella_tags_table;
	private $plot_tags_table;
	private $plot_tag_map_table;
	private $plot_umbrella_map_table;
	private $gallery_table;

	const CHAR_STATUS_ACTIVE = 1;

	// owner_type values on phpbb_profile_values
	const OWNER_WANTED_CHARACTER = 4;
	const OWNER_WANTED_PLOT      = 5;

	function main($id, $mode)
	{
		global $db, $user, $template, $request, $table_prefix;

		$user->add_lang('ucp/wanted');
		$this->tpl_name = 'ucp_wanted';
		$this->page_title = 'UCP_WANTED_MANAGE';

		$this->characters_table        = $table_prefix . 'characters';
		$this->wanted_characters_table = $table_prefix . 'wanted_characters';
		$this->wanted_plots_table      = $table_prefix . 'wanted_plots';
		$this->fields_table            = $table_prefix . 'profile_fields';
		$this->values_table            = $table_prefix . 'profile_values';
		$this->umbrella_tags_table     = $table_prefix . 'wanted_umbrella_tags';
		$this->plot_tags_table         = $table_prefix . 'wanted_plot_tags';
		$this->plot_tag_map_table      = $table_prefix . 'wanted_plot_tag_map';
		$this->plot_umbrella_map_table = $table_prefix . 'wanted_plot_umbrella_map';
		$this->gallery_table           = $table_prefix . 'character_gallery';

		add_form_key('ucp_wanted');

		$action = $request->variable('action', 'list');
		$character_id = $request->variable('character_id', 0);
		$ad_id = $request->variable('ad_id', 0);

		switch ($action)
		{
			case 'add_char':
			case 'edit_char':
				$this->character_ad_form($action, $character_id, $ad_id);
				return;

			case 'save_char':
				$this->save_character_ad($character_id, $ad_id);
				return;

			case 'delete_char':
				$this->delete_character_ad($ad_id);
				return;

			case 'add_plot':
			case 'edit_plot':
				$this->plot_form($action, $character_id, $ad_id);
				return;

			case 'save_plot':
				$this->save_plot($character_id, $ad_id);
				return;

			case 'delete_plot':
				$this->delete_plot($ad_id);
				return;
		}

		$this->list_dashboard();
	}

	// -------------------------------------------------------------------
	// Dashboard
	// -------------------------------------------------------------------

	private function my_active_characters()
	{
		global $db, $user;

		$sql = 'SELECT character_id, character_name FROM ' . $this->characters_table . '
				WHERE user_id = ' . (int) $user->data['user_id'] . '
				AND status = ' . self::CHAR_STATUS_ACTIVE . '
				ORDER BY character_name ASC';
		$result = $db->sql_query($sql);
		$characters = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$characters[] = $row;
		}
		$db->sql_freeresult($result);

		return $characters;
	}

	private function list_dashboard()
	{
		global $db, $user, $template, $config;

		$characters = $this->my_active_characters();
		$cap = (int) $config['gem_wanted_ad_cap'];

		foreach ($characters as $character)
		{
			$char_id = (int) $character['character_id'];

			$sql = 'SELECT COUNT(*) AS cnt FROM ' . $this->wanted_characters_table . ' WHERE character_id = ' . $char_id;
			$result = $db->sql_query($sql);
			$ad_count = (int) $db->sql_fetchfield('cnt');
			$db->sql_freeresult($result);

			$template->assign_block_vars('characters', array(
				'CHARACTER_ID'   => $char_id,
				'CHARACTER_NAME' => $character['character_name'],
				'AD_COUNT'       => $ad_count,
				'AD_CAP'         => $cap,
				'S_CAP_REACHED'  => ($cap > 0 && $ad_count >= $cap),
				'U_ADD_CHAR_AD'  => $this->u_action . '&amp;action=add_char&amp;character_id=' . $char_id,
				'U_ADD_PLOT'     => $this->u_action . '&amp;action=add_plot&amp;character_id=' . $char_id,
			));

			$sql = 'SELECT * FROM ' . $this->wanted_characters_table . '
					WHERE character_id = ' . $char_id . ' ORDER BY created_at DESC';
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$template->assign_block_vars('characters.ads', array(
					'AD_ID'      => $row['ad_id'],
					'CHAR_NAME'  => $row['char_name'],
					'VISIBLE'    => (bool) $row['ad_status'],
					'U_EDIT'     => $this->u_action . '&amp;action=edit_char&amp;character_id=' . $char_id . '&amp;ad_id=' . $row['ad_id'],
					'U_DELETE'   => $this->u_action . '&amp;action=delete_char&amp;ad_id=' . $row['ad_id'],
				));
			}
			$db->sql_freeresult($result);

			$sql = 'SELECT * FROM ' . $this->wanted_plots_table . '
					WHERE character_id = ' . $char_id . ' ORDER BY created_at DESC';
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$template->assign_block_vars('characters.plots', array(
					'AD_ID'    => $row['ad_id'],
					'TITLE'    => $row['title'],
					'VISIBLE'  => (bool) $row['ad_status'],
					'U_EDIT'   => $this->u_action . '&amp;action=edit_plot&amp;character_id=' . $char_id . '&amp;ad_id=' . $row['ad_id'],
					'U_DELETE' => $this->u_action . '&amp;action=delete_plot&amp;ad_id=' . $row['ad_id'],
				));
			}
			$db->sql_freeresult($result);
		}
	}

	private function owned_active_character_or_die($character_id)
	{
		global $db, $user;

		$sql = 'SELECT * FROM ' . $this->characters_table . '
				WHERE character_id = ' . (int) $character_id . '
				AND user_id = ' . (int) $user->data['user_id'] . '
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

	// -------------------------------------------------------------------
	// Shared: dynamic field rendering (reused for both ad types)
	// -------------------------------------------------------------------

	private function assign_dynamic_fields($visibility_column, $owner_type, $owner_id)
	{
		global $db, $template;

		$existing_values = array();
		if ($owner_id)
		{
			$sql = 'SELECT field_id, value FROM ' . $this->values_table . '
					WHERE owner_type = ' . (int) $owner_type . ' AND owner_id = ' . (int) $owner_id;
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$existing_values[$row['field_id']] = $row['value'];
			}
			$db->sql_freeresult($result);
		}

		$sql = 'SELECT * FROM ' . $this->fields_table . ' WHERE ' . $visibility_column . ' = 1 ORDER BY sort_order ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$raw_value = isset($existing_values[$row['field_id']]) ? $existing_values[$row['field_id']] : '';

			$block = array(
				'FIELD_ID'   => $row['field_id'],
				'LABEL'      => $row['label'],
				'REQUIRED'   => (bool) $row['required'],
				'S_TEXT'     => ($row['field_type'] === 'text'),
				'S_TEXTAREA' => ($row['field_type'] === 'textarea'),
				'S_SELECT'   => ($row['field_type'] === 'select'),
				'S_DATE'     => ($row['field_type'] === 'date'),
				'S_URL'      => ($row['field_type'] === 'url'),
				'S_CHECKBOX' => ($row['field_type'] === 'checkbox'),
				'VALUE'      => $raw_value,
				'CHECKED'    => ($row['field_type'] === 'checkbox' && $raw_value === '1'),
			);
			$template->assign_block_vars('dynamic_fields', $block);

			if ($row['field_type'] === 'select')
			{
				$choices = json_decode($row['field_options'], true);
				if (is_array($choices))
				{
					foreach ($choices as $choice)
					{
						$template->assign_block_vars('dynamic_fields.choices', array(
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

	private function save_dynamic_fields($visibility_column, $owner_type, $owner_id)
	{
		global $db, $request;

		$sql = 'SELECT * FROM ' . $this->fields_table . ' WHERE ' . $visibility_column . ' = 1';
		$result = $db->sql_query($sql);
		while ($field = $db->sql_fetchrow($result))
		{
			$post_key = 'field_' . $field['field_id'];
			$value = ($field['field_type'] === 'checkbox')
				? ($request->variable($post_key, 0) ? '1' : '0')
				: $request->variable($post_key, '', true);

			$sql2 = 'SELECT value_id FROM ' . $this->values_table . '
					WHERE field_id = ' . (int) $field['field_id'] . '
					AND owner_type = ' . (int) $owner_type . ' AND owner_id = ' . (int) $owner_id;
			$existing_result = $db->sql_query($sql2);
			$existing = $db->sql_fetchrow($existing_result);
			$db->sql_freeresult($existing_result);

			if ($existing && $value === '')
			{
				$sql2 = 'DELETE FROM ' . $this->values_table . ' WHERE value_id = ' . (int) $existing['value_id'];
				$db->sql_query($sql2);
			}
			else if ($existing)
			{
				$sql2 = 'UPDATE ' . $this->values_table . ' SET value = \'' . $db->sql_escape($value) . '\'
						WHERE value_id = ' . (int) $existing['value_id'];
				$db->sql_query($sql2);
			}
			else if ($value !== '')
			{
				$sql2 = 'INSERT INTO ' . $this->values_table . ' ' . $db->sql_build_array('INSERT', array(
					'field_id'   => (int) $field['field_id'],
					'owner_type' => (int) $owner_type,
					'owner_id'   => (int) $owner_id,
					'value'      => $value,
				));
				$db->sql_query($sql2);
			}
		}
		$db->sql_freeresult($result);
	}

	// -------------------------------------------------------------------
	// Wanted character ads
	// -------------------------------------------------------------------

	private function character_ad_form($action, $character_id, $ad_id)
	{
		global $db, $user, $template, $config;

		$character = $this->owned_active_character_or_die($character_id);

		$ad = array('char_name' => '', 'image_url' => '', 'blurb' => '', 'is_reserved' => 0, 'ad_status' => 1);

		if ($action == 'edit_char' && $ad_id)
		{
			$sql = 'SELECT * FROM ' . $this->wanted_characters_table . '
					WHERE ad_id = ' . (int) $ad_id . ' AND character_id = ' . (int) $character_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$row)
			{
				trigger_error('GEM_WANTED_AD_NOT_FOUND', E_USER_WARNING);
			}
			$ad = $row;
			decode_message($ad['blurb'], $ad['signature_bbcode_uid']);
		}
		else
		{
			// enforce cap on new ads only
			$cap = (int) $config['gem_wanted_ad_cap'];
			if ($cap > 0)
			{
				$sql = 'SELECT COUNT(*) AS cnt FROM ' . $this->wanted_characters_table . ' WHERE character_id = ' . (int) $character_id;
				$result = $db->sql_query($sql);
				$current = (int) $db->sql_fetchfield('cnt');
				$db->sql_freeresult($result);

				if ($current >= $cap)
				{
					trigger_error($user->lang('GEM_WANTED_CAP_REACHED', $cap) . adm_back_link($this->u_action), E_USER_WARNING);
				}
			}
		}

		$template->assign_vars(array(
			'S_CHAR_AD_FORM' => true,
			'CHARACTER_ID'   => $character_id,
			'CHARACTER_NAME' => $character['character_name'],
			'AD_ID'          => $ad_id,
			'AD_CHAR_NAME'   => $ad['char_name'],
			'IMAGE_URL'      => $ad['image_url'],
			'BLURB'          => $ad['blurb'],
			'IS_RESERVED'    => (bool) $ad['is_reserved'],
			'AD_STATUS'      => (int) $ad['ad_status'],
			'U_SAVE'         => $this->u_action . '&amp;action=save_char&amp;character_id=' . (int) $character_id . '&amp;ad_id=' . (int) $ad_id,
		));

		$this->assign_dynamic_fields('wanted_character_field', self::OWNER_WANTED_CHARACTER, $ad_id);
	}

	private function save_character_ad($character_id, $ad_id)
	{
		global $db, $user, $request, $config;

		if (!check_form_key('ucp_wanted'))
		{
			trigger_error('FORM_INVALID', E_USER_WARNING);
		}

		$character = $this->owned_active_character_or_die($character_id);
		$is_new = !$ad_id;

		if (!$is_new)
		{
			$sql = 'SELECT ad_id FROM ' . $this->wanted_characters_table . '
					WHERE ad_id = ' . (int) $ad_id . ' AND character_id = ' . (int) $character_id;
			$result = $db->sql_query($sql);
			$owned = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			if (!$owned)
			{
				trigger_error('GEM_WANTED_AD_NOT_FOUND', E_USER_WARNING);
			}
		}
		else
		{
			$cap = (int) $config['gem_wanted_ad_cap'];
			if ($cap > 0)
			{
				$sql = 'SELECT COUNT(*) AS cnt FROM ' . $this->wanted_characters_table . ' WHERE character_id = ' . (int) $character_id;
				$result = $db->sql_query($sql);
				$current = (int) $db->sql_fetchfield('cnt');
				$db->sql_freeresult($result);
				if ($current >= $cap)
				{
					trigger_error($user->lang('GEM_WANTED_CAP_REACHED', $cap) . adm_back_link($this->u_action), E_USER_WARNING);
				}
			}
		}

		$char_name = $request->variable('char_name', '', true);
		if ($char_name === '')
		{
			trigger_error($user->lang('GEM_WANTED_NAME_REQUIRED') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$blurb = $request->variable('blurb', '', true);
		$uid = $bitfield = $options = '';
		generate_text_for_storage($blurb, $uid, $bitfield, $options, true, true, true);

		$sql_ary = array(
			'char_name'                 => $char_name,
			'image_url'                 => $request->variable('image_url', '', true),
			'blurb'                     => $blurb,
			'signature_bbcode_uid'      => $uid,
			'signature_bbcode_bitfield' => $bitfield,
			'is_reserved'               => $request->variable('is_reserved', 0) ? 1 : 0,
			'ad_status'                 => $request->variable('ad_status', 1) ? 1 : 0,
			'updated_at'                => time(),
		);

		if ($is_new)
		{
			$sql_ary['character_id'] = (int) $character_id;
			$sql_ary['created_at']   = time();

			$sql = 'INSERT INTO ' . $this->wanted_characters_table . ' ' . $db->sql_build_array('INSERT', $sql_ary);
			$db->sql_query($sql);
			$ad_id = (int) $db->sql_nextid();
		}
		else
		{
			$sql = 'UPDATE ' . $this->wanted_characters_table . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
					WHERE ad_id = ' . (int) $ad_id;
			$db->sql_query($sql);
		}

		$this->save_dynamic_fields('wanted_character_field', self::OWNER_WANTED_CHARACTER, $ad_id);

		trigger_error($user->lang($is_new ? 'GEM_WANTED_AD_CREATED' : 'GEM_WANTED_AD_SAVED') . adm_back_link($this->u_action));
	}

	private function delete_character_ad($ad_id)
	{
		global $db, $user;

		$sql = 'SELECT wc.ad_id FROM ' . $this->wanted_characters_table . ' wc
				JOIN ' . $this->characters_table . ' ch ON wc.character_id = ch.character_id
				WHERE wc.ad_id = ' . (int) $ad_id . ' AND ch.user_id = ' . (int) $user->data['user_id'];
		$result = $db->sql_query($sql);
		$owned = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$owned)
		{
			trigger_error('GEM_WANTED_AD_NOT_FOUND', E_USER_WARNING);
		}

		$sql = 'DELETE FROM ' . $this->values_table . ' WHERE owner_type = ' . self::OWNER_WANTED_CHARACTER . ' AND owner_id = ' . (int) $ad_id;
		$db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->wanted_characters_table . ' WHERE ad_id = ' . (int) $ad_id;
		$db->sql_query($sql);

		trigger_error($user->lang('GEM_WANTED_AD_DELETED') . adm_back_link($this->u_action));
	}

	// -------------------------------------------------------------------
	// Wanted plots
	// -------------------------------------------------------------------

	private function plot_form($action, $character_id, $ad_id)
	{
		global $db, $user, $template;

		$character = $this->owned_active_character_or_die($character_id);

		$plot = array('title' => '', 'teaser' => '', 'linked_ad_id' => 0, 'image_url' => '', 'blurb' => '', 'is_adult_content' => 0, 'ad_status' => 1);
		$selected_umbrella = array();
		$specific_tags_csv = '';

		if ($action == 'edit_plot' && $ad_id)
		{
			$sql = 'SELECT * FROM ' . $this->wanted_plots_table . '
					WHERE ad_id = ' . (int) $ad_id . ' AND character_id = ' . (int) $character_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$row)
			{
				trigger_error('GEM_WANTED_AD_NOT_FOUND', E_USER_WARNING);
			}
			$plot = $row;
			decode_message($plot['blurb'], $plot['signature_bbcode_uid']);

			$sql = 'SELECT tag_id FROM ' . $this->plot_umbrella_map_table . ' WHERE ad_id = ' . (int) $ad_id;
			$result = $db->sql_query($sql);
			while ($r = $db->sql_fetchrow($result))
			{
				$selected_umbrella[] = (int) $r['tag_id'];
			}
			$db->sql_freeresult($result);

			$sql = 'SELECT pt.tag_name FROM ' . $this->plot_tag_map_table . ' ptm
					JOIN ' . $this->plot_tags_table . ' pt ON ptm.tag_id = pt.tag_id
					WHERE ptm.ad_id = ' . (int) $ad_id;
			$result = $db->sql_query($sql);
			$tag_names = array();
			while ($r = $db->sql_fetchrow($result))
			{
				$tag_names[] = $r['tag_name'];
			}
			$db->sql_freeresult($result);
			$specific_tags_csv = implode(', ', $tag_names);
		}

		$template->assign_vars(array(
			'S_PLOT_FORM'    => true,
			'CHARACTER_ID'   => $character_id,
			'CHARACTER_NAME' => $character['character_name'],
			'AD_ID'          => $ad_id,
			'TITLE'          => $plot['title'],
			'TEASER'         => $plot['teaser'],
			'IMAGE_URL'      => $plot['image_url'],
			'BLURB'          => $plot['blurb'],
			'IS_ADULT'       => (bool) $plot['is_adult_content'],
			'AD_STATUS'      => (int) $plot['ad_status'],
			'SPECIFIC_TAGS'  => $specific_tags_csv,
			'U_SAVE'         => $this->u_action . '&amp;action=save_plot&amp;character_id=' . (int) $character_id . '&amp;ad_id=' . (int) $ad_id,
		));

		// This character's own wanted-character ads, for the optional link dropdown
		$sql = 'SELECT ad_id, char_name FROM ' . $this->wanted_characters_table . '
				WHERE character_id = ' . (int) $character_id . ' ORDER BY char_name ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('own_ads', array(
				'AD_ID'     => $row['ad_id'],
				'CHAR_NAME' => $row['char_name'],
				'SELECTED'  => ((int) $row['ad_id'] === (int) $plot['linked_ad_id']),
			));
		}
		$db->sql_freeresult($result);

		// Umbrella tag checkboxes
		$sql = 'SELECT * FROM ' . $this->umbrella_tags_table . ' ORDER BY sort_order ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('umbrella_options', array(
				'TAG_ID'   => $row['tag_id'],
				'TAG_NAME' => $row['tag_name'],
				'CHECKED'  => in_array((int) $row['tag_id'], $selected_umbrella, true),
			));
		}
		$db->sql_freeresult($result);

		// Existing specific-tag vocabulary, for the datalist autocomplete
		$sql = 'SELECT tag_name FROM ' . $this->plot_tags_table . ' ORDER BY tag_name ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('existing_tags', array('TAG_NAME' => $row['tag_name']));
		}
		$db->sql_freeresult($result);

		$this->assign_dynamic_fields('wanted_plot_field', self::OWNER_WANTED_PLOT, $ad_id);
	}

	private function save_plot($character_id, $ad_id)
	{
		global $db, $user, $request;

		if (!check_form_key('ucp_wanted'))
		{
			trigger_error('FORM_INVALID', E_USER_WARNING);
		}

		$character = $this->owned_active_character_or_die($character_id);
		$is_new = !$ad_id;

		if (!$is_new)
		{
			$sql = 'SELECT ad_id FROM ' . $this->wanted_plots_table . '
					WHERE ad_id = ' . (int) $ad_id . ' AND character_id = ' . (int) $character_id;
			$result = $db->sql_query($sql);
			$owned = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			if (!$owned)
			{
				trigger_error('GEM_WANTED_AD_NOT_FOUND', E_USER_WARNING);
			}
		}

		$title = $request->variable('title', '', true);
		$teaser = substr($request->variable('teaser', '', true), 0, 300);

		if ($title === '' || $teaser === '')
		{
			trigger_error($user->lang('GEM_PLOT_TITLE_TEASER_REQUIRED') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$umbrella_selected = $request->variable('umbrella_tags', array(0));
		$umbrella_selected = array_filter(array_map('intval', $umbrella_selected));
		if (empty($umbrella_selected))
		{
			trigger_error($user->lang('GEM_UMBRELLA_TAG_REQUIRED') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		// linked_ad_id must belong to THIS same character - Gem's stricter,
		// character-level version of TGG's original same-user check.
		$linked_ad_id = $request->variable('linked_ad_id', 0);
		if ($linked_ad_id > 0)
		{
			$sql = 'SELECT ad_id FROM ' . $this->wanted_characters_table . '
					WHERE ad_id = ' . (int) $linked_ad_id . ' AND character_id = ' . (int) $character_id;
			$result = $db->sql_query($sql);
			if (!$db->sql_fetchrow($result))
			{
				$linked_ad_id = 0;
			}
			$db->sql_freeresult($result);
		}

		$blurb = $request->variable('blurb', '', true);
		$uid = $bitfield = $options = '';
		generate_text_for_storage($blurb, $uid, $bitfield, $options, true, true, true);

		$sql_ary = array(
			'title'                     => $title,
			'teaser'                    => $teaser,
			'linked_ad_id'              => (int) $linked_ad_id,
			'image_url'                 => $request->variable('image_url', '', true),
			'blurb'                     => $blurb,
			'signature_bbcode_uid'      => $uid,
			'signature_bbcode_bitfield' => $bitfield,
			'is_adult_content'          => $request->variable('is_adult_content', 0) ? 1 : 0,
			'ad_status'                 => $request->variable('ad_status', 1) ? 1 : 0,
			'updated_at'                => time(),
		);

		if ($is_new)
		{
			$sql_ary['character_id'] = (int) $character_id;
			$sql_ary['created_at']   = time();

			$sql = 'INSERT INTO ' . $this->wanted_plots_table . ' ' . $db->sql_build_array('INSERT', $sql_ary);
			$db->sql_query($sql);
			$ad_id = (int) $db->sql_nextid();
		}
		else
		{
			$sql = 'UPDATE ' . $this->wanted_plots_table . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
					WHERE ad_id = ' . (int) $ad_id;
			$db->sql_query($sql);
		}

		// Umbrella tags - rebuild from scratch
		$sql = 'DELETE FROM ' . $this->plot_umbrella_map_table . ' WHERE ad_id = ' . (int) $ad_id;
		$db->sql_query($sql);
		foreach ($umbrella_selected as $tag_id)
		{
			$sql = 'INSERT INTO ' . $this->plot_umbrella_map_table . ' ' . $db->sql_build_array('INSERT', array(
				'ad_id'  => (int) $ad_id,
				'tag_id' => (int) $tag_id,
			));
			$db->sql_query($sql);
		}

		// Specific tags - freeform, auto-create on first use, rebuild map from scratch
		$raw_tags = $request->variable('specific_tags', '', true);
		$tag_names = array_filter(array_unique(array_map('trim', explode(',', $raw_tags))), function ($v) { return $v !== ''; });

		$sql = 'DELETE FROM ' . $this->plot_tag_map_table . ' WHERE ad_id = ' . (int) $ad_id;
		$db->sql_query($sql);

		foreach ($tag_names as $tag_name)
		{
			$tag_name = substr($tag_name, 0, 50);

			$sql = 'SELECT tag_id FROM ' . $this->plot_tags_table . " WHERE LOWER(tag_name) = '" . $db->sql_escape(strtolower($tag_name)) . "'";
			$result = $db->sql_query($sql);
			$tag_row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if ($tag_row)
			{
				$tag_id = (int) $tag_row['tag_id'];
			}
			else
			{
				$sql = 'INSERT INTO ' . $this->plot_tags_table . ' ' . $db->sql_build_array('INSERT', array('tag_name' => $tag_name));
				$db->sql_query($sql);
				$tag_id = (int) $db->sql_nextid();
			}

			$sql = 'INSERT INTO ' . $this->plot_tag_map_table . ' ' . $db->sql_build_array('INSERT', array('ad_id' => (int) $ad_id, 'tag_id' => $tag_id));
			$db->sql_query($sql);
		}

		$this->save_dynamic_fields('wanted_plot_field', self::OWNER_WANTED_PLOT, $ad_id);

		trigger_error($user->lang($is_new ? 'GEM_PLOT_CREATED' : 'GEM_PLOT_SAVED') . adm_back_link($this->u_action));
	}

	private function delete_plot($ad_id)
	{
		global $db, $user;

		$sql = 'SELECT wp.ad_id FROM ' . $this->wanted_plots_table . ' wp
				JOIN ' . $this->characters_table . ' ch ON wp.character_id = ch.character_id
				WHERE wp.ad_id = ' . (int) $ad_id . ' AND ch.user_id = ' . (int) $user->data['user_id'];
		$result = $db->sql_query($sql);
		$owned = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$owned)
		{
			trigger_error('GEM_WANTED_AD_NOT_FOUND', E_USER_WARNING);
		}

		$sql = 'DELETE FROM ' . $this->values_table . ' WHERE owner_type = ' . self::OWNER_WANTED_PLOT . ' AND owner_id = ' . (int) $ad_id;
		$db->sql_query($sql);
		$sql = 'DELETE FROM ' . $this->plot_tag_map_table . ' WHERE ad_id = ' . (int) $ad_id;
		$db->sql_query($sql);
		$sql = 'DELETE FROM ' . $this->plot_umbrella_map_table . ' WHERE ad_id = ' . (int) $ad_id;
		$db->sql_query($sql);
		$sql = 'DELETE FROM ' . $this->wanted_plots_table . ' WHERE ad_id = ' . (int) $ad_id;
		$db->sql_query($sql);

		trigger_error($user->lang('GEM_PLOT_DELETED') . adm_back_link($this->u_action));
	}
}
