<?php if (! defined('ABSPATH')) exit; // Exit if accessed directly
$config['after']['/locations/presenter->fields'][] = function( $app, $ret )
{
	$ret['directions'] = __('Directions', 'locatoraid');
	return $ret;
};

$config['after']['/locations/presenter->present_front'][] = function( $app, $ret, $search, $search_coordinates )
{
	if( ! ($ret['latitude'] && $ret['longitude']) ){
		return $ret;
	}

	if( ( ($ret['latitude'] == -1) OR ($ret['longitude'] == -1) ) ){
		return $ret;
	}

	if( ! $search_coordinates ){
		return $ret;
	}
	if( ! is_array($search_coordinates) ){
		return $ret;
	}

	$search_lat = array_shift( $search_coordinates );
	$search_lng = array_shift( $search_coordinates );
	if( ! ($search_lat && $search_lng) ){
		return $ret;
	}

	$app_settings = $app->make('/app/settings');

	$this_pname = 'fields:directions:use';
	$this_pname_config = $app_settings->get($this_pname);
	if( ! $this_pname_config ){
		return $ret;
	}

	$this_pname = 'fields:directions:label';
	$this_label = $app_settings->get($this_pname);
	$this_label = strlen($this_label) ? $this_label : __('Directions', 'locatoraid');

	$link_args = array(
		'class'			=> 'lpr-directions',
		'href'			=> '#',
		'data-to-lat'	=> $ret['latitude'],
		'data-to-lng'	=> $ret['longitude'],
		'data-from-lat'	=> $search_lat,
		'data-from-lng'	=> $search_lng,
		);

	$link_view = '<a';
	foreach( $link_args as $k => $v ){
		$link_view .= ' ' . $k . '="' . $v . '"';
	}

	$link_view .= '>';
	$link_view .= $this_label;
	$link_view .= '</a>';

	$ret['directions'] = $link_view;
	return $ret;
};
