<?php
/**
 * Gem - registers the "Gem" ACP category (parent for all Gem admin modules
 * going forward - Tickets, Characters, etc. will nest under it too) and the
 * Profile Fields module itself, with its two modes.
 */

namespace phpbb\db\migration\data\v33x;

class add_profile_fields_acp_module extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_character_signature_fields');
	}

	public function update_data()
	{
		return array(
			array('module.add', array('acp', 0, 'ACP_CAT_GEM')),
			array('module.add', array('acp', 'ACP_CAT_GEM', array(
				'module_basename' => 'acp_profile_fields',
				'modes'           => array('fields', 'sections', 'settings'),
			))),
		);
	}

	public function revert_data()
	{
		return array(
			array('module.remove', array('acp', 'ACP_CAT_GEM', array(
				'module_basename' => 'acp_profile_fields',
				'modes'           => array('fields', 'sections', 'settings'),
			))),
			array('module.remove', array('acp', 0, 'ACP_CAT_GEM')),
		);
	}
}
