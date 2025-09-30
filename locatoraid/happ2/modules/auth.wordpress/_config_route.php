<?php if (! defined('ABSPATH')) exit; // Exit if accessed directly
$auth_wordpress_login_redirect = function( $app ){
	$redirect_to = wp_login_url();
	return $app->make('/http/view/response')
		->set_redirect($redirect_to) 
		;
};

$config['route']['auth/login'] = $auth_wordpress_login_redirect;
$config['route']['login'] = $auth_wordpress_login_redirect;
if (is_admin()){
    $config['route'][''] = '/locations/index/controller';
} else {
    $config['route'][''] = $auth_wordpress_login_redirect;
}
