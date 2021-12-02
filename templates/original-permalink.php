<?php
$breadcrumbs = array(
	gp_project_links_from_root( $project ),
);

if ( $translation_set ) {
	gp_title( sprintf( __( 'Discussion &lt; %1$s &lt; %2$s &lt; GlotPress', 'glotpress' ), $translation_set->name, $project->name ) );
	$breadcrumbs[] = $translation_set->name;
} else {
	gp_title( sprintf( __( 'Discussion &lt; %s &lt; GlotPress', 'glotpress' ), $project->name ) );
}

gp_breadcrumb( $breadcrumbs );
gp_enqueue_scripts( array( 'gp-editor', 'gp-translations-page', 'gp-translation-discussion-js' ) );
wp_localize_script(
	'gp-translations-page',
	'$gp_translations_options',
	array(
		'sort'   => __( 'Sort', 'glotpress' ),
		'filter' => __(
			'Filter',
			'glotpress'
		),
	)
);
gp_enqueue_style( 'gp-discussion-css' );
gp_tmpl_header();

if ( $locale_slug ) {
	gp_tmpl_load( 'discussion', get_defined_vars(), dirname( __FILE__ ) );
} else {
	gp_tmpl_load( 'original-permalink-template', get_defined_vars(), dirname( __FILE__ ) );
}
