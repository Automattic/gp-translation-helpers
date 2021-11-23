<?php
// Plugin name: GP Translation Helpers

class GP_Route_Translation_Helpers extends GP_Route {

	private $helpers = array();

	function __construct() {
		$this->helpers = GP_Translation_Helpers::load_helpers();
		$this->template_path = dirname( __FILE__ ) . '/templates/';
	}

	function discussion( $project_path, $original_id, $locale_slug = null, $set_slug = null, $translation_id = null ) {
		$project = GP::$project->by_path( $project_path );
		if ( ! $project ) {
			$this->die_with_404();
		}

		$args = array(
			'project_id' => $project->id,
			'locale_slug' => $locale_slug,
			'set_slug' => $set_slug,
			'original_id' => $original_id,
			'translation_id' => $translation_id,
		);
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $set_slug, $locale_slug );
		$original = GP::$original->get( $original_id );

		$translation_helper = $this->helpers['comments'];
		$translation_helper->set_data( $args );

		$post_id = $translation_helper::get_shadow_post( $original_id );
		$comments = get_comments(
			array(
				'post_id' => $post_id,
				'status' => 'approve',
				'type' => 'comment',
				'include_unapproved' => array( get_current_user_id() ),
			)
		);

		$translation = GP::$translation->get( $translation_id );
		$original_permalink = gp_url_project( $project, array( 'filters[original_id]' => $original_id ) );
		$original_translation_permalink = false;
		if ( $translation_set ) {
			$original_translation_permalink = gp_url_project_locale( $project, $locale_slug, $translation_set->slug, array( 'filters[original_id]' => $original_id ) );
		}
		
		wp_register_style( 'gp-discussion-css', plugins_url( './css/discussion.css', __FILE__ ) );

		wp_register_script( 'gp-translation-discussion-js', plugins_url( './js/discussion.js', __FILE__ ) );
		
		add_filter( 'comment_form_logged_in', function( $logged_in_as, $commenter, $user_identity ) {
			return sprintf( '<p class="logged-in-as">%s</p>', sprintf( __( 'Logged in as %s.' ), $user_identity ) );
		}, 10, 3 );

		add_filter( 'comment_form_fields', function( $comment_fields ) {
			$comment_fields['comment'] = str_replace( '>Comment<', '>Please leave your comment about this string here:<', $comment_fields['comment'] );
			return $comment_fields;
		} );

		remove_action( 'comment_form_top', 'rosetta_comment_form_support_hint' );


		add_filter( 'comment_reply_link', function( $link, $args, $comment, $post ) use ( $project, $translation_set, $original ) {
			$permalink = "/projects/" . $project->path . $original->id;
			if ( $translation_set ) {
				$permalink .= '/' . $translation_set->locale . '/' . $translation_set->slug;
			}
			$permalink = home_url( $permalink );

			$data_attributes = array(
				'commentid'      => $comment->comment_ID,
				'postid'         => $post->ID,
				'belowelement'   => $args['add_below'] . '-' . $comment->comment_ID,
				'respondelement' => $args['respond_id'],
				'replyto'        => sprintf( $args['reply_to_text'], $comment->comment_author ),
			);

			$data_attribute_string = '';

			foreach ( $data_attributes as $name => $value ) {
				$data_attribute_string .= " data-${name}=\"" . esc_attr( $value ) . '"';
			}

			$data_attribute_string = trim( $data_attribute_string );

			$link = sprintf(
				"<a rel='nofollow' class='comment-reply-link' href='%s' %s aria-label='%s'>%s</a>",
				esc_url(
					add_query_arg(
						array(
							'replytocom'      => $comment->comment_ID,
							'unapproved'      => false,
							'moderation-hash' => false,
						),
						$permalink
					)
				) . '#' . $args['respond_id'],
				$data_attribute_string,
				esc_attr( sprintf( $args['reply_to_text'], $comment->comment_author ) ),
				$args['reply_text']
			);
			return $args['before'] . $link . $args['after'];
		}, 10, 4 );


