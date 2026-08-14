<?php
/**
 * Gem - Public Character Roster language file (en)
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
	'GEM_ROSTER_TITLE'      => 'Character Roster',
	'GEM_SEARCH'            => 'Search',
	'GEM_ANY'               => 'Any',
	'GEM_APPLY_FILTERS'     => 'Apply',
	'GEM_RESULTS_FOUND'     => 'characters found',
	'GEM_NO_RESULTS'        => 'No characters match your search.',
	'GEM_PLAYED_BY'         => 'Played By',
	'GEM_OTHER_CHARACTERS'  => 'Other Characters',
	'GEM_RECENT_POSTS'      => 'Recent Posts',
	'GEM_CHARACTER_NOT_FOUND' => 'That character could not be found.',
));
