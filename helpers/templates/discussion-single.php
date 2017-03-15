<?php
/**
 * The template for displaying an original's discussion
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
	gp_enqueue_styles( 'gp-base' );
	gp_head();
	wp_head();
	gp_enqueue_style( 'wpcom-translate' );
	gp_print_styles();
?>
	<meta charset="utf-8">
	<title>title</title>
	<style>
		body {
			background: transparent;
		}
		.discussion-list {
			list-style:none;
		}
		article.comment {
			margin: 15px 0 15px 30px;
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
		#respond {
			padding: 0;
		}
	</style>
</head>
<body>
<?php while ( have_posts() ) : the_post(); ?>
	<article id="post-<?php the_ID(); ?>-comments">
		<?php comments_template(); ?>
	</article><!-- #post-## -->
	<?php endwhile;  // End of the loop. ?>
</body>
<?php wp_footer();?>
</html>
