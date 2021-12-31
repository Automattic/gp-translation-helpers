<?php
/**
 * The template part for the comments and comment form on an original discussion
 */
?>
<div class="discussion-wrapper">
	<?php if ( $number = count( $comments ) ) : ?>
		<h6><?php printf( _n( '%s Comment', '%s Comments', $number ), number_format_i18n( $number ) ); ?>
		<?php if ( $locale_slug ) : ?>
			(<?php echo esc_html( $locale_slug )?>)		
			<span class="comments-selector">
				<a href="#" class="active-link" data-selector="all">Show all</a> | <a href="#" data-selector="<?php echo esc_attr( $locale_slug );?>"><?php echo esc_html( $locale_slug )?> only</a>
			</span>
		<?php endif; ?>
		</h6>
	<?php endif; ?>
	<ul class="discussion-list">
		<?php
		wp_list_comments( array(
			'style'       => 'ul',
			'type'       => 'comment',
			'callback' => 'gth_discussion_callback',
			'translation_id' => $translation_id,
			'locale_slug' => $locale_slug,
			'original_permalink' => $original_permalink,
		), $comments );
		?>
	</ul><!-- .discussion-list -->
	<?php
	if ( is_user_logged_in() ) {
		comment_form( $args = array(
			'title_reply'          => __( 'Discuss this string' ),
			'title_reply_to'       => __( 'Reply to %s' ),
			'title_reply_before'   => '<h5 id="reply-title" class="discuss-title">',
			'title_reply_after'    => '</h5>',
			'id_form'              => 'commentform-' . $post_id,
			'cancel_reply_link'     => "<span></span>",
			'comment_notes_after'  => implode( "\n",
				array(
					'<input type="hidden" name="comment_locale" value="' . esc_attr( $locale_slug ) . '" />',
					'<input type="hidden" name="translation_id" value="' . esc_attr( $translation_id ) . '" />',
					'<input type="hidden" name="redirect_to" value="' . esc_url( $original_permalink ) . '" />',
				) ),
		), $post_id);
	} else {
		echo sprintf( __( 'You have to be <a href="%s">logged in</a> to comment.' ), wp_login_url() );
	}
	
	?>
</div><!-- .discussion-wrapper -->
