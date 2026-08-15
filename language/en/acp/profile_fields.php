<?php
/**
 * Gem - Profile Fields ACP language file (en)
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
	'ACP_CAT_GEM'                   => 'Gem',
	'ACP_PROFILE_FIELDS'            => 'Profile Fields',
	'ACP_PROFILE_FIELDS_MANAGE'     => 'Fields',
	'ACP_PROFILE_SECTIONS_MANAGE'   => 'Sections',

	'PROFILE_ADD_SECTION'           => 'Add section',
	'PROFILE_ADD_FIELD'             => 'Add field',

	'PROFILE_SECTION_DETAILS'       => 'Section details',
	'PROFILE_SECTION_NAME'          => 'Section name',
	'PROFILE_ANCHOR_SLUG'           => 'Anchor slug',
	'PROFILE_ANCHOR_SLUG_HINT'      => 'Leave blank to auto-generate from the section name. Used for in-page jump links.',
	'PROFILE_SECTION_NAME_REQUIRED' => 'A section name is required.',
	'PROFILE_SECTION_SAVED'         => 'Section saved.',
	'PROFILE_SECTION_DELETED'       => 'Section deleted. Any fields that were in it are now ungrouped.',
	'PROFILE_SECTION_DELETE_CONFIRM' => 'Delete this section? Fields inside it will not be deleted - they will become ungrouped.',
	'PROFILE_SECTION_NOT_FOUND'     => 'That section could not be found.',

	'PROFILE_FIELD_DETAILS'         => 'Field details',
	'PROFILE_FIELD_LABEL'           => 'Label',
	'PROFILE_FIELD_KEY'             => 'Field key',
	'PROFILE_FIELD_KEY_HINT'        => 'Leave blank to auto-generate from the label. This is the variable name used in custom templates.',
	'PROFILE_FIELD_LABEL_REQUIRED'  => 'A label is required.',
	'PROFILE_FIELD_KEY_TAKEN'       => 'That field key is already in use - choose another.',
	'PROFILE_FIELD_SAVED'           => 'Field saved.',
	'PROFILE_FIELD_DELETED'         => 'Field deleted, along with any stored values for it.',
	'PROFILE_FIELD_DELETE_CONFIRM'  => 'Delete this field? This also deletes every stored value for it, for every player, character, and ticket. This cannot be undone.',
	'PROFILE_FIELD_NOT_FOUND'       => 'That field could not be found.',
	'PROFILE_INVALID_FIELD_TYPE'    => 'That is not a recognized field type.',
	'PROFILE_INVALID_ENFORCEMENT'   => 'That is not a recognized enforcement timing.',

	'PROFILE_FIELD_TYPE'            => 'Field type',
	'PROFILE_FIELD_OPTIONS'         => 'Choices',
	'PROFILE_FIELD_OPTIONS_HINT'    => 'One choice per line. Only used for Select and Multi-select field types.',
	'PROFILE_SECTION'               => 'Section',
	'PROFILE_UNGROUPED'             => 'Ungrouped',
	'PROFILE_APPLIES_TO'            => 'Applies to',
	'PROFILE_APPLIES_PLAYER'        => 'Player',
	'PROFILE_APPLIES_CHARACTER'     => 'Character',
	'PROFILE_APPLIES_BOTH'          => 'Both',
	'PROFILE_REQUIRED'              => 'Required',
	'PROFILE_ENFORCEMENT'           => 'Enforce required at',
	'PROFILE_ENFORCE_CREATION'      => 'Creation',
	'PROFILE_ENFORCE_APPROVAL'      => 'Approval',
	'PROFILE_ENFORCE_BOTH'          => 'Both',
	'PROFILE_ENFORCEMENT_HINT'      => 'When a required field actually gets checked.',
	'PROFILE_SEARCHABLE'            => 'Searchable',
	'PROFILE_SEARCHABLE_HINT'       => 'Makes this field usable as a roster/ticket filter.',
	'PROFILE_SHOW_ON_ROSTER'        => 'Show on roster listing',
	'PROFILE_SHOW_ON_ROSTER_HINT'   => 'Appears in the compact roster list, not just the full character profile page.',
	'PROFILE_SHOW_IN_SHOWCASE'      => 'Show in player showcase hover',
	'PROFILE_SHOW_IN_SHOWCASE_HINT' => 'Appears in the hover/reveal on a player\'s character showcase grid.',
	'PROFILE_SORT_ORDER'            => 'Order',
	'PROFILE_SAVE_ORDER'            => 'Save order',
	'PROFILE_ORDER_UPDATED'         => 'Order updated.',

	'ACP_GEM_SETTINGS'              => 'Settings',
	'ACP_GEM_SETTINGS_SAVED'        => 'Settings saved.',
	'GEM_REQUIRE_APPROVAL'          => 'Require staff approval for new characters',
	'GEM_REQUIRE_APPROVAL_HINT'     => 'When off, new characters go live immediately. When on, they land as pending until reviewed via the Ticketing System.',
	'GEM_MAX_CHARACTERS'            => 'Max characters per player',
	'GEM_MAX_CHARACTERS_HINT'       => '0 = unlimited.',
	'GEM_SELF_UNARCHIVE'            => 'Players may unarchive their own characters',
	'GEM_SELF_UNARCHIVE_HINT'       => 'When off, unarchiving a character requires staff action instead.',
));
