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
		add_action( 'gp_pre_tmpl_load',  array( $this, 'pre_tmpl_load' ), 10, 2 );
	}

	public function pre_tmpl_load( $template, $args ) {
		if ( 'translations' !== $template ) {
			return;
		}

		$this->helpers = self::load_helpers();

		$translation_helpers_settings = array(
			'th_url' => gp_url_project( $args['project'], gp_url_join( $args['locale_slug'],  $args['translation_set_slug'], '-get-translation-helpers' ) ),
		);

		add_action( 'gp_head',           array( $this, 'css' ), 10 );
		add_action( 'gp_translation_row_editor_columns', array( $this, 'translation_helpers' ), 10, 2 );

		add_filter(  'gp_translation_row_editor_clospan', function( $colspan ) {
			return ( $colspan - 2 );
		});


		wp_register_script( 'gp-translation-helpers', plugins_url( './js/translation-helpers.js', __FILE__ ), array( 'gp-editor' ), '2017-02-09' );
		gp_enqueue_scripts( array( 'gp-translation-helpers' ) );

		wp_localize_script( 'gp-translation-helpers', '$gp_translation_helpers_settings',  $translation_helpers_settings );
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
			'translation_id' => $t->id,
		);

		$sections = array();
		foreach ( $this->helpers as $translation_helper ) {
			$translation_helper->init( $args );

			if ( ! $translation_helper->activate() ) {
				continue;
			}

			$sections[] = array(
				'title' => $translation_helper->get_tab_title(),
				'content' => $translation_helper->get_initial_output(),
				'classname' => $translation_helper->get_div_classname(),
				'id' => $translation_helper->get_div_id(),
				'priority' => $translation_helper->get_priority(),
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
			.translation-helpers h4 {
				margin-bottom: 0.5em;
				font-size: 1.1em;
				padding: .25em .5em;
			}

			.helpers-content {
				overflow-y: scroll;
				max-height: 800px;
			}

			.translation-helpers .helpers-content h3 {
				margin-top: 0.5em;
			}

			.helpers-tabs {
				margin: 0px;
				padding: 0px;
				list-style: none;
				border-bottom: 2px solid #eee;
				white-space: nowrap;
			}

			.helpers-tabs li {
				background: #eee;
				color: #222;
				display: inline-block;
				padding: 10px 15px;
				cursor: pointer;
				margin: 0 1px;
			}

			.helpers-tabs li.current {
				background-color: transparent;
				margin: 0 0 -2px;
				border: 2px solid #eee;
				border-bottom: 2px solid #f8ffec;
				font-weight: bold;
			}

			.loading .helpers-tabs {
				padding-right: 28px;
				background: transparent url(https://s0.wp.com/wp-content/mu-plugins/notes/images/loading.gif) no-repeat right 4px center;
				background-size: 20px;
			}

			.helper {
				display: none;
				border: 2px solid #eee;
				border-top: none;
				padding: 1em .5em 1em;
				min-height: 200px;
			}

			.helper.current {
				display: inherit;
			}

			.helpers-tabs li .count {
				display: inline-block;
				padding-left: 4px;
				opacity: 0.6;
			}

		</style>
		<?php
	}

}

add_action( 'gp_init', array( 'GP_Translation_Helpers', 'init' ) );
