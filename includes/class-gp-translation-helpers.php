<?php
/**
 * GP_Translation_Helpers class
 *
 * @package gp-translation-helpers
 * @since 0.0.1
 */
class GP_Translation_Helpers {

	/**
	 * Class id.
	 *
	 * @since 0.0.1
	 * @var string
	 */
	public $id = 'translation-helpers';

	/**
	 * Stores an instance of each helper.
	 *
	 * @since 0.0.1
	 * @var array
	 */
	private $helpers = array();

	/**
	 * Stores the self instance.
	 *
	 * @since 0.0.1
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Inits the class.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public static function init() {
		self::get_instance();
	}

	/**
	 * Gets the self instance.
	 *
	 * @since 0.0.1
	 *
	 * @return GP_Translation_Helpers
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * GP_Translation_Helpers constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'register_routes' ), 5 );
		add_action( 'gp_before_request', array( $this, 'before_request' ), 10, 2 );

		add_filter( 'gp_translation_row_template_more_links', array( $this, 'translation_row_template_more_links' ), 10, 5 );
		add_filter( 'preprocess_comment', array( $this, 'preprocess_comment' ) );

		$this->helpers = self::load_helpers();
	}

	/**
	 * Adds the action to load the CSS and JavaScript required only in the original-permalink template.
	 *
	 * @since 0.0.1
	 *
	 * @param string $class_name    The class name of the route.
	 * @param string $last_method   The route method that will be called.
	 *
	 * @return void
	 */
	public function before_request( string $class_name, string $last_method ) {
		if (
			in_array(
				$class_name . '::' . $last_method,
				array(
					// 'GP_Route_Translation::translations_get',
					'GP_Route_Translation_Helpers::original_permalink',
				),
				true
			)
		) {
			add_action( 'gp_pre_tmpl_load', array( $this, 'pre_tmpl_load' ), 10, 2 );
		}
	}

	/**
	 * Adds the link for the discussion in the main screen.
	 *
	 * @since 0.0.2
	 *
	 * @param array              $more_links         The links to be output.
	 * @param GP_Project         $project            Project object.
	 * @param GP_Locale          $locale             Locale object.
	 * @param GP_Translation_Set $translation_set    Translation Set object.
	 * @param Translation_Entry  $translation        Translation object.
	 *
	 * @return array
	 */
	public function translation_row_template_more_links( array $more_links, GP_Project $project, GP_Locale $locale, GP_Translation_Set $translation_set, Translation_Entry $translation ): array {
		$permalink = GP_Route_Translation_Helpers::get_permalink( $project->path, $translation->original_id, $translation_set->slug, $translation_set->locale );

		$more_links['discussion'] = '<a href="' . esc_url( $permalink ) . '">Discussion</a>';

		return $more_links;
	}

	/**
	 * Prevents remote POST to comment forms.
	 *
	 * @since 0.0.2
	 *
	 * @param array $commentdata     Comment data.
	 *
	 * @return array|void
	 */
	public function preprocess_comment( array $commentdata ) {
		if ( ! $commentdata['user_ID'] ) {
			die( 'User not authorized!' );
		}
				return $commentdata;
	}

