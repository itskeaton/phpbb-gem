<?php
/**
 * Gem - Wanted Plots
 *
 * is_adult_content stays a native column (not a Dynamic Field) - it's a
 * universal safety/moderation flag, not admin-renameable vocab, same
 * reasoning as keeping IP address handling untouched elsewhere in this
 * build. linked_ad_id points at the SAME character's own wanted_characters
 * ad (ownership validated by character_id match at save time, not just
 * "same player" - Gem's character-level granularity makes this stricter
 * than TGG's original account-level check).
 */

namespace phpbb\db\migration\data\v33x;

class add_wanted_plots extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'wanted_plots');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_wanted_characters');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'wanted_plots' => array(
					'COLUMNS' => array(
						'ad_id'                    => array('UINT', NULL, 'auto_increment'),
						'character_id'             => array('UINT', 0),
						'title'                    => array('VCHAR:255', ''),
						'teaser'                   => array('VCHAR:300', ''),
						'linked_ad_id'             => array('UINT', 0), // 0 = standalone, else FK -> wanted_characters.ad_id
						'image_url'                => array('VCHAR:255', ''), // '' = fall back to posting character's default sidebar image
						'blurb'                    => array('MTEXT', ''),
						'signature_bbcode_uid'      => array('VCHAR:8', ''),
						'signature_bbcode_bitfield' => array('VCHAR:255', ''),
						'is_adult_content'         => array('BOOL', 0),
						'ad_status'                => array('BOOL', 1),
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
				$this->table_prefix . 'wanted_plots',
			),
		);
	}
}
