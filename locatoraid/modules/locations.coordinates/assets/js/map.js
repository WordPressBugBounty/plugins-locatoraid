(function($) {

jQuery(document).on('hc2-gmaps-loaded', function()
{
	var map_div = "hclc_map";

	var $map = jQuery('#' + map_div);
	if( ! $map.data('latitude') ){
		return false;
	}

	var map = hc2_init_gmaps( map_div );

	map.setOptions({ zoomControl: true });

	var can_edit = $map.data('edit');
	var custom_icon = $map.data('icon');

	var location_position = new google.maps.LatLng( 
		$map.data('latitude'),
		$map.data('longitude')
		);

	map.setCenter( location_position );

	var markerConf = {
		map: map,
		position: location_position,
		draggable: false,
	};

	if( map.useAdvancedMarkers ){
		var marker = new google.maps.marker.AdvancedMarkerElement( markerConf );
	}
	else {
		var marker = new google.maps.Marker({
			map: map,
			position: location_position,
			draggable: false,
			visible: true,
		});
	}

	if( can_edit ){
		marker.gmpDraggable = true;
	}

	if( custom_icon ){
		const markerCustomIcon = document.createElement('img');
		markerCustomIcon.src = custom_icon;
		marker.content = markerCustomIcon;
	}

	function update_marker_position( latLng )
	{
		$map.closest('.hcj2-container').find('input[name\$=latitude]').val( latLng.lat );
		$map.closest('.hcj2-container').find('input[name\$=longitude]').val( latLng.lng );
	}

	if( can_edit ){
		google.maps.event.addListener( marker, 'drag', function() {
			update_marker_position( marker.position );
		});

		var try_address = $map.data('address');
		hc2_geocode(
			try_address,
			handle_geocode_result
		);
	}

	function handle_geocode_result( success, results, return_status )
	{
		if( success || (return_status == google.maps.GeocoderStatus.ZERO_RESULTS) ){
			var this_found = false;
			var this_coord = {};

			switch( return_status ){
				case google.maps.GeocoderStatus.ZERO_RESULTS:
					this_found = true;
					this_coord = {
						lat: -1,
						lng: -1
					};
					break;

				case google.maps.GeocoderStatus.OK:
					this_found = true;
					this_coord = {
						lat: results.lat,
						lng: results.lng
					};
					break;

				default:
					break;
			}

			if( this_found ){
				var newPos = new google.maps.LatLng( results.lat, results.lng );

				map.setCenter( newPos );
				marker.position = newPos;
				update_marker_position( newPos );
			}
			else {
				var errorMsg = 'Geocoding error: ' + return_status;
				$map.append( errorMsg  + '<br/>' );
			}
		}
		else {
			var errorMsg = 'Geocoding error: ' + return_status;
			$map.append( errorMsg  + '<br/>' );
		}
	}
});

}());
