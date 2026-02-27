<?php if (! defined('ABSPATH')) exit; // Exit if accessed directly
class Front_Conf_Map_Form_LC_HC_MVC extends _HC_MVC
{
	public function inputs()
	{
		$return = array();

		$app_settings = $this->app->make('/app/settings');

		$this_field_pname = 'front_map:advanced';
		$this_advanced = $app_settings->get($this_field_pname);

		if( $this_advanced ){
			$this_field_pname = 'front_map:template';
			$return[ $this_field_pname ] = $this->app->make('/form/textarea')
				->set_rows( 14 )
				;
		}
		else {
			$p = $this->app->make('/locations/presenter');
			$fields = $p->fields_labels();

			foreach( $fields as $fn => $flabel ){
				$checkboxes = array( 'show', 'w_label' );
				foreach( $checkboxes as $ch ){
					$this_field_pname = 'front_map:' . $fn  . ':' . $ch;
					$this_field_conf = $app_settings->get($this_field_pname);

					if( ($this_field_conf === TRUE) OR ($this_field_conf === FALSE) ){
					}
					else {
						$return[ $this_field_pname ] = 
							$this->app->make('/form/checkbox')
						;
					}
				}
			}
		}

		$return['front_map:linktolist'] = array(
			'input'	=> $this->app->make('/form/text'),
			'label'	=> __('More info link label', 'locatoraid'),
			'help'	=> __('On click it will scroll to the results list. Leave blank to hide.', 'locatoraid'),
        );


		// $return['front_map:linktolist'] = $this->app->make('/form/text');

		return $return;
	}
}