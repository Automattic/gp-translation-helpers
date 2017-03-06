<?php
abstract class GP_Translation_Helper {

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

	public function get_initial_output() {

		if ( ! $this->is_active() ) {
			return;

		}

		$output = '<h4>' . esc_html( $this->title ) . '</h4>';
		$output .= sprintf( '<div class="%s helper" id="%s">', esc_attr( $this->get_div_classname() ), esc_attr( $this->get_div_id() ) );
		$output .= $this->get_output();
		$output .= '</div>';

		return $output;
	}

	public function is_active() {
		return true;
	}

	abstract public function get_output();
}