	/**
	 * Loads the CSS and JavaScript required only in the original-permalink template.
	 *
	 * @since 0.0.1
	 *
	 * @param string $template  The template. E.g. "original-permalink".
	 * @param array  $args      Arguments passed to the template. Passed by reference.
	 *
	 * @return void
	 */
	public function pre_tmpl_load( string $template, array $args ):void {
		$allowed_templates = apply_filters( 'gp_translations_helpers_templates', array( 'original-permalink' ) );

		if ( ! in_array( $template, $allowed_templates, true ) ) {
			return;
		}

		$translation_helpers_settings = array(
			'th_url' => gp_url_project( $args['project'], gp_url_join( $args['locale_slug'], $args['translation_set_slug'], '-get-translation-helpers' ) ),
		);

		add_action( 'gp_head', array( $this, 'css_and_js' ), 10 );
		add_action( 'gp_translation_row_editor_columns', array( $this, 'translation_helpers' ), 10, 2 );

		add_filter(
			'gp_translation_row_editor_clospan',
			function( $colspan ) {
				return ( $colspan - 3 );
			}
		);

		wp_register_style( 'gp-translation-helpers-css', plugins_url( 'css/translation-helpers.css', __DIR__ ), '', '0.0.1' ); // todo: add the version as global element.
		gp_enqueue_style( 'gp-translation-helpers-css' );

		wp_register_script( 'gp-translation-helpers', plugins_url( '/js/translation-helpers.js', __DIR__ ), array( 'gp-editor' ), '2017-02-09', true );
		gp_enqueue_scripts( array( 'gp-translation-helpers' ) );

		wp_localize_script( 'gp-translation-helpers', '$gp_translation_helpers_settings', $translation_helpers_settings );
		wp_localize_script(
			'gp-translation-helpers',
			'wpApiSettings',
			array(
				'root'  => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Gets the translation helpers.
	 *
	 * The returned array has the title helper as key and object of
	 * this class as value.
	 *
	 * @since 0.0.1
	 *
	 * @return array
	 */
	public static function load_helpers(): array {
		$base_dir = dirname( dirname( __FILE__ ) ) . '/helpers/';
		require_once $base_dir . '/base-helper.php';

		$helpers_files = array(
			'helper-translation-discussion.php',
			'helper-other-locales.php',
			'helper-translation-history.php',
		);

		foreach ( $helpers_files as $helper ) {
			require_once $base_dir . $helper;
		}

		$helpers = array();

		$classes = get_declared_classes();
		foreach ( $classes as $declared_class ) {
			$reflect = new ReflectionClass( $declared_class );
			if ( $reflect->isSubclassOf( 'GP_Translation_Helper' ) ) {
				$helpers[ sanitize_title_with_dashes( $reflect->getDefaultProperties()['title'] ) ] = new $declared_class();
			}
		}

		return $helpers;
	}

	/**
	 * Loads the 'translation-helpers' template.
	 *
	 * @since 0.0.1
	 *
	 * @param GP_Translation     $translation The current translation.
	 * @param GP_Translation_Set $translation_set The current translation set.
	 *
	 * @return void
	 */
	public function translation_helpers( GP_Translation $translation, GP_Translation_Set $translation_set ) {
		$args = array(
			'project_id'           => $translation->project_id,
			'locale_slug'          => $translation_set->locale,
			'translation_set_slug' => $translation_set->slug,
			'original_id'          => $translation->original_id,
			'translation_id'       => $translation->id,
			'translation'          => $translation,
		);

		$sections = array();
		foreach ( $this->helpers as $translation_helper ) {
			$translation_helper->set_data( $args );

			if ( ! $translation_helper->activate() ) {
				continue;
			}

			$sections[] = array(
				'title'             => $translation_helper->get_title(),
				'content'           => $translation_helper->get_output(),
				'classname'         => $translation_helper->get_div_classname(),
				'id'                => $translation_helper->get_div_id(),
				'priority'          => $translation_helper->get_priority(),
				'has_async_content' => $translation_helper->has_async_content(),
			);
		}

		usort(
			$sections,
			function( $s1, $s2 ) {
				return $s1['priority'] > $s2['priority'];
			}
		);

		gp_tmpl_load( 'translation-helpers', array( 'sections' => $sections ), dirname( __FILE__ ) . '/templates/' );
	}

	/**
	 * Registers the routes and the methods that will respond to these routes.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function register_routes() {
		$dir      = '([^_/][^/]*)';
		$path     = '(.+?)';
		$projects = 'projects';
		$project  = $projects . '/' . $path;
		$locale   = '(' . implode( '|', wp_list_pluck( GP_Locales::locales(), 'slug' ) ) . ')';
		$set      = "$project/$locale/$dir";
		$id       = '(\d+)-?(\d+)?';

		GP::$router->prepend( "/$project/(\d+)(?:/$locale/$dir)?(/\d+)?", array( 'GP_Route_Translation_Helpers', 'original_permalink' ), 'get' );
		GP::$router->prepend( "/$project/-get-translation-helpers/$id", array( 'GP_Route_Translation_Helpers', 'ajax_translation_helpers' ), 'get' );
		GP::$router->prepend( "/$project/$locale/$dir/-get-translation-helpers/$id", array( 'GP_Route_Translation_Helpers', 'ajax_translation_helpers_locale' ), 'get' );
	}

	/**
	 * Prints inline CSS and JavaScript.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function css_and_js() {
		?>
		<style>
			<?php
			foreach ( $this->helpers as $translation_helper ) {
				$css = $translation_helper->get_css();
				if ( $css ) {
					echo '/* Translation Helper:  ' . esc_js( $translation_helper->get_title() ) . ' */' . "\n";
					echo esc_html( $css ) . "\n";
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
					echo $js . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
			?>
		</script>
		<?php
	}

}
