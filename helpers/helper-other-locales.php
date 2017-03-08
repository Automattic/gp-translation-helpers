<?php

class Helper_Other_Locales extends GP_Translation_Helper {

	public $priority = 3;
	public $title = 'In other locales';
	public $has_async_content = true;

	function get_async_content() {
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $this->data['project_id'], $this->data['set_slug'], $this->data['locale_slug'] );
		if ( ! $translation_set ) {
			return;
		}

		$translations  = GP::$translation->find_many_no_map( array( 'status' => 'current', 'original_id' => $this->data['original_id'] ) );
		foreach ( $translations as $key => $translation ) {
			if ( $translation->translation_set_id === $translation_set->id ) {
				unset( $translations[ $key ] );
			}
		}

		return $translations;
	}

	function async_output_callback( $translations ) {
		$output = '<ul>';
		foreach ( $translations as $translation ) {
			$_set = GP::$translation_set->get( $translation->translation_set_id );
			$output .= sprintf( '<li> <em>%s</em>: %s</li>', $_set->locale, $translation->translation_0 );
		}
		$output .= '</ul>';
		return $output;
	}

	function empty_content() {
		return 'No other locales have translated this string yet.';
	}
}
