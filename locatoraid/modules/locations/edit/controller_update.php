<?php if (! defined('ABSPATH')) exit; // Exit if accessed directly
class Locations_Edit_Controller_Update_LC_HC_MVC extends _HC_MVC
{
	public function execute( $id )
	{
		$post = $this->app->make('/input/lib')->post();

		$inputs = $this->app->make('/locations/edit/form')
			->inputs()
			;
		$helper = $this->app->make('/form/helper');

		list( $values, $errors ) = $helper->grab( $inputs, $post );

		if( $errors ){
			return $this->app->make('/http/view/response')
				->set_redirect('-referrer-') 
				;
		}

		$values['id'] = $id;

		$cm = $this->app->make('/commands/manager');

	// check if address changes then redirect to coordinates
		$addressFields = array( 'street1', 'street2', 'city', 'state', 'zip', 'country' );

		$args = array();
		$args[] = $id;
		$args[] = array('select', array_merge(array('id'), $addressFields));
		$command = $this->app->make('/locations/commands/read');
		$current = $command->execute( $args );

		$addressChanged = false;
		foreach( $values as $k => $v ){
			if( ! array_key_exists($k, $current) ){
				continue;
			}

			if( $v != $current[$k] ){
				$addressChanged = true;
				break;
			}
		}

	// update
		$command = $this->app->make('/locations/commands/update');
		$command
			->execute( $id, $values )
			;

		$errors = $cm->errors( $command );
		if( $errors ){
			$session = $this->app->make('/session/lib');
			$session
				->set_flashdata('error', $errors)
				;
			return $this->app->make('/http/view/response')
				->set_redirect('-referrer-') 
				;
		}

		if( $addressChanged ){
			$to = $this->app->make('/http/uri')
				->url( '/locations.coordinates/' . $id )
				;
		}
		else {
			$to = '-referrer-';
		}

	// OK
		return $this->app->make('/http/view/response')
			->set_redirect( $to ) 
			;
	}
}