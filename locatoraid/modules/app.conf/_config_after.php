<?php if (! defined('ABSPATH')) exit; // Exit if accessed directly
$config['after']['/conf/view/layout->tabs'][] = function( $app, $ret )
{
	$ret['app'] = array( 'app.conf', __('Configuration', 'locatoraid') );
	return $ret;
};
