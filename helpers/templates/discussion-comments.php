<?php
/**
 * The template part for the comments and comment form on an original discussion
 */
?>
<div id="comments">
	<div class="discussion-wrapper">
		<ul id="discussion-list">
			<?php
			wp_list_comments( array(
				'style'       => 'ul',
				'type'       => 'comment',
				'callback' => 'gth_discussion_callback',
			) );
			?>
		</ul><!-- .discussion-list -->
	</div><!-- .discussion-wrapper -->
	<?php
	comment_form( $args = array(
		'title_reply'          => __( 'Discuss this string' ),
		'title_reply_to'       => __( 'Reply to %s' ),
		'title_reply_before'   => '<h4 id="reply-title" class="discuss-title">',
		'title_reply_after'    => '</h4>',
	));
	?>
</div><!-- #comments -->