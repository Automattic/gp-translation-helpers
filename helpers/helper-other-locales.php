<?php

class Helper_Other_Locales extends GP_Translation_Helper {

	public $priority = 3;
	public $title = 'Other locales';
	public $has_async_content = true;

	function activate() {
		if ( ! $this->data['project_id'] ) {
			return false;
		}

		if ( ! isset( $this->data['translation_set_slug'] ) || ! isset( $this->data['locale_slug'] ) ) {
			$this->title = 'Translations';
		}

		return true;
	}

	function get_async_content() {
		if ( ! $this->data['project_id'] ) {
			return;
		}
		$translation_set = null;
		if ( isset( $this->data['translation_set_slug'] ) && isset( $this->data['locale_slug'] )  ) {
			$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $this->data['project_id'], $this->data['translation_set_slug'], $this->data['locale_slug'] );
		}

		$translations  = GP::$translation->find_many_no_map( array( 'status' => 'current', 'original_id' => $this->data['original_id'] ) );
		$translations_by_locale = array();
		foreach ( $translations as $translation ) {
			$_set = GP::$translation_set->get( $translation->translation_set_id );
			if ( ! $_set || ( $translation_set && intval( $translation->translation_set_id ) === intval( $translation_set->id ) ) ) {
				continue;
			}
			$translations_by_locale[ $_set->locale ] = $translation;
		}

		ksort( $translations_by_locale );

		return $translations_by_locale;
	}

	function async_output_callback( $translations ) {
		$output = '<ul class="other-locales">';
		foreach ( $translations as $locale => $translation ) {
			if ( ( '' == $translation->translation_1 ) && ( '' == $translation->translation_2 ) &&
			     ( '' == $translation->translation_3 ) && ( '' == $translation->translation_4 ) &&
			     ( '' == $translation->translation_5 ) ) {
				$output .= sprintf( '<li><span class="locale unique">%s</span>%s</li>', $locale, esc_translation( $translation->translation_0 ) );
			} else {
				$output .= sprintf( '<li><span class="locale">%s</span>', $locale );
				$output .= '<ul>';
				for ( $i = 0; $i <= 5; $i ++ ) {
					if ( '' != $translation->{'translation_' . $i} ) {
						$output .= sprintf( '<li>%s</li>', esc_translation( $translation->{'translation_' . $i} ) );
					}
				}
				$output .= '</ul>';
				$output .= '</li>';
			}
		}
			$output .= '</ul>';
			return $output;
	}
	function empty_content() {
		esc_html( 'No other locales have translated this string yet.' );
	}

	function get_css() {
		return <<<CSS
	.other-locales {
		list-style: none;
	}
	ul.other-locales {
		padding-left: 0;
	}
	.other-locales li {
		clear:both;
	}
	ul.other-locales li {
		display: flex;
	}
	ul.other-locales li ul li {
		display: list-item;
		list-style: disc;
	}
	span.locale.unique {
		margin-right: 26px;
	}
	.other-locales .locale {
		display: inline-block;
		padding: 1px 6px 0 0;
		margin: 1px 6px 1px 0;
		background: #00DA12;
		width: 5em;
		text-align: right;
		float: left;
		color: #fff;
	}
CSS;
	}
}
