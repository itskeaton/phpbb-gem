<?php
/**
 * Gem - Wanted Ads UCP module info
 */

class ucp_wanted_info
{
	function module()
	{
		return array(
			'filename' => 'ucp_wanted',
			'title'    => 'UCP_WANTED',
			'version'  => '1.0.0',
			'modes'    => array(
				'wanted' => array(
					'title' => 'UCP_WANTED_MANAGE',
					'auth'  => '',
					'cat'   => array('UCP_CAT_GEM'),
				),
			),
		);
	}
}
