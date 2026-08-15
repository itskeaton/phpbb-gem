<?php
/**
 * Gem - Wanted Character ads
 *
 * Only truly structural fields are native columns here - the "identity/
 * media/moderation" data every ad needs regardless of admin configuration.
 * Everything vocab-like (FC status, age range, social class, wanted type,
 * gender, associated groups, etc.) lives in phpbb_profile_values with
 * owner_type = 4, exactly like ticket category fields, rendered/managed
 * through the same Dynamic Profile Fields engine.
 *
 * character_id replaces TGG's free-text "connected_to" - the ad is posted
 * AS one of the player's own characters, not just associated by name.
 */

namespace phpbb\db\migration\data\v33x;

class add_wanted_characters extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'wanted_characters');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_wanted_umbrella_tags');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'wanted_characters' => array(
					'COLUMNS' => array(
						'ad_id'                    => array('UINT', NULL, 'auto_increment'),
						'character_id'             => array('UINT', 0), // the poster's own character this ad is connected to
						'char_name'                => array('VCHAR:255', ''), // the wanted character's name - the ad's title/identity
						'image_url'                => array('VCHAR:255', ''),
						'blurb'                    => array('MTEXT', ''),
						'signature_bbcode_uid'      => array('VCHAR:8', ''),
						'signature_bbcode_bitfield' => array('VCHAR:255', ''),
						'is_reserved'              => array('BOOL', 0),
						'ad_status'                => array('BOOL', 1), // 1 = visible, 0 = hidden
						'created_at'               => array('UINT', 0),
						'updated_at'               => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'ad_id',
					'KEYS' => array(
						'character_id' => array('INDEX', 'character_id'),
						'ad_status'    => array('INDEX', 'ad_status'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'wanted_characters',
			),
		);
	}
}
