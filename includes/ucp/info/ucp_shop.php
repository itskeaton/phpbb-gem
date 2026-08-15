<?php
/**
 * Gem - Shop UCP module info
 */

class ucp_shop_info
{
	function module()
	{
		return array(
			'filename' => 'ucp_shop',
			'title'    => 'UCP_SHOP',
			'version'  => '1.0.0',
			'modes'    => array(
				'shop' => array(
					'title' => 'UCP_SHOP_BROWSE',
					'auth'  => '',
					'cat'   => array('UCP_CAT_GEM'),
				),
			),
		);
	}
}
