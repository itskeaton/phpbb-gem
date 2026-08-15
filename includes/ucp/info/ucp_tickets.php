<?php
/**
 * Gem - Ticketing System UCP module info
 */

class ucp_tickets_info
{
	function module()
	{
		return array(
			'filename' => 'ucp_tickets',
			'title'    => 'UCP_TICKETS',
			'version'  => '1.0.0',
			'modes'    => array(
				'tickets' => array(
					'title' => 'UCP_TICKETS_MANAGE',
					'auth'  => '', // any logged-in user - category-level group restriction handled inside the controller
					'cat'   => array('UCP_CAT_GEM'),
				),
			),
		);
	}
}
