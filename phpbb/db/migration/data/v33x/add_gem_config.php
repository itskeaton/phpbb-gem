<?php
/**
 * Gem - core config settings
 *
 *   gem_require_approval  bool  - new characters land as 'pending' instead of 'active'
 *   gem_max_characters    int   - 0 = unlimited
 *   gem_self_unarchive    bool  - can players unarchive their own characters without staff?
 */

namespace phpbb\db\migration\data\v33x;

class add_gem_config extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->config->offsetExists('gem_require_approval');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_character_status_log');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('gem_require_approval', 0)),
			array('config.add', array('gem_max_characters', 0)),
			array('config.add', array('gem_self_unarchive', 1)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('gem_require_approval')),
			array('config.remove', array('gem_max_characters')),
			array('config.remove', array('gem_self_unarchive')),
		);
	}
}
