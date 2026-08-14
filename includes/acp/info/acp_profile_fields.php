<?php
/**
 * Gem - Profile Fields ACP module info
 */

class acp_profile_fields_info
{
	function module()
	{
		return array(
			'filename' => 'acp_profile_fields',
			'title'    => 'ACP_PROFILE_FIELDS',
			'version'  => '1.0.0',
			'modes'    => array(
				'fields' => array(
					'title' => 'ACP_PROFILE_FIELDS_MANAGE',
					'auth'  => 'acl_a_board',
					'cat'   => array('ACP_CAT_GEM'),
				),
				'sections' => array(
					'title' => 'ACP_PROFILE_SECTIONS_MANAGE',
					'auth'  => 'acl_a_board',
					'cat'   => array('ACP_CAT_GEM'),
				),
				'settings' => array(
					'title' => 'ACP_GEM_SETTINGS',
					'auth'  => 'acl_a_board',
					'cat'   => array('ACP_CAT_GEM'),
				),
			),
		);
	}
}
