<?php
/**
 * Gem - Player/Character Split
 *
 * Schema migration. Creates:
 *   - phpbb_characters         one row per character, owned by a master (player) user_id
 *   - phpbb_characters_active  one row per player, tracking their persistent "posting as" default
 *
 * Gem is a fresh-install fork - every character here is created through
 * normal UCP flow. legacy_user_id and character_colour exist for the case
 * where a specific site adopting Gem wants to manually convert an existing
 * standalone account into a character (a per-site admin action, not an
 * automated import tool - Gem has no bundled migration/import tooling).
 *
 * character.status values (kept as plain integers, not an enum, to match
 * phpBB convention):
 *   1 = active       - in the player's live roster, selectable to post as
 *   2 = archived      - released back to the pool by the player (name
 *                       available for reapplication). Non-permanent.
 *   3 = deactivated    - the whole owning account has gone dormant, not a
 *                       per-character release. Derived from the master
 *                       account's own phpBB active/inactive state.
 *   4 = pending        - new character awaiting staff approval (only used
 *                       if the "require staff approval" ACP setting is on)
 *   5 = declined        - staff rejected the application. Reason logged,
 *                       staff-only visibility (see Ticketing System).
 *
 * legacy_user_id preserves the character's *original* phpbb_users.user_id,
 * for sites that manually convert a pre-existing account into a character.
 * Also required if/when phpbb_posts.poster_id history ever needs migrating
 * onto the new model - do not drop this column even after posting-side
 * integration is done.
 *
 * character_colour is a one-time snapshot of user_colour (phpBB's
 * usergroup-based name-colour hex, no leading #), taken whenever a
 * character is created or converted. It does NOT live-track anything
 * afterward - once a character exists here, its display colour is
 * independent of any account's group changes.
 */

namespace phpbb\db\migration\data\v33x;

class add_player_character_split extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'characters');
	}

	static public function depends_on()
	{
		// Adjust to the latest core migration in your tree before running.
		return array('\phpbb\db\migration\data\v33x\v330');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'characters' => array(
					'COLUMNS' => array(
						'character_id'     => array('UINT', NULL, 'auto_increment'),
						'user_id'          => array('UINT', 0),   // FK -> phpbb_users.user_id (the master/player)
						'legacy_user_id'   => array('UINT', 0),   // set only if manually converted from a pre-existing standalone account
						'character_name'   => array('VCHAR:255', ''),
						'avatar'           => array('VCHAR:255', ''),
						'avatar_type'      => array('VCHAR:255', ''),
						'avatar_width'     => array('USINT', 0),
						'avatar_height'    => array('USINT', 0),
						'signature'        => array('MTEXT', ''),
						'character_colour' => array('VCHAR:6', ''),
						'status'           => array('TINT:2', 1),
						'created_at'       => array('UINT', 0),
						'updated_at'       => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'character_id',
					'KEYS' => array(
						'user_id'        => array('INDEX', 'user_id'),
						'legacy_user_id' => array('INDEX', 'legacy_user_id'),
						'status'         => array('INDEX', 'status'),
					),
				),
				$this->table_prefix . 'characters_active' => array(
					'COLUMNS' => array(
						'user_id'      => array('UINT', 0),   // FK -> phpbb_users.user_id, one row per player
						'character_id' => array('UINT', 0),   // FK -> phpbb_characters.character_id
						'updated_at'   => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'user_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'characters_active',
				$this->table_prefix . 'characters',
			),
		);
	}
}
