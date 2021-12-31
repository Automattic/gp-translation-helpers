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
gp_head();

?>

<div id="original" class="clear">
<h1>
    <?php
    if ($original->plural) esc_attr_e('Singular: ');
    esc_html_e( $original->singular );
	if ($original->plural) {
        echo '<br>';
		esc_html_e('Plural: ');
		esc_html_e( $original->plural );
	}
    ?>
</h1>
<?php if ( $translation ) : ?>
	<p>
		<?php echo esc_html( ucfirst( $translation->status ) ); ?> translation:
        <?php if ( ( '' == $translation->translation_1 ) && ( '' == $translation->translation_2 ) &&
                   ( '' == $translation->translation_3 ) && ( '' == $translation->translation_4 ) &&
                   ( '' == $translation->translation_5 ) ) : ?>
		    <strong><?php echo esc_html( $translation->translation_0 ); ?></strong>
        <?php else : ?>
            <ul id="translation-list">
            <?php for ($i=0; $i<=5; $i++): ?>
                <?php if ( '' != $translation->{'translation_' . $i} ) : ?>
                    <li>
                        <?php esc_html_e( $translation->{'translation_' . $i} ) ?>
                    </li>
                <?php endif ?>
            <?php endfor ?>
            </ul>
        <?php endif ?>
	</p>
<?php elseif ( $existing_translations ) : ?>
	<?php foreach ( $existing_translations as $e ) : var_dump( $e );?>
		<p>
			<?php echo esc_html( ucfirst( $e->translation_status ) ); ?> translation:
			<strong><?php echo esc_html( $e->translations[0] ); ?></strong>
		</p>
	<?php endforeach; ?>
<?php endif; ?>
<div class="translations" row="<?php echo esc_attr( $row_id . ( $translation ? '-' . $translation->id : '' ) ); ?>" replytocom="<?php echo esc_attr( gp_get( 'replytocom' ) ); ?>" >
<div class="translation-helpers">
	<nav>
		<ul class="helpers-tabs">
			<?php
			$is_first_class = 'current';
			foreach ( $sections as $section ) {
				// TODO: printf.
				echo "<li class='{$is_first_class}' data-tab='{$section['id']}'>" . esc_html( $section['title'] ) . '<span class="count"></span></li>'; // WPCS: XSS OK.
				$is_first_class = '';
			}
			?>
		</ul>
	</nav>
	<?php
	$is_first_class = 'current';
	foreach ( $sections as $section ) {
		printf( '<div class="%s helper %s" id="%s">', esc_attr( $section['classname'] ), esc_attr( $is_first_class ), esc_attr( $section['id'] ) );
		if ( $section['has_async_content'] ) {
			echo '<div class="async-content"></div>';
		}
		echo $section['content'];
		echo '</div>';
		$is_first_class = '';
	}
		?>
</div>
</div>
</div>
<?php

gp_tmpl_footer();
