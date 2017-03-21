<?php

class Helper_Translation_Discussion extends GP_Translation_Helper {

	public $priority = 0;
	public $title = 'Comments';
	public $has_async_content = true;

	const POST_TYPE = 'gth_original';
	const POST_STATUS = 'publish';
	const LINK_TAXONOMY = 'gp_original_id';
	const URL_SLUG = 'discuss';
	const ORIGINAL_ID_PREFIX = 'original-';

	//Temporarily disable
	function activate() {
		return true;
	}

	function after_constructor() {
		$this->register_post_type_and_taxonomy();
		add_filter( 'pre_comment_approved', array( $this, 'comment_moderation' ), 10, 2 );
	}

	public function register_post_type_and_taxonomy() {
		register_taxonomy(
			self::LINK_TAXONOMY,
			array(),
			array(
				'public' => false,
				'show_ui' => false,
			)
		);

		$post_type_args = array(
			'supports'            => array( 'comments' ),
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => false,
			'exclude_from_search' => false,
			'public'              => true,
			'publicly_queryable'  => true,
			'rewrite'               => array(
				'slug' => self::URL_SLUG,
			),
			'has_archive'         => true,
			'show_in_rest'        => true,
			'taxonomies'          => array( self::LINK_TAXONOMY ),
		);

		register_post_type( SELF::POST_TYPE, $post_type_args );
	}

	public function add_locale_to_comment_meta( $comment_id ) {
		if ( isset( $_POST['comment_locale'] ) && GP_Locales::by_slug( $_POST['comment_locale'] ) ) {
			add_comment_meta( $comment_id, 'locale', $_POST['comment_locale'], true );
		}
	}

	public function comment_moderation( $approved, $commentdata ) {
		global $wpdb;

		// If the comment is already approved, we're good.
		if ( $approved ) {
			return $approved;
		}

		// We only care on comments on our specific post type
		if ( self::POST_TYPE !== get_post_type( $commentdata['comment_post_ID'] ) ) {
			return $approved;
		}

		// We can't do much if the comment was posted logged out.
		if ( empty( $commentdata['comment_author'] ) ) {
			return $approved;
		}

		// If our user has already contributed translations, approve comment.
		$user_current_translations = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->gp_translations WHERE user_id = %s AND status = 'current'", $commentdata['comment_author'] ) );
		if ( $user_current_translations ) {
			$approved = true;
		}

		return $approved;
	}

	private function get_shadow_post( $original_id ) {
		$cache_key = self::LINK_TAXONOMY . '_' . $original_id;

		if ( true || false === ( $post_id = wp_cache_get( $cache_key ) ) ) {
			$gp_posts = get_posts(
				array(
					'tax_query'        => array(
						array(
							'taxonomy' => self::LINK_TAXONOMY,
							'terms'    => $original_id,
							'field'    => 'slug',
						),
					),
					'post_type'        => self::POST_TYPE,
					'posts_per_page'   => 1,
					'post_status'      => self::POST_STATUS,
					'suppress_filters' => false,
				)
			);

			if ( ! empty( $gp_posts ) ) {
				$post_id = $gp_posts[0]->ID;
			} else {
				$post_id = wp_insert_post(
					array(
						'post_type'      => SELF::POST_TYPE,
						'tax_input'      => array(
							self::LINK_TAXONOMY => array( $original_id ),
						),
						'post_status'    => self::POST_STATUS,
						'comment_status' => 'open',
					)
				);
			}
		}

		wp_cache_add( $cache_key, $post_id );
		return $post_id;
	}

	public function get_async_content() {
		return get_comments( array('post_id' => $this->get_shadow_post( $this->data['original_id'] ) ) );
	}

	public function async_output_callback( $comments ) {
		$output = gp_tmpl_get_output(
			'discussion-comments',
			array(
				'comments' => $comments,
				'post_id' => $this->get_shadow_post( $this->data['original_id'] ),
				'locale_slug' => $this->data['locale_slug'],
			),
			dirname( __FILE__ ) . '/templates'
		);
		return $output;
	}

	public function empty_content() {
		return 'No comments yet.';
	}

