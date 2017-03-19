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
		<?php if ( get_comments_number() ) : ?>
		<?php comments_number( 'no comments', 'one comment', '% comments' ); ?>
		<div id="comments-selector">
			<a href="#" data-selector="all">Show all</a> | <a href="#" data-selector="<?php echo esc_attr( gth_get_locale() );?>"><?php echo esc_html( gth_get_locale() )?> only</a>
		</div>
		<?php endif; ?>
		<?php comments_template(); ?>
	</article><!-- #post-## -->
	<?php endwhile;  // End of the loop. ?>
	<script>
		jQuery( function( $ ) {
			var $comments = $('#discussion-list');
			$('.comments-wrapper').on( 'click', '#comments-selector a', function( e ){
				e.preventDefault();
				var selector = $(e.target).data('selector');
				if ( 'all' === selector  ) {
					$comments.children().show();
				} else {
					$comments.children().hide();
					$comments.children( '.comment-locale-' + selector ).show();
				}
				return false;
			} );
		});
	</script>
</body>
<?php wp_footer();?>
</html>
