<?php
abstract class GP_Translation_Helper {

	public function init( $args ) {
		$this->data = $args;
	}

	public function get_priority() {
		return $this->priority;
	}

	abstract public function get_output();
}