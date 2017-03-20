<?php
/**
 * The template for displaying an original's discussion
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
	wp_head();
?>
	<meta charset="utf-8">
	<title>title</title>
	<style>
		body {
			background: transparent;
			padding-right: 30px;
		}
		#discussion-list {
			list-style:none;
		}
		article.comment {
			margin: 15px 30px 15px 30px;
			position: relative;
			font-size: 0.9rem;
		}
		article.comment p {
			margin-bottom: 0.5em;
		}
		article.comment footer {
			overflow: hidden;
			font-size: 0.8rem;
			font-style: italic;
		}
		article.comment time {
			font-style: italic;
			opacity: 0.8;
			display: inline-block;
			padding-left: 4px;
		}
		.comment-locale {
			opacity: 0.8;
			float: right;
		}
		.comment-avatar {
			margin-left: -45px;
			margin-bottom: -25px;
			width: 50px;
			height: 26px;
		}
		.comment-avatar img {
			display: block;
			border-radius: 13px;
		}
		#comments-selector {
			display: inline-block;
			padding-left: 10px;
			font-size: 0.9em;
		}
		#respond {
			padding: 0;
		}
	</style>
</head>
<body>
<?php while ( have_posts() ) : the_post(); ?>
	<article id="post-<?php the_ID(); ?>-comments" class="comments-wrapper">
		<?php
		comment_form( $args = array(
			'title_reply'          => __( 'Discuss this string' ),
			'title_reply_to'       => __( 'Reply to %s' ),
			'title_reply_before'   => '<h4 id="reply-title" class="discuss-title">',
			'title_reply_after'    => '</h4>',
		));
		?>
	</article><!-- #post-## -->
	<?php endwhile;  // End of the loop. ?>
</body>
<?php wp_footer();?>
</html>
