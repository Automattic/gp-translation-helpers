<?php

class Helper_History extends GP_Translation_Helper {

	public $priority = 2;
	public $title = 'History';
	public $has_async_content = true;

	function activate() {
		if ( ! $this->data['project_id'] || ! isset( $this->data['translation_set_slug'] ) || ! isset( $this->data['locale_slug'] ) ) {
			// Deactivate when translation set is available.
			return false;
		}

		return true;
	}

	function get_async_content() {
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $this->data['project_id'], $this->data['translation_set_slug'], $this->data['locale_slug'] );

		if ( ! $translation_set ) {
			return;
		}

		$translations  = GP::$translation->find_many_no_map(
			array(
				'translation_set_id' => $translation_set->id,
				'original_id' => $this->data['original_id'],
			)
		);

		usort( $translations, function ( $t1, $t2 ) {
			$cmp_prop_t1 = $t1->date_modified ?: $t1->date_added;
			$cmp_prop_t2 = $t2->date_modified ?: $t2->date_added;
			return $cmp_prop_t1 < $cmp_prop_t2;
		} );

		$this->set_count( $translations );

		return $translations;
	}

	function async_output_callback( $translations ) {
		if ( $translations ) {
			$output = '<table id="translation-history-table">';
			$output .= '<thead>';
			$output .= '<tr><th>Date</th><th>Translation</th><th>Added by</th><th>Last modified by</th>';
			$output .= '</thead>';

			foreach ( $translations as $key => $translation ) {
				$date_and_time = is_null( $translation->date_modified ) ? $translation->date_added : $translation->date_modified;
				$date_and_time = explode( ' ', $date_and_time );

				$user = get_userdata( $translation->user_id );
				$user_last_modified = get_userdata( $translation->user_id_last_modified );

				if ( ( '' == $translation->translation_1 ) && ( '' == $translation->translation_2 ) &&
				     ( '' == $translation->translation_3 ) && ( '' == $translation->translation_4 ) &&
				     ( '' == $translation->translation_5 ) ) {
						$output_translation = $translation->translation_0;
				} else {
					$output_translation = '<ul>';
					for ( $i = 0; $i <= 5; $i ++ ) {
						if ( '' != $translation->{'translation_' . $i} ) {
							$output_translation .= sprintf( '<li>%s</li>', esc_translation( $translation->{'translation_' . $i} ) );
						}
					}
					$output_translation .= '</ul>';
				}

				$output .= sprintf( '<tr class="preview status-%1$s"><td title="%2$s">%3$s</td><td>%4$s</td><td>%5$s</td><td>%6$s</td></tr>',
					esc_attr( $translation->status ),
					esc_attr( $translation->date_modified ?: $translation->date_added ) ,
					esc_html( $date_and_time[0] ) ,
					$output_translation,
					$user ? esc_html( $user->user_login ) : '&mdash;',
					$user_last_modified ? esc_html( $user_last_modified->user_login ) : '&mdash;'
				);
			}
		}
		return $output;
	}

	function empty_content() {
		return 'No translation history for this string';
	}
}
