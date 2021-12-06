<?php
// Plugin name: GP Translation Helpers


require_once __DIR__ . '/class-gp-translation-helpers.php';
require_once __DIR__ . '/class-gp-route-translation-helpers.php';

add_action( 'gp_init', array( 'GP_Translation_Helpers', 'init' ) );
add_action(
	'comment_post',
	function( $comment_ID ) {
		if ( gp_post( 'translation_id' ) ) {
			$translation_id = sanitize_text_field( gp_post( 'translation_id' ) );
			add_comment_meta( $comment_ID, 'translation_id', $translation_id );
		}
		if ( gp_post( 'comment_locale' ) ) {
			$comment_locale = sanitize_text_field( gp_post( 'comment_locale' ) );
			add_comment_meta( $comment_ID, 'locale', $comment_locale );
		}
	}
);
