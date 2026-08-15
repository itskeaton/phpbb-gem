<?php
/**
 * Gem - Connections UCP language file (en)
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
	'UCP_CAT_GEM'            => 'Gem',
	'UCP_CONNECTIONS'        => 'Connections',
	'UCP_CONNECTIONS_MANAGE' => 'My Connections',

	'GEM_ADD_CONNECTION'     => 'Add connection',
	'GEM_TARGET_CHARACTER'   => 'Character name',
	'GEM_TARGET_CHARACTER_HINT' => 'The exact name of the character you want to connect to.',
	'GEM_TARGET_CHARACTER_NOT_FOUND' => 'No active character named "%s" was found.',
	'GEM_CANNOT_CONNECT_SELF' => 'A character can\'t be connected to itself.',
	'GEM_CATEGORY_NAME'      => 'Category',
	'GEM_CATEGORY_REQUIRED'  => 'A category is required.',
	'GEM_DESCRIPTION'        => 'Description',
	'GEM_DESCRIPTION_HINT'   => 'Optional - how does your character see this connection?',
	'GEM_TARGET_NAME_REQUIRED' => 'A character name is required.',
	'GEM_CONNECTION_ADDED'   => 'Connection added.',
	'GEM_CONNECTION_DELETED' => 'Connection deleted.',
	'GEM_CONNECTION_NOT_FOUND' => 'That connection could not be found.',
));
