<?php

class Helper_Translation_Memory extends GP_Translation_Helper {

	public $priority = 1;

	function get_output() {
		if ( ! class_exists( 'GP_Route_Translation_Memory' ) ) {
			return false;
		}

		$tm_route = new GP_Route_Translation_Memory();
		$suggestions = $tm_route->suggestions_output_for_original_id( $this->data['original_id'], $this->data['locale_slug'] );

		return $suggestions;
	}
}
