<?php

class Helper_User_Info extends GP_Translation_Helper {

	public $priority = 0;
	public $title = 'User stats';
	public $has_async_content = true;

	public $translation = false;


	function init( $args ) {
		parent::init( $args );

		if (  isset( $this->data['translation_id'] ) ) {
			$this->translation = GP::$translation->get( $this->data['translation_id'] );
		}
	}

	function get_async_output() {

		$translations = GP::$translation->find_many_no_map( array( 'user_id' => $this->translation->user_id  ) );
		$translations_by_status = array();
		foreach ( $translations as $translation ) {
			if ( isset( $translations_by_status[ $translation->status ] ) ) {
				$translations_by_status[ $translation->status ]++;
			} else {
				$translations_by_status[ $translation->status ] = 1;
			}
		}

		$total = count( $translations );

		return sprintf( '%d total translations. %d%% accepted, %d%% rejected, %d%% waiting', $total , number_format( $translations_by_status['current'] * 100 / $total ), number_format( $translations_by_status['rejected'] * 100 / $total ), number_format( $translations_by_status['waiting'] * 100 / $total ) );
	}

	function get_output() {
	}

	function is_active() {
		return $this->translation;
	}
}
