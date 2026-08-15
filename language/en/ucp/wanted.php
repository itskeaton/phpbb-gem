<?php
/**
 * Gem - Wanted Ads UCP language file (en)
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
	'UCP_CAT_GEM'       => 'Gem',
	'UCP_WANTED'        => 'Wanted Ads',
	'UCP_WANTED_MANAGE' => 'Wanted Ads Dashboard',

	'GEM_WANTED_AD'             => 'Wanted Ad',
	'GEM_WANTED_PLOT'           => 'Wanted Plot',
	'GEM_ADD_WANTED_AD'         => 'Add wanted ad',
	'GEM_ADD_WANTED_PLOT'       => 'Add wanted plot',
	'GEM_WANTED_CHAR_NAME'      => 'Wanted character name',
	'GEM_WANTED_NAME_REQUIRED'  => 'A character name is required.',
	'GEM_IMAGE_URL'             => 'Image URL',
	'GEM_BLURB'                 => 'Details',
	'GEM_IS_RESERVED'           => 'Mark as reserved',
	'GEM_VISIBILITY'            => 'Visibility',
	'GEM_VISIBLE'               => 'Visible',
	'GEM_HIDDEN'                => 'Hidden',
	'GEM_CAP_REACHED_NOTICE'    => 'This character has reached the wanted-ad limit.',
	'GEM_WANTED_CAP_REACHED'    => 'You\'ve reached the limit of %d wanted ads for this character.',
	'GEM_WANTED_AD_CREATED'     => 'Wanted ad posted.',
	'GEM_WANTED_AD_SAVED'       => 'Wanted ad updated.',
	'GEM_WANTED_AD_DELETED'     => 'Wanted ad removed.',
	'GEM_WANTED_AD_NOT_FOUND'   => 'That wanted ad could not be found.',

	'GEM_PLOT_TITLE'            => 'Plot title',
	'GEM_PLOT_TEASER'           => 'Teaser (max 300 characters)',
	'GEM_PLOT_TITLE_TEASER_REQUIRED' => 'A title and teaser are required.',
	'GEM_LINK_TO_CHARACTER'     => 'Link to a wanted character (optional)',
	'GEM_STANDALONE_PLOT'       => 'None - standalone plot',
	'GEM_PLOT_IMAGE_OVERRIDE'   => 'Replacement image (optional)',
	'GEM_PLOT_IMAGE_OVERRIDE_HINT' => 'Leave blank to use this character\'s default sidebar image.',
	'GEM_UMBRELLA_TAGS'         => 'Genre tags (select at least one)',
	'GEM_UMBRELLA_TAG_REQUIRED' => 'At least one genre tag is required.',
	'GEM_SPECIFIC_TAGS'         => 'Specific tags (comma-separated)',
	'GEM_ADULT_CONTENT'         => 'Adult content',
	'GEM_ADULT_CONTENT_CONFIRM' => 'Yes, this plot involves adult content',
	'GEM_PLOT_CREATED'          => 'Wanted plot posted.',
	'GEM_PLOT_SAVED'            => 'Wanted plot updated.',
	'GEM_PLOT_DELETED'          => 'Wanted plot removed.',
));
