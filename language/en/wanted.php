<?php
/**
 * Gem - Public Wanted Ads pages language file (en)
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
	'GEM_WANTED_CHARACTERS_TITLE' => 'Wanted Characters',
	'GEM_WANTED_PLOTS_TITLE'      => 'Wanted Plots',
	'GEM_ANY'                     => 'Any',
	'GEM_APPLY_FILTERS'           => 'Apply',
	'GEM_RESERVED'                => 'Reserved',
	'GEM_CONNECTED_TO'            => 'Connected to',
	'GEM_NO_WANTED_ADS'           => 'There are currently no active wanted characters.',
	'GEM_NO_WANTED_PLOTS'         => 'There are currently no active wanted plots.',
	'GEM_UMBRELLA_TAGS'           => 'Genre Tags',
	'GEM_SPECIFIC_TAGS'           => 'Specific Tags',
	'GEM_HIDE_ADULT'              => 'Hide adult content',
	'GEM_ADULT'                   => 'Adult',
	'GEM_REQUESTED_BY'            => 'Requested by',
	'GEM_TIED_TO'                 => 'Tied to',
));
