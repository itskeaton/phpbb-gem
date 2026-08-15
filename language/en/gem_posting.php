<?php
/**
 * Gem - Post Switcher language file (en)
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
	'GEM_POSTING_AS'          => 'Posting As',
	'GEM_CHARACTER'           => 'Character',
	'GEM_POST_AS_PLAYER'      => '(post as yourself)',
	'GEM_SET_AS_DEFAULT'      => 'Set as my default character',
	'GEM_SET_AS_DEFAULT_HINT' => 'Leave unchecked to use this character just for this post, without changing your default.',
	'GEM_SIDEBAR_IMAGE'       => 'Sidebar Image',
	'GEM_USE_DEFAULT_IMAGE'   => "(use this character's default)",
	'GEM_SIDEBAR_IMAGE_HINT'  => 'Pick a different sidebar image just for this post, or leave as default.',
));
