<?php
/**
 * Gem - Character Management UCP language file (en)
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
	'UCP_CAT_GEM'          => 'Gem',
	'UCP_CHARACTERS'       => 'Characters',
	'UCP_CHARACTERS_MANAGE' => 'Manage Characters',

	'GEM_STATUS_ACTIVE'      => 'Active',
	'GEM_STATUS_ARCHIVED'    => 'Archived',
	'GEM_STATUS_DEACTIVATED' => 'Deactivated',
	'GEM_STATUS_PENDING'     => 'Pending approval',
	'GEM_STATUS_DECLINED'    => 'Declined',

	'GEM_ADD_CHARACTER'      => 'Add a character',
	'GEM_NO_CHARACTERS'      => 'You don\'t have any characters yet.',
	'GEM_CAP_REACHED_NOTICE' => 'You\'ve reached the maximum number of characters allowed.',
	'GEM_CHARACTER_CAP_REACHED' => 'You have reached the maximum number of characters allowed.',

	'GEM_CHARACTER_DETAILS'  => 'Character details',
	'GEM_CHARACTER_NAME'     => 'Character name',
	'GEM_CHARACTER_NAME_REQUIRED' => 'A character name is required.',
	'GEM_AVATAR_URL'         => 'Avatar image URL',
	'GEM_AVATAR_DIMENSIONS'  => 'Avatar dimensions (px)',
	'GEM_SHOWCASE_IMAGE'     => 'Showcase image URL',
	'GEM_SHOWCASE_IMAGE_HINT' => 'A larger, presentation-focused image for your character showcase on your profile - separate from your avatar.',
	'GEM_SIGNATURE'          => 'Signature',
	'GEM_FIELD_REQUIRED'     => '"%s" is required.',
	'GEM_FIELD_COMING_SOON'  => 'This field type isn\'t available to fill in yet.',

	'GEM_CHARACTER_CREATED'  => 'Character created.',
	'GEM_CHARACTER_SAVED'    => 'Character saved.',
	'GEM_CHARACTER_NOT_FOUND' => 'That character could not be found.',

	'GEM_ARCHIVE'            => 'Archive',
	'GEM_ARCHIVING'          => 'Archiving',
	'GEM_UNARCHIVE'          => 'Unarchive',
	'GEM_ARCHIVE_PROMPT_INTRO' => 'Archiving releases this character back to the pool - the name becomes available for someone else to apply for. This isn\'t permanent; it can be unarchived later.',
	'GEM_ARCHIVE_REASON'     => 'Reason',
	'GEM_ARCHIVE_REASON_HINT' => 'Shown in the character\'s history log.',
	'GEM_ARCHIVE_REASON_REQUIRED' => 'A reason is required to archive a character.',
	'GEM_CONFIRM_ARCHIVE'    => 'Archive this character',
	'GEM_CHARACTER_ARCHIVED' => 'Character archived.',
	'GEM_ONLY_ACTIVE_CAN_ARCHIVE' => 'Only active characters can be archived.',
	'GEM_ONLY_ARCHIVED_CAN_UNARCHIVE' => 'Only archived characters can be unarchived.',
	'GEM_UNARCHIVE_NOT_PERMITTED' => 'You don\'t have permission to unarchive this character - staff will need to do this instead.',
	'GEM_CHARACTER_UNARCHIVED' => 'Character unarchived.',
));
