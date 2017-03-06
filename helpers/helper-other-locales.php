<?php

class Helper_Other_Locales extends GP_Translation_Helper {

	public $priority = 5;
	public $title = 'In other locales';
	public $has_async_content = true;

	function get_async_output() {
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $this->data['project_id'], $this->data['set_slug'], $this->data['locale_slug'] );
		if ( ! $translation_set ) {
			return;
		}

		$translations  = GP::$translation->find_many_no_map( array( 'status' => 'current', 'original_id' => $this->data['original_id'] ) );
		if ( $translations ) {

			$output = '<ul>';
			foreach ( $translations as $key => $translation ) {
				if ( $translation->translation_set_id === $translation_set->id ) {
					continue;
				}

				$_set                  = GP::$translation_set->get( $translation->translation_set_id );

				$output .= sprintf( '<li> <em>%s</em>: %s</li>', $_set->locale, $translation->translation_0 );
			}
		}
		return $output;
	}

	function get_output() {
		return '<div class="loading">Loading...</div>';
	}
}
