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
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_action( 'send_headers',  array( $this, 'robots_header' ) );
		add_action( 'comment_post',  array( $this, 'add_locale_to_comment_meta' ) );
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

		// Custom Single template.
		add_filter( 'single_template', array( $this, 'single_template' ) );

		// Remove theme header specific script.
		add_action( 'wp_enqueue_scripts', function(){
			wp_dequeue_script( 'shoreditch-header' );
		}, 99 );

		// Add the locale passed to the iframe to the comment form data.
		if ( gth_get_locale() ) {
			add_action( 'highlander_comment_notes_after', function() {
					echo '<input type="hidden" name="comment_locale" value="' . esc_attr( gth_get_locale() ) . '" />' . "\n";
			} );
		}

		// Disable subscribe to posts.
		add_filter( 'option_stb_enabled', '__return_false' );

		// Disable subscribe to comments for now.
		add_filter( 'option_stc_disabled', '__return_true' );

		// Redirect if referrer is missing ous site url. This helps (but doesn't prevent)
		// the comments from being loaded directly (instead of inside our iframe). Good enough.
		if ( ! wp_startswith( wp_get_raw_referer(), site_url() ) ) {
			wp_safe_redirect( site_url() );
		}

		// Dequeue a bunch of unnecessary styles and scripts. Source: JP renderer.
		add_action( 'wp_print_styles', function() {
			// Remove some styles
			wp_dequeue_style( 'mp6hacks' );
			wp_dequeue_style( 'wpcom-bbpress2-staff-css' );
			wp_dequeue_style( 'geo-location-flair' );
			wp_dequeue_style( 'translator-jumpstart' );
			wp_dequeue_style( 'reblogging' );
			wp_dequeue_style( 'notes-admin-bar-rest' );
			wp_dequeue_style( 'follow_css' );
			wp_dequeue_style( 'wpcom-actionbar-bar' );
			wp_dequeue_style( 'widget-achievements' );
			wp_dequeue_style( 'a8c-global-print' );
			wp_dequeue_style( 'notes-admin-bar-rest' );
			wp_dequeue_style( 'wpcom-masterbar-css' );
			wp_dequeue_style( 'masterbar-css' );
			wp_dequeue_style( 'shoreditch-style' );
			wp_dequeue_style( 'shoreditch-wpcom' );

			// Scripts to dequeue
			wp_dequeue_script( 'admin-bar'            );
			wp_dequeue_script( 'debug-bar'            );
			wp_dequeue_script( 'debug-bar-ajax'       );
			wp_dequeue_script( 'notes-admin-bar'      );
			wp_dequeue_script( 'notes-admin-bar-rest' );
			wp_dequeue_script( 'loggedout-subscribe'  );
			wp_dequeue_script( 'homepagescripts'      );
			wp_dequeue_script( 'follow_js'            );

			wp_dequeue_script( 'tos-report-form'      );
			wp_dequeue_script( 'thickbox'             );
			wp_dequeue_script( 'devicepx'             );
			wp_dequeue_script( 'grofiles-cards'       );
			wp_dequeue_script( 'wpgroho'              );
			wp_dequeue_script( 'notes-rest-common'    );
			wp_dequeue_script( 'wpcom-actionbar-bar'  );
			wp_dequeue_script( 'translator'           );
			wp_dequeue_script( 'translator-jumpstart' );
			wp_dequeue_script( 'wpcom-masterbar-js'   );
			wp_dequeue_script( 'notes-rest-common'    );
		}, 999 );

		add_action( 'wp_head', function(){
			// Actions we can remove here
			remove_action( 'wp_head', 'wpl_load_css'                );
			remove_action( 'wp_head', 'jetpack_og_tags'             );
			remove_action( 'wp_head', 'wp_admin_bar_header'         );

			remove_action( 'wp_head', 'feed_links_extra', 3              ); // Display the links to the extra feeds such as category feeds
			remove_action( 'wp_head', 'feed_links', 2                    ); // Display the links to the general feeds: Post and Comment Feed
			remove_action( 'wp_head', 'rsd_link'                         ); // Display the link to the Really Simple Discovery service endpoint, EditURI link
			remove_action( 'wp_head', 'wlwmanifest_link'                 ); // Display the link to the Windows Live Writer manifest file.
			remove_action( 'wp_head', 'index_rel_link'                   ); // index link
			remove_action( 'wp_head', 'parent_post_rel_link', 10, 0      ); // prev link
			remove_action( 'wp_head', 'start_post_rel_link', 10, 0       ); // start link
			remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0   ); // Display relational links for the posts adjacent to the current post.
			remove_action( 'wp_head', 'wp_generator'                     ); // Display the XHTML generator that is generated on the wp_head hook, WP version

			global $opensearch;
			remove_action( 'wp_head', array( $opensearch, 'insertAutodiscovery' ) );

			remove_action( 'wp_head', 'iesitemode_define_webapp'  );
			remove_action( 'wp_head', 'iesitemode_jumplist_tasks' );
			remove_action( 'wp_head', 'openidserver_link_rel_tags');

			remove_action( 'wp_head', 'rel_canonical' );
			remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );

			remove_action( 'wp_head', 'blavatar_add_meta' );
		}, -999 );

		add_action( 'wp_footer', function(){
			remove_action( 'wp_footer', 'wpl_load_ajax_js'            );
			remove_action( 'wp_footer', 'wpl_load_logged_out_js'      );
			remove_action( 'wp_footer', 'loggedout_follow_widget'     );
			remove_action( 'wp_footer', 'sharing_footer'              );
			remove_action( 'wp_footer', 'wpcom_subs_js'               );
			remove_action( 'wp_footer', 'stats_footer',           101 );
			remove_action( 'wp_footer', 'skimlinks_footer_js'         );
			remove_action( 'wp_footer', 'wpcom_mobile_devices_stats', 9999 );
			remove_action( 'wp_footer', 'wpcom_pre_masterbar_statistics' );
		}, -999 );

		// Remove inline terms
		remove_action( 'plugins_loaded', 'inline_terms_loader', 9 );
	}

	public function add_locale_to_comment_meta( $comment_id ) {
		if ( isset( $_POST['comment_locale'] ) && GP_Locales::by_slug( $_POST['comment_locale'] ) ) {
			add_comment_meta( $comment_id, 'locale', $_POST['comment_locale'], true );
		}
	}

	public function robots_header( $wp ) {
		if ( wp_startswith( $wp->request, self::URL_SLUG ) ) {
			header( 'X-Robots-Tag: noindex, nofollow' );
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


	public function single_template( $template ) {
		global $post;
		if ( self::POST_TYPE === $post->post_type ) {
			$template = plugin_dir_path( __FILE__ ) . 'templates/discussion-form.php';
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

	public function get_async_content() {
		return get_comments( array('post_id' => $this->get_shadow_post( $this->data['original_id'] ) ) );
	}

	public function async_output_callback( $comments ) {
		return gp_tmpl_get_output(
			'discussion-comments',
			array(
					'comments' => $comments,
					'post_id' => $this->get_shadow_post( $this->data['original_id'] ),
					'locale_slug' => $this->data['locale_slug'],
			),
			dirname( __FILE__ ) . '/templates'
		);
	}

	public function get_output() {
		$iframe_src = site_url() . '/discuss/original-' . $this->data['original_id'] . '/?locale_slug=' . $this->data['locale_slug'];
		$output = "<div class='iframe-loader'><iframe name='discuss-" . $this->data['original_id'] . "' frameborder='0' data-src='$iframe_src' class='discuss'></iframe></div>";
		return $output;
	}

	public function get_css() {
		return <<<CSS
.helper-translation-discussion {
	position:relative;
	min-height: 600px;
}
.iframe-loader::before {
	content: "Loading comment form...";
	min-height: 200px; 
}
iframe.discuss {
	border: 0;
	position: absolute;
	height: 100%;
	width: 100%;
}
.discussion-list {
	list-style:none;
	max-width: 560px;
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
		return <<<JS
		jQuery( function( $ ) {
			$('#translations').on( 'beforeShow', '.editor', function(){
		        $(this).find( 'iframe.discuss' ).prop( 'src', function(){
		            return $(this).data('src');
		        }).on('load', function(){
		        	$(this).parent().removeClass('iframe-loader');
		        });
			});
			$('.helper-translation-discussion').on( 'click', '.comments-selector a', function( e ){
				e.preventDefault();
				var comments = jQuery(e.target).parents('h6').next('.discussion-list');
				var selector = $(e.target).data('selector');
				if ( 'all' === selector  ) {
					comments.children().show();
				} else {
					comments.children().hide();
					comments.children( '.comment-locale-' + selector ).show();
				}
				return false;
			} );
		});
JS;
	}
}


function gth_get_locale() {
	$locale_slug = gp_get( 'locale_slug', false );
	if ( ! $locale_slug ) {
		return false;
	}
	$_locale = GP_Locales::by_slug( $_GET['locale_slug'] );
	return  $_locale ? $_locale->slug : false;
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