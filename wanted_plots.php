<?php
/**
 * Gem - Public Wanted Plots page
 *
 * The flip-book cover-flip effect is pure CSS (see the template) - no JS
 * needed for that part. Same server-side filtering deviation from TGG as
 * wanted_characters.php: umbrella tags, specific tags, and the adult-hide
 * toggle are all resolved via query params + SQL rather than client-side
 * JS, for consistency with the rest of Gem.
 */

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();
$user->add_lang('wanted');

$table_prefix = defined('PHPBB_TABLE_PREFIX') ? PHPBB_TABLE_PREFIX : 'phpbb_';
$wanted_plots_table       = $table_prefix . 'wanted_plots';
$characters_table         = $table_prefix . 'characters';
$gallery_table            = $table_prefix . 'character_gallery';
$umbrella_tags_table      = $table_prefix . 'wanted_umbrella_tags';
$plot_tags_table          = $table_prefix . 'wanted_plot_tags';
$plot_tag_map_table       = $table_prefix . 'wanted_plot_tag_map';
$plot_umbrella_map_table  = $table_prefix . 'wanted_plot_umbrella_map';

page_header($user->lang('GEM_WANTED_PLOTS_TITLE'));
$template->set_filenames(array('body' => 'wanted_plots_body.html'));

$hide_adult = $request->variable('hide_adult', 0);
$umbrella_filter = $request->variable('umbrella', array(0));
$umbrella_filter = array_filter(array_map('intval', $umbrella_filter));
$tag_filter = $request->variable('tag', array(0));
$tag_filter = array_filter(array_map('intval', $tag_filter));

$where = 'wp.ad_status = 1';
if ($hide_adult)
{
	$where .= ' AND wp.is_adult_content = 0';
}

$matching_ids = null;
if (!empty($umbrella_filter))
{
	$sql = 'SELECT DISTINCT ad_id FROM ' . $plot_umbrella_map_table . ' WHERE ' . $db->sql_in_set('tag_id', $umbrella_filter);
	$result = $db->sql_query($sql);
	$ids = array();
	while ($row = $db->sql_fetchrow($result)) { $ids[] = (int) $row['ad_id']; }
	$db->sql_freeresult($result);
	$matching_ids = ($matching_ids === null) ? $ids : array_intersect($matching_ids, $ids);
}
if (!empty($tag_filter))
{
	$sql = 'SELECT DISTINCT ad_id FROM ' . $plot_tag_map_table . ' WHERE ' . $db->sql_in_set('tag_id', $tag_filter);
	$result = $db->sql_query($sql);
	$ids = array();
	while ($row = $db->sql_fetchrow($result)) { $ids[] = (int) $row['ad_id']; }
	$db->sql_freeresult($result);
	$matching_ids = ($matching_ids === null) ? $ids : array_intersect($matching_ids, $ids);
}
if ($matching_ids !== null)
{
	$matching_ids = empty($matching_ids) ? array(0) : $matching_ids;
	$where .= ' AND ' . $db->sql_in_set('wp.ad_id', $matching_ids);
}

$sql = 'SELECT wp.*, ch.character_name, ch.character_colour, wc.char_name AS linked_char_name
		FROM ' . $wanted_plots_table . ' wp
		LEFT JOIN ' . $characters_table . ' ch ON wp.character_id = ch.character_id
		LEFT JOIN ' . $table_prefix . 'wanted_characters wc ON wc.ad_id = wp.linked_ad_id
		WHERE ' . $where . '
		ORDER BY wp.created_at DESC';
$result = $db->sql_query($sql);

