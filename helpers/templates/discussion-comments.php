<?php
/**
 * The template part for the comments and comment form on an original discussion
 */
?>
<div class="discussion-wrapper">
	<?php if ( $number = count( $comments) ) : ?>
		<h6><?php printf( _n( '%s Comment', '%s Comments', $number ), number_format_i18n( $number ) ); ?>
		<span class="comments-selector">
			<a href="#" data-selector="all">Show all</a> | <a href="#" data-selector="<?php echo esc_attr( $locale_slug );?>"><?php echo esc_html( $locale_slug )?> only</a>
		</span>
		</h6>
	<?php endif; ?>
	<ul class="discussion-list">
		<?php
		wp_list_comments( array(
			'style'       => 'ul',
			'type'       => 'comment',
			'callback' => 'gth_discussion_callback',
		), $comments );
		?>
	</ul><!-- .discussion-list -->
</div><!-- .discussion-wrapper -->