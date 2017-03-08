<?php

class Helper_History extends GP_Translation_Helper {

	public $priority = 0;
	public $title = 'History';
	public $has_async_content = true;

	function get_async_output() {
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $this->data['project_id'], $this->data['set_slug'], $this->data['locale_slug'] );
		if ( ! $translation_set ) {
			return;
		}

		$translations  = GP::$translation->find_many_no_map(
			array(
				'translation_set_id' => $translation_set->id,
				'original_id' => $this->data['original_id'],
			),
			'id DESC'
		);

		if ( $translations ) {
			$output = '<table>';
			$output .= '<thead>';
			$output .= '<tr><th>date</th><th>translation</th>';
			$output .= '</thead>';
			foreach ( $translations as $key => $translation ) {
				$output .= sprintf( '<tr class="preview status-%s"><td>%s</td><td>%s</td></tr>', esc_attr( $translation->status ), $translation->date_modified, esc_html( $translation->translation_0 ) );
			}
		}
		return $output;
	}

	function get_output() {
	}
}