	public function get_css() {
		return <<<CSS
.helper-translation-discussion {
	position:relative;
	min-height: 600px;
}
.discussion-list {
	list-style:none;
	max-width: 560px;
}
.comments-wrapper {
max-width: 600px;
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
.comments-selector {
	display: inline-block;
	padding-left: 10px;
	font-size: 0.9em;
}
CSS;
	}
	public function get_js() {
		return <<<'JS'
		jQuery( function( $ ) {
			$('.helper-translation-discussion').on( 'click', '.comments-selector a', function( e ){
				e.preventDefault();
				var $comments = jQuery(e.target).parents('h6').next('.discussion-list');
				var selector = $(e.target).data('selector');
				if ( 'all' === selector  ) {
					$comments.children().show();
				} else {
					$comments.children().hide();
					$comments.children( '.comment-locale-' + selector ).show();
				}
				return false;
			} );
			$('.helper-translation-discussion').on( 'submit', '.comment-form', function( e ){
				e.preventDefault();
				var $commentform = $( e.target );
				var formdata = $commentform.serializeArray();
				var formurl = 'https://public-api.wordpress.com/wp/v2/sites/translate.wordpress.com/comments';
				//Post Form with data
				$.ajax({
					type: 'post',
					url: formurl + '?nohc',
					data: formdata,
					dataType: 'json',
					error: function(){
					  statusdiv.html('<p class="ajax-error" >You might have left one of the fields blank, or be posting too quickly</p>');
					},
					success: function( data ){
						console.log(data);
						if ( data == "success" ) {
							$gp.translation_helpers.fetch( 'comments' );
							statusdiv.html('<p class="ajax-success" >Thanks for your comment. We appreciate your response.</p>');
						} else {
							statusdiv.html('<p class="ajax-error" >Please wait a while before posting your next comment</p>');
							$commentform.find('textarea[name=comment]').val('');
						}
					}
				});
				return false;
				
			});
		});
JS;
	}
}


function gth_discussion_callback( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;

	// Remove comment likes for now (or forever :) ).
	remove_filter( 'comment_text', 'comment_like_button', 12 );

	$comment_locale = get_comment_meta( $comment->comment_ID, 'locale', true );
	?>
<li class="<?php echo esc_attr( 'comment-locale-' . $comment_locale );?>">
	<article id="comment-<?php comment_ID(); ?>" class="comment">
		<div class="comment-avatar">
			<?php echo get_avatar( $comment, 25 ); ?>
		</div><!-- .comment-avatar -->
		<?php printf( '<cite class="fn">%s</cite>', get_comment_author_link() ); ?>
		<?php
		// Older than a week, show date; otherwise show __ time ago.
		if ( current_time( 'timestamp' ) - get_comment_time( 'U' ) > 604800 ) {
			$time = sprintf( _x( '%1$s at %2$s', '1: date, 2: time' ), get_comment_date(), get_comment_time() );
		} else {
			$time = sprintf( __( '%1$s ago' ), human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) ) );
		}
		echo '<time datetime=" ' . get_comment_time( 'c' ) . '">' . $time . '</time>';
		?>
		<?php

		if ( $comment_locale  ) : ?>
			<div class="comment-locale">Locale: <?php echo esc_html( $comment_locale );?></div>
		<?php endif; ?>
		<div class="comment-content"><?php comment_text(); ?></div>
		<footer>
			<div class="comment-author vcard">
				<?php
				if ( $comment->comment_parent ) {
					printf(
						'<a href="%1$s">%2$s</a>',
						esc_url( get_comment_link( $comment->comment_parent ) ),
						sprintf( __( 'in reply to %s' ), get_comment_author( $comment->comment_parent ) )
					);
				}
				comment_reply_link( array_merge( $args, array(
					'depth'     => $depth,
					'max_depth' => $args['max_depth'],
					'before'    => '<span class="alignright">',
					'after'     => '</span>',
				) ) );
				?>
			</div><!-- .comment-author .vcard -->
			<?php if ( $comment->comment_approved == '0' ) : ?>
				<em><?php _e( 'Your comment is awaiting moderation.' ); ?></em>
			<?php endif; ?>
		</footer>
	</article><!-- #comment-## -->
</li>
	<?php
}