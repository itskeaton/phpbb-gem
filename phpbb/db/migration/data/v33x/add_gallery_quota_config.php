<?php
/**
 * Gem - gallery image quota, ACP-configurable.
 *
 * gem_gallery_quota: max images per character, TOTAL across both albums
 * combined (sidebar + misc), not per-album. That's a default pick, not a
 * spec decision - the original design note left this genuinely open
 * ("per-album or total-per-character?"). If per-album limits are wanted
 * instead, this becomes two config keys instead of one - flag it if the
 * total-per-character default doesn't match what you actually want.
 *
 * 0 = unlimited.
 */

namespace phpbb\db\migration\data\v33x;

class add_gallery_quota_config extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->config->offsetExists('gem_gallery_quota');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\add_post_sidebar_image_link');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('gem_gallery_quota', 0)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('gem_gallery_quota')),
		);
	}
}
