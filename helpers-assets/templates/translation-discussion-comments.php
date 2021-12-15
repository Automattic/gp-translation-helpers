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

			<a href="../..">Show all</a>
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
		), $comments );
		?>
	</ul><!-- .discussion-list -->
	<?php
	comment_form( $args = array(
		'title_reply'          => __( 'Discuss this string' ),
		'title_reply_to'       => __( 'Reply to %s' ),
		'title_reply_before'   => '<h6 id="reply-title" class="discuss-title">',
		'title_reply_after'    => '</h6>',
		'id_form'              => 'commentform-' . $post_id,
		'comment_notes_after'  => implode( "\n",
			array(
				'<input type="hidden" name="comment_locale" value="' . esc_attr( $locale_slug ) . '" />',
				'<input type="hidden" name="translation_id" value="' . esc_attr( $translation_id ) . '" />',
				'<input type="hidden" name="redirect_to" value="' . esc_url( $original_permalink ) . '" />',
			) ),
	), $post_id);
	?>
</div><!-- .discussion-wrapper -->
