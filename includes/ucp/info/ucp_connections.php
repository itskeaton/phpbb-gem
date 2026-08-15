<?php
/**
 * Gem - Connections UCP module info
 */

class ucp_connections_info
{
	function module()
	{
		return array(
			'filename' => 'ucp_connections',
			'title'    => 'UCP_CONNECTIONS',
			'version'  => '1.0.0',
			'modes'    => array(
				'connections' => array(
					'title' => 'UCP_CONNECTIONS_MANAGE',
					'auth'  => '',
					'cat'   => array('UCP_CAT_GEM'),
				),
			),
		);
	}
}
