<?php
/**
 * Gem - Character Management UCP module info
 */

class ucp_characters_info
{
	function module()
	{
		return array(
			'filename' => 'ucp_characters',
			'title'    => 'UCP_CHARACTERS',
			'version'  => '1.0.0',
			'modes'    => array(
				'manage' => array(
					'title' => 'UCP_CHARACTERS_MANAGE',
					'auth'  => '', // any logged-in user - tighten to a custom permission later if needed
					'cat'   => array('UCP_CAT_GEM'),
				),
			),
		);
	}
}
