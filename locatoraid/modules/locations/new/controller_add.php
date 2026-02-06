<?php if (! defined('ABSPATH')) exit; // Exit if accessed directly
class Locations_New_Controller_Add_LC_HC_MVC extends _HC_MVC
{
	public function execute()
	{
		$post = $this->app->make('/input/lib')->post();

		$inputs = $this->app->make('/locations/form')->inputs();
        $nextOptions = array(
            '..' => __('View the new location', 'locatoraid'),
            '.' => __('Add another location', 'locatoraid')
        );
        $inputs['next'] = array(
            'input' => $this->app->make('/form/radio')->set_options($nextOptions),
            'label' => __('Next action', 'locatoraid'),
        );
        $inputs['latitude'] = array(
            'input' => $this->app->make('/form/text'),
            'label' => __('Latitude', 'locatoraid') . ' (' . __('Optional', 'locatoraid') . ')',
            );
        $inputs['longitude'] = array(
            'input' => $this->app->make('/form/text'),
            'label' => __('Longitude', 'locatoraid') . ' (' . __('Optional', 'locatoraid') . ')',
        );

		$helper = $this->app->make('/form/helper');

		list( $values, $errors ) = $helper->grab( $inputs, $post );

		if( $errors ){
			return $this->app->make('/http/view/response')
				->set_redirect('-referrer-') 
				;
		}

        $next = isset($values['next']) ? $values['next'] : '..';
        unset($values['next']);

		$cm = $this->app->make('/commands/manager');

		$command = $this->app->make('/locations/commands/create');
		$command
			->execute( $values )
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

		$results = $cm->results( $command );

	// OK
        if ('.' == $next) {
            $redirect_to = $this->app->make('/http/uri')
                ->url('/locations/new', array('noflashform' => 1))
                ;
            $session = $this->app->make('/session/lib');
            $session_values = $session->flashdata('form_values');
        } else {
            $redirect_to = $this->app->make('/http/uri')
                ->url('/locations/' . $results['id'] . '/edit')
                ;
        }

		return $this->app->make('/http/view/response')
			->set_redirect($redirect_to) 
			;
	}
}