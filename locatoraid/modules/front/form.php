<?php if (! defined('ABSPATH')) exit; // Exit if accessed directly
class Front_Form_LC_HC_MVC extends _HC_MVC
{
	public function inputs()
	{
		$ret = array();

		$app_settings = $this->app->make('/app/settings');
		$label = $app_settings->get('front_text:search_field');
		if( $label === NULL ){
			$label = __('Address or Zip Code', 'locatoraid');
		}
		else {
			$label = __($label, 'locatoraid');
		}

		$ret['search'] = $this->app->make('/form/text')
			->set_label( $label )
			;

		$ret = $this->app
			->after( $this, $ret )
			;

		return $ret;
	}
}