$has_results = false;
while ($row = $db->sql_fetchrow($result))
{
	$has_results = true;

	// Image: per-ad override > posting character's default sidebar image > nothing
	$image_url = $row['image_url'];
	if (!$image_url)
	{
		$sql2 = 'SELECT image_url FROM ' . $gallery_table . '
				WHERE character_id = ' . (int) $row['character_id'] . "
				AND album = 'sidebar' AND is_default = 1";
		$result2 = $db->sql_query($sql2);
		$image_url = (string) $db->sql_fetchfield('image_url');
		$db->sql_freeresult($result2);
	}

	$blurb = generate_text_for_display($row['blurb'], $row['signature_bbcode_uid'], $row['signature_bbcode_bitfield'], OPTION_FLAG_BBCODE | OPTION_FLAG_SMILIES | OPTION_FLAG_LINKS);

	$template->assign_block_vars('plot_ads', array(
		'AD_ID'            => $row['ad_id'],
		'TITLE'            => $row['title'],
		'TEASER'           => $row['teaser'],
		'IMAGE_URL'        => $image_url,
		'BLURB'            => $blurb,
		'IS_ADULT'         => (bool) $row['is_adult_content'],
		'AUTHOR_NAME'      => $row['character_name'],
		'U_AUTHOR'         => append_sid("{$phpbb_root_path}character_roster.{$phpEx}", 'mode=profile&amp;character_id=' . $row['character_id']),
		'LINKED_CHAR_NAME' => $row['linked_char_name'] ?: '',
		'U_LINKED_CHAR'    => $row['linked_ad_id'] ? append_sid("{$phpbb_root_path}wanted_characters.{$phpEx}") . '#wanted-ad-' . (int) $row['linked_ad_id'] : '',
	));

	$sql2 = 'SELECT cat.tag_name FROM ' . $plot_umbrella_map_table . ' m
			JOIN ' . $umbrella_tags_table . ' cat ON m.tag_id = cat.tag_id
			WHERE m.ad_id = ' . (int) $row['ad_id'];
	$result2 = $db->sql_query($sql2);
	while ($t = $db->sql_fetchrow($result2))
	{
		$template->assign_block_vars('plot_ads.umbrella_display', array('TAG_NAME' => $t['tag_name']));
	}
	$db->sql_freeresult($result2);

	$sql2 = 'SELECT pt.tag_name FROM ' . $plot_tag_map_table . ' m
			JOIN ' . $plot_tags_table . ' pt ON m.tag_id = pt.tag_id
			WHERE m.ad_id = ' . (int) $row['ad_id'];
	$result2 = $db->sql_query($sql2);
	while ($t = $db->sql_fetchrow($result2))
	{
		$template->assign_block_vars('plot_ads.tag_display', array('TAG_NAME' => $t['tag_name']));
	}
	$db->sql_freeresult($result2);
}
$db->sql_freeresult($result);

// Filter option lists
$sql = 'SELECT * FROM ' . $umbrella_tags_table . ' ORDER BY sort_order ASC';
$result = $db->sql_query($sql);
while ($row = $db->sql_fetchrow($result))
{
	$template->assign_block_vars('umbrella_filter_options', array(
		'TAG_ID'   => $row['tag_id'],
		'TAG_NAME' => $row['tag_name'],
		'CHECKED'  => in_array((int) $row['tag_id'], $umbrella_filter, true),
	));
}
$db->sql_freeresult($result);

$sql = 'SELECT * FROM ' . $plot_tags_table . ' ORDER BY tag_name ASC';
$result = $db->sql_query($sql);
while ($row = $db->sql_fetchrow($result))
{
	$template->assign_block_vars('specific_filter_options', array(
		'TAG_ID'   => $row['tag_id'],
		'TAG_NAME' => $row['tag_name'],
		'CHECKED'  => in_array((int) $row['tag_id'], $tag_filter, true),
	));
}
$db->sql_freeresult($result);

$template->assign_vars(array(
	'S_HAS_RESULTS' => $has_results,
	'S_HIDE_ADULT'  => (bool) $hide_adult,
	'U_ACTION'      => append_sid("{$phpbb_root_path}wanted_plots.{$phpEx}"),
));

page_footer();
