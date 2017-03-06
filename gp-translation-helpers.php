<?php

class GP_Route_Translation_Helpers extends GP_Route {

	private $helpers = array();

	function __construct() {
		$this->helpers = GP_Translation_Helpers::load_helpers();
		$this->template_path = dirname( __FILE__ ) . '/templates/';
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

		$sections = array();
		foreach ( $this->helpers as $translation_helper ) {
			$translation_helper->init( $args );
			if ( $translation_helper->has_async_content() ) {
				$sections[ $translation_helper->get_div_id() ] = $translation_helper->get_async_output();
			}
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
		$this->helpers = self::load_helpers();

		add_action( 'gp_head',           array( $this, 'css' ), 10 );
		add_action( 'template_redirect', array( $this, 'register_routes' ), 5 );
		add_action( 'gp_pre_tmpl_load',  array( $this, 'pre_tmpl_load' ), 10, 2 );

		add_filter(  'gp_translation_row_editor_clospan', function( $colspan ) {
			return ( $colspan - 2 );
		});

		add_action( 'gp_translation_row_editor_columns', array( $this, 'translation_helpers' ), 10, 2 );
	}

	public static function load_helpers() {
		require_once( dirname( __FILE__ ) . '/helpers/abstract-helper.php' );

		$helpers_files = glob( dirname( __FILE__ ) . '/helpers/helper-*.php' );
		foreach ( $helpers_files as $helper ) {
			require_once( $helper );
		}

		$helpers = array();

		$classes = get_declared_classes();
		foreach ( $classes as $declared_class ) {
			$reflect = new ReflectionClass( $declared_class );
			if ( $reflect->isSubclassOf( 'GP_Translation_Helper' ) ) {
				$helpers[] = new $declared_class;
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
			'translation_id' => $t->translation_id,
		);

		$sections = array();
		foreach ( $this->helpers as $translation_helper ) {
			$translation_helper->init( $args );
			$sections[ $translation_helper->get_priority() ] = $translation_helper->get_initial_output();
		}

		l( $sections );

		ksort( $sections );
		gp_tmpl_load( 'translation-helpers', array( 'sections' => $sections ), dirname( __FILE__ ) . '/templates/' );
	}

	public function pre_tmpl_load( $template, $args ) {
		if ( 'translations' !== $template ) {
			return;
		}

		$translation_helpers_settings = array(
			'th_url' => gp_url_project( $args['project'], gp_url_join( $args['locale_slug'],  $args['translation_set_slug'], '-get-translation-helpers' ) ),
		);

		wp_register_script( 'gp-translation-helpers', plugins_url( './js/translation-helpers.js', __FILE__ ), array( 'gp-editor' ), '2017-02-09' );
		gp_enqueue_scripts( array( 'gp-translation-helpers' ) );

		wp_localize_script( 'gp-translation-helpers', '$gp_translation_helpers_settings',  $translation_helpers_settings );
	}

	function register_routes() {
		$dir = '([^_/][^/]*)';
		$path = '(.+?)';
		$projects = 'projects';
		$project = $projects . '/' . $path;
		$locale = '(' . implode( '|', wp_list_pluck( GP_Locales::locales(), 'slug' ) ) . ')';
		$set = "$project/$locale/$dir";
		$id = '(\d+)(-\d+)?';

		GP::$router->prepend( "/$set/-get-translation-helpers/$id", array( 'GP_Route_Translation_Helpers', 'translation_helpers' ), 'get' );
	}

	public function css() {
		?>
		<style>
			.editor td {
				vertical-align: top;
			}
			.translation-helpers {
				min-width: 550px;
				padding:10px;
				border:0;
			}
			.translation-helpers .loading {
				background: url(https://s0.wp.com/wp-content/mu-plugins/notes/images/loading.gif) no-repeat left center;
				background-size: 20px;
				padding-left: 24px;
			}
			.translations .translation-helpers > h3 {
				margin-top: 5px;
				margin-bottom: 10px;
			}
			.translation-helpers h4 {
				margin-bottom: 0.5em;
				font-size: 1.1em;
				padding: .25em .5em;
			}
			.helper {
				padding: .25em .5em;
			}
			.helpers-content {
				overflow-y: scroll;
				max-height: 800px;
			}
		</style>
		<?php
	}

}

add_action( 'gp_init', array( 'GP_Translation_Helpers', 'init' ) );