		$this->tmpl( 'discussion', get_defined_vars() );
	}

	function translation_helpers( $project_path, $locale_slug, $set_slug, $original_id, $translation_id = null ) {
		$project = GP::$project->by_path( $project_path );
		if ( ! $project ) {
			$this->die_with_404();
		}

		$args = array(
			'project_id' => $project->id,
			'locale_slug' => $locale_slug,
			'set_slug' => $set_slug,
			'original_id' => $original_id,
			'translation_id' => $translation_id,
		);

		$single_helper = gp_get( 'helper' );
		$helpers = $this->helpers;
		if ( isset( $this->helpers[ $single_helper ] ) ) {
			$helpers = array( $this->helpers[ $single_helper ] );
		}

		$sections = array();
		foreach ( $helpers as $translation_helper ) {
			$translation_helper->set_data( $args );
			if ( $translation_helper->has_async_content() && $translation_helper->activate() ) {
				$sections[ $translation_helper->get_div_id() ] = array(
					'content' => $translation_helper->get_async_output(),
					'count' => $translation_helper->get_count(),
				);
			};
		}

		echo wp_json_encode( $sections );
	}
}

class GP_Translation_Helpers {
	public $id = 'translation-helpers';
	private $helpers = array();

	private static $instance = null;

	public static function init() {
		self::get_instance();
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'register_routes' ), 5 );
		// add_action( 'gp_before_request',    array( $this, 'before_request' ), 10, 2 );

		add_filter( 'gp_translation_row_template_more_links', function( $more_links, $project, $locale, $translation_set, $translation ) {
			$permalink = "/projects/" . $project->path . '/' . $translation->original_id;
			$original_permalink = home_url( $permalink );
			if ( $translation_set ) {
				$permalink .= '/' . $translation_set->locale . '/' . $translation_set->slug;
			}
			$permalink = home_url( $permalink );

			$more_links['original_permalink'] = '<a href="' . esc_url( $original_permalink ) . '">Permlink to original</a>';
			$more_links['discussions'] = '<a href="' . esc_url( $permalink ) . '">Discussions</a>';

			return $more_links;

		}, 10, 5 );

		$this->helpers = self::load_helpers();
	}

	public function before_request( $class_name, $last_method ) {
		if (  'GP_Route_Translation' !== $class_name || 'translations_get' !== $last_method ) {
			return;
		}

		// add_action( 'gp_pre_tmpl_load',  array( $this, 'pre_tmpl_load' ), 10, 2 );
	}

	public function pre_tmpl_load( $template, $args ) {
		$allowed_templates = apply_filters( 'gp_translations_helpers_templates', array( 'translations' ) );

		if ( ! in_array( $template, $allowed_templates, true ) ) {
			return;
		}

		$translation_helpers_settings = array(
			'th_url' => gp_url_project( $args['project'], gp_url_join( $args['locale_slug'],  $args['translation_set_slug'], '-get-translation-helpers' ) ),
		);

		add_action( 'gp_head',           array( $this, 'css_and_js' ), 10 );
		add_action( 'gp_translation_row_editor_columns', array( $this, 'translation_helpers' ), 10, 2 );

		add_filter(  'gp_translation_row_editor_clospan', function( $colspan ) {
			return ( $colspan - 3 );
		});

		wp_register_style( 'gp-translation-helpers-css', plugins_url( 'css/translation-helpers.css', __FILE__ ) );
		gp_enqueue_style( 'gp-translation-helpers-css' );

		wp_register_script( 'gp-translation-helpers', plugins_url( './js/translation-helpers.js', __FILE__ ), array( 'gp-editor' ), '2017-02-09' );
		gp_enqueue_scripts( array( 'gp-translation-helpers' ) );

		wp_localize_script( 'gp-translation-helpers', '$gp_translation_helpers_settings',  $translation_helpers_settings );
	}

	public static function load_helpers() {
		require_once( dirname( __FILE__ ) . '/helpers/base-helper.php' );

		$helpers_files = glob( dirname( __FILE__ ) . '/helpers/helper-*.php' );
		foreach ( $helpers_files as $helper ) {
			if ( ! in_array( basename( $helper ), array( 'helper-translation-memory.php' ) ) ) {
				require_once( $helper );
			}
		}

		$helpers = array();

		$classes = get_declared_classes();
		foreach ( $classes as $declared_class ) {
			$reflect = new ReflectionClass( $declared_class );
			if ( $reflect->isSubclassOf( 'GP_Translation_Helper' ) ) {
				$helpers[ sanitize_title_with_dashes( $reflect->getDefaultProperties()['title'] ) ] = new $declared_class;
			}
		}

		return $helpers;
	}

	public function translation_helpers( $t, $translation_set ) {
		$args = array(
			'project_id' => $t->project_id,
			'locale_slug' => $translation_set->locale,
			'set_slug' => $translation_set->slug,
			'original_id' => $t->original_id,
			'translation_id' => $t->id,
			'translation' => $t,
		);

		$sections = array();
		foreach ( $this->helpers as $translation_helper ) {
			$translation_helper->set_data( $args );

			if ( ! $translation_helper->activate() ) {
				continue;
			}

			$sections[] = array(
				'title' => $translation_helper->get_title(),
				'content' => $translation_helper->get_output(),
				'classname' => $translation_helper->get_div_classname(),
				'id' => $translation_helper->get_div_id(),
				'priority' => $translation_helper->get_priority(),
				'has_async_content' => $translation_helper->has_async_content(),
			);
		}

		usort( $sections, function( $s1, $s2 ) {
			return $s1['priority'] > $s2['priority'];
		});

		gp_tmpl_load( 'translation-helpers', array( 'sections' => $sections ), dirname( __FILE__ ) . '/templates/' );
	}


	function register_routes() {
		$dir = '([^_/][^/]*)';
		$path = '(.+?)';
		$projects = 'projects';
		$project = $projects . '/' . $path;
		$locale = '(' . implode( '|', wp_list_pluck( GP_Locales::locales(), 'slug' ) ) . ')';
		$set = "$project/$locale/$dir";
		$id = '(\d+)-?(\d+)?';

		GP::$router->prepend( "/$set/-get-translation-helpers/$id", array( 'GP_Route_Translation_Helpers', 'translation_helpers' ), 'get' );
		GP::$router->prepend( "/$project/(\d+)(?:/$locale/$dir)?", array( 'GP_Route_Translation_Helpers', 'discussion' ), 'get' );
	}

	public function css_and_js() {
		?>
		<style>
			<?php
			foreach ( $this->helpers as $translation_helper ) {
				$css = $translation_helper->get_css();
				if ( $css ) {
					echo '/* Translation Helper:  ' . esc_js( $translation_helper->get_title() ) . ' */' . "\n";
					echo $css . "\n";
				}
			}
			?>
		</style>
		<script>
			<?php
			foreach ( $this->helpers as $translation_helper ) {
				$js = $translation_helper->get_js();
				if ( $js ) {
					echo '/* Translation Helper:  ' . esc_js( $translation_helper->get_title() ) . ' */' . "\n";
					echo $js . "\n";
				}
			}
			?>
		</script>
		<?php
	}

}

add_action( 'gp_init', array( 'GP_Translation_Helpers', 'init' ) );
add_action( 'comment_post', function( $comment_ID ) {
	if ( gp_post( 'translation_id' ) ) {
		$translation_id = sanitize_text_field( gp_post( 'translation_id' ) );
		add_comment_meta( $comment_ID, 'translation_id', $translation_id );
	}
	if ( gp_post( 'comment_locale' ) ) {
		$comment_locale = sanitize_text_field( gp_post( 'comment_locale' ) );
		add_comment_meta( $comment_ID, 'locale', $comment_locale  );
	}
} );
