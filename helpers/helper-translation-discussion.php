<?php

class Helper_Translation_Discussion extends GP_Translation_Helper {

	public $priority = 0;
	public $title = 'Comments';
	public $has_async_content = false;

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
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_action( 'send_headers',  array( $this, 'robots_header' ) );
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

	public function pre_get_posts( $query ) {
		if ( ! ( self::POST_TYPE === $query->get( 'post_type' ) && $query->is_main_query() ) ) {
			return;
		}

		$original_or_post_id = $query->get( self::POST_TYPE );

		// URL with shadow post id - redirect to url with original id
		if ( ! gp_startswith( $original_or_post_id, self::ORIGINAL_ID_PREFIX ) ) {

			$terms = wp_get_object_terms( $original_or_post_id, self::LINK_TAXONOMY );
			if ( empty( $terms ) ) {
				$query->set_404();
				status_header( 404 );
				return;
			}
			$original_id = $terms[0]->slug;

			wp_safe_redirect( trailingslashit( site_url( '/' . self::URL_SLUG . '/' . self::ORIGINAL_ID_PREFIX . absint( $original_id ) ) ), 301 );
			die();
		}

		// URL in the format of discuss/original-xxx. Show our minimal template.
		$original_id = absint( substr( $original_or_post_id, 9 ) );
		$this->original = GP::$original->get( $original_id );

		if ( ! $this->original ) {
			$query->set_404();
			status_header( 404 );
			return;
		}

		$this->set_the_stage();

		$post_id = $this->get_shadow_post( $original_id );

		$query->set( self::POST_TYPE, $post_id );
		$query->set( 'original_id', $original_id );
	}

	public function set_the_stage() {
		define( 'IFRAME_REQUEST', true );

		// Custom Single template
		add_filter( 'single_template', array( $this, 'single_template' ) );

		// Custom comments template
		add_filter( 'comments_template', function(){
			return plugin_dir_path( __FILE__ ) . 'templates/discussion-comments.php';
		} );

		// Remove theme header specific script
		add_action( 'wp_enqueue_scripts', function(){
			wp_dequeue_script( 'shoreditch-header' );
		}, 99 );

		// Remove comment likes for now (or forever :) )
		remove_filter( 'comment_text', 'comment_like_button', 12 );
	}

	public function robots_header( $wp ) {
		if ( wp_startswith( $wp->request, self::URL_SLUG ) ) {
			header( 'X-Robots-Tag: noindex, nofollow' );
		}
	}

	public function single_template( $template ) {
		global $post;
		if ( self::POST_TYPE === $post->post_type ) {
			$template = plugin_dir_path( __FILE__ ) . 'templates/discussion-single.php';

			// Load some GP functions
			$core_templates = WP_CONTENT_DIR . '/plugins/glotpress/gp-templates/';
			require_once $core_templates . 'helper-functions.php';
		}

		return $template;
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

	public function get_output() {
		$iframe_src = site_url() . '/discuss/original-' . $this->data['original_id'] . '/';
		$output = "<iframe style='border:0; position: absolute; height: 100%; width: 100%;' name='discuss-" . $this->data['original_id'] . "' frameborder='0' src='$iframe_src'></iframe>";
		return $output;
	}

	public function get_css() {
		return <<<CSS
.helper-translation-discussion {
	position:relative;
	min-height: 600px;
}
.original-comments {
	list-style: none;
}
.original-comments li {
	clear: both;
}
.comment-meta {
    float: left;
    width: 50px;
    margin-right: 10px;
}
CSS;

	}
}


function gth_discussion_callback( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment; ?>
<li>
	<article id="comment-<?php comment_ID(); ?>" class="comment">
		<div class="comment-avatar">
			<?php echo get_avatar( $comment, 25 ); ?>
		</div><!-- .comment-avatar -->
		<?php printf( '<cite class="fn">%s</cite>', get_comment_author_link() ); ?>
		<div class="comment-content"><?php comment_text(); ?></div>
		<footer>
			<div class="comment-author vcard">
				<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
					<?php
					// Older than a week, show date; otherwise show __ time ago.
					if ( current_time( 'timestamp' ) - get_comment_time( 'U' ) > 604800 ) {
						$time = sprintf( _x( '%1$s at %2$s', '1: date, 2: time' ), get_comment_date(), get_comment_time() );
					} else {
						$time = sprintf( __( '%1$s ago' ), human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) ) );
					}
					echo '<time datetime=" ' . get_comment_time( 'c' ) . '">' . $time . '</time>'; ?>
				</a>
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