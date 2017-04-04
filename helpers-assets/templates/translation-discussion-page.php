<?php
$locale_display_name = "{$locale->english_name} ({$locale->slug})";
gp_title( sprintf( __( 'Latest comments for &lt; %s &lt; GlotPress', 'glotpress' ), $locale_display_name ) );

$breadcrumb = array(
	gp_link_get( gp_url( '/languages' ), __( 'Locales', 'glotpress' ) ),
	gp_link_get( gp_url_join( gp_url( '/languages' ), $locale->slug ), esc_html( $locale->english_name ) ),
	'Discussion',
);
gp_breadcrumb( $breadcrumb );

gp_tmpl_header();
$project_count = 0;
?>
	<h2>
		<?php printf( esc_html__( 'Waiting and Fuzzy Translations in %s', 'glotpress' ), esc_html( $locale_display_name ) ); ?>
	</h2>
	<p>
		This page lists all recent discussion regarding translations for <em><?php echo esc_html( $locale_display_name ); ?></em> across all active translation projects.
	</p>
	<?php foreach ( $originals as $original_id => $original ) : ?>
	<?php gth_discussion_output_original( $original, $locale_slug, $set_slug ); ?>
	<ol class="discussion-list">
		<?php
		wp_list_comments( array(
			'style'       => 'ol',
			'type'       => 'comment',
			'callback' => 'gth_discussion_page_callback',
		), $comments_by_original_id[ $original_id ] );
		?>
	</ol><!-- .discussion-list -->
	<?php endforeach; ?>
<?php
gp_tmpl_footer();
