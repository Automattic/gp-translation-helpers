<?php

class Helper_Translation_Memory extends GP_Translation_Helper {

	public $priority = 1;
	public $title = 'Translation Memory';
	public $has_async_content = true;

	function get_async_content() {
		if ( ! class_exists( 'GP_Translation_Memory' ) ) {
			return false;
		}

		$original = GP::$original->get( $this->data['original_id'] );
		if ( ! $original ) {
			return false;
		}

		$suggestions = GP_Translation_Memory::get_suggestions( $original->id,  $original->singular, $this->data['locale_slug'] );
		return $suggestions;
	}

	function async_output_callback( $items ) {
		$output = '<ul class="suggestions">';
		foreach ( $items as $suggestion ) {
			$output .= '<li>';
			if ( $suggestion['diff'] ) {
				$output .= '<span class="score has-diff">';
				$output .= '<span class="original-diff">' . wp_kses_post( $suggestion['diff'] ) . '</span>';
			} else {
				$output .= '<span class="score">';
			}
			$output .= esc_html( number_format( 100 * $suggestion['similarity_score'] ) ) . '%</span>';
			$output .= '<span class="translation">' . esc_html( $suggestion['translation']['translation_0'] ) . '</span>';
			$output .= '<a class="copy-suggestion" href="#">copy this</a>';
			$output .= '</li>';
		}
		$output .= '</ul>';
		return $output;
	}

	function empty_content() {
		return 'No suggestions found!';
	}
}
