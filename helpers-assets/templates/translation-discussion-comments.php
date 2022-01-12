<?php
/**
 * The template part for the comments and comment form on an original discussion
 */
?>
<div class="discussion-wrapper">
	<?php if ( $number = count( $comments ) ) : ?>
		<h6><?php printf( _n( '%s Comment', '%s Comments', $number ), number_format_i18n( $number ) ); ?>
		<?php if ( $locale_slug ) : ?>
			(<?php echo esc_html( $locale_slug ); ?>)
			<?php
			$countLocaleComments = 0;
			foreach ( $comments as $comment ) {
				$comment_locale = get_comment_meta( $comment->comment_ID, 'locale', true );
				if ( $locale_slug == $comment_locale ) {
					$countLocaleComments++;
				}
			}
			?>
						
			<span class="comments-selector">
				<a href="#" class="active-link" data-selector="all">Show all (<?php echo esc_html( $number ); ?>)</a> | <a href="#" data-selector="<?php echo esc_attr( $locale_slug ); ?>"><?php echo esc_html( $locale_slug ); ?> only (<?php echo esc_html( $countLocaleComments ); ?>)</a>
			</span>
		<?php endif; ?>
		</h6>
	<?php endif; ?>
	<ul class="discussion-list">
		<?php
		wp_list_comments(
			array(
				'style'              => 'ul',
				'type'               => 'comment',
				'callback'           => 'gth_discussion_callback',
				'translation_id'     => $translation_id,
				'locale_slug'        => $locale_slug,
				'original_permalink' => $original_permalink,
			),
			$comments
		);
		?>
	</ul><!-- .discussion-list -->
	<?php
	add_action(
		'comment_form_logged_in_after',
		function () use ( $locale_slug ) {
			$english_name_for_locale = '';
			$language_question       = '';

			if ( $locale_slug ) {
				$english_name_for_locale = GP_Locales::by_slug( $locale_slug )->english_name;
				$language_question       = '<option value="question">Question about translating to ' . esc_html( $english_name_for_locale ) . '</option>';
			}

			echo '<p class="comment-form-url">
					<label for="comment_topic">Topic </label>
					<select required name="comment_topic" id="comment_topic">
						<option value="">Select topic</option>
						<option value="typo">Typo in the English text</option>
    					<option value="context">Where does this string appear? (more context)</option>' .
						$language_question .
					'</select>
    			</p>';
		},
		10,
		2
	);

	if ( is_user_logged_in() ) {
		comment_form(
			$args = array(
				'title_reply'         => __( 'Discuss this string' ),
				'title_reply_to'      => __( 'Reply to %s' ),
				'title_reply_before'  => '<h5 id="reply-title" class="discuss-title">',
				'title_reply_after'   => '</h5>',
				'id_form'             => 'commentform-' . $post_id,
				'cancel_reply_link'   => '<span></span>',
				'comment_notes_after' => implode(
					"\n",
					array(
						'<input type="hidden" name="comment_locale" value="' . esc_attr( $locale_slug ) . '" />',
						'<input type="hidden" name="translation_id" value="' . esc_attr( $translation_id ) . '" />',
						'<input type="hidden" name="redirect_to" value="' . esc_url( $original_permalink ) . '" />',
					)
				),
			),
			$post_id
		);
	} else {
		echo sprintf( __( 'You have to be <a href="%s">logged in</a> to comment.' ), wp_login_url() );
	}

	?>
</div><!-- .discussion-wrapper -->
