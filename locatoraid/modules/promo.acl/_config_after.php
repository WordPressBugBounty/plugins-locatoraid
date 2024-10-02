<?php if (! defined('ABSPATH')) exit; // Exit if accessed directly
$config['after']['/root/link'][] = function( $app, $ret )
{
	if( ! $ret ){
		return $ret;
	}

	// check module
	$module = 'promo';
	if( ($module != $ret) && (substr($ret, 0, strlen($module . '/')) != $module . '/') ){
		return $ret;
	}

	// check admin
	$logged_in = $app->make('/auth/lib')
		->logged_in()
		;
	$is_admin = $app->make('/acl/roles')
		->has_role( $logged_in, 'admin')
		;
	if( $is_admin ){
		return $ret;
	}

	$ret = false;
	return $ret;
};