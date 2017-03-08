<?php
class GP_Translation_Helper {

	public final function __construct() {
		$required_properties = array(
			'title',
		);

		foreach ( $required_properties as $prop ) {
			if ( ! isset( $this->{$prop} ) ) {
				throw new LogicException( get_class( $this ) . ' must have a property ' . $prop );
			}
		}

		if ( method_exists( $this, 'after_constructor' ) ) {
			$this->after_constructor();
		}
	}

	public function init( $args ) {
		$this->data = $args;
	}

	public function get_priority() {
		return isset( $this->priority ) ? $this->priority : 1;
	}

	public function has_async_content() {
		return isset( $this->has_async_content ) ? $this->has_async_content : false;
	}

	public function get_div_classname() {
		if ( isset( $this->classname ) ) {
			return $this->classname;
		}

		return sanitize_html_class( str_replace( '_' , '-', strtolower( get_class( $this ) ) ), 'default-translation-helper' );
	}

	public function get_div_id() {
		return $this->get_div_classname() . '-' . $this->data['original_id'];
	}

	public function get_tab_title() {
		return $this->title;
	}

	public function get_initial_output() {
		return $this->get_output();
	}

	public function activate() {
		return true;
	}

	public function set_count( $list ) {
		$list = (array) $list;
		$this->count = count( $list );
	}

	public function get_count() {
		return isset( $this->count ) ? $this->count : 0;
	}

	public function empty_content() {
		return 'No results found.';
	}

	public function async_output_callback( $items ) {
		$output = '<ul>';
		foreach ( $items as $item ) {
			$output .= '<li>' . $item . '</li>';
		}
		$output .= '</ul>';
		return $output;
	}

	public function get_async_output() {
		$items = $this->get_async_content();
		$this->set_count( $items );

		if ( ! $items ) {
			return $this->empty_content();

		}

		$output = $this->async_output_callback( $items );
		return $output;
	}

	public function get_output() {
		return '<div class="loading">Loading&hellip;</div>';
	}

	public function get_css() {
		return;
	}
}