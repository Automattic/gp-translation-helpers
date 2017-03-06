<?php

class Helper_Translation_Memory extends GP_Translation_Helper {

	public $priority = 1;
	public $title = 'Translation Memory';
	public $has_async_content = true;

	function get_async_output() {
		if ( ! class_exists( 'GP_Translation_Memory' ) ) {
			return false;
		}

		$suggestions = GP_Translation_Memory::suggestions_output_for_original_id( $this->data['original_id'], $this->data['locale_slug'], false );
		return $suggestions;
	}

	function get_output() {
		return '<div class="loading">Loading...</div>';
	}
}
