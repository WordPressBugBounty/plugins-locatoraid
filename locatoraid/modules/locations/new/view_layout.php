<?php if (! defined('ABSPATH')) exit; // Exit if accessed directly
class Locations_New_View_Layout_LC_HC_MVC extends _HC_MVC
{
	public function header()
	{
		$ret = __('Add New Location', 'locatoraid');
		return $ret;
	}

	public function menubar()
	{
		$ret = array();

	// LIST
		$ret['list'] = $this->app->make('/html/ahref')
			->to('/locations')
			->add( $this->app->make('/html/icon')->icon('arrow-left') )
			->add( __('Locations', 'locatoraid') )
			;

		return $ret;
	}

	public function render( $content )
	{
		$this->app->make('/layout/top-menu')
			->set_current( 'locations' )
			;

		$header = $this->header();
		$menubar = $this->menubar();

		$out = $this->app->make('/layout/view/content-header-menubar')
			->set_content( $content )
			->set_header( $header )
			->set_menubar( $menubar )
			;

		return $out;
	}
}