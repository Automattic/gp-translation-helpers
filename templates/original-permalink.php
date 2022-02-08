<?php
$breadcrumbs = array(
	gp_project_links_from_root( $project ),
);

if ( $translation_set ) {
	/* translators: 1: Translation set name, 2: Project name. */
	gp_title( sprintf( __( 'Discussion &lt; %1$s &lt; %2$s &lt; GlotPress', 'glotpress' ), $translation_set->name, $project->name ) );
	$breadcrumbs[] = $translation_set->name;
} else {
	/* translators: Project name. */
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
	if ( $original->plural ) {
		esc_html_e( 'Singular: ' );
	}

	echo esc_html( $original->singular );
	if ( $original->plural ) {
		echo '<br />';
		esc_html_e( 'Plural: ' );
		echo esc_html( $original->plural );
	}
	?>
</h1>
<?php if ( $translation ) : ?>
	<p>
		<?php echo esc_html( ucfirst( $translation->status ) ); ?> translation:
		<?php
		if ( ( '' == $translation->translation_1 ) && ( '' == $translation->translation_2 ) &&
				   ( '' == $translation->translation_3 ) && ( '' == $translation->translation_4 ) &&
				   ( '' == $translation->translation_5 ) ) :
			?>
			<strong><?php echo esc_html( $translation->translation_0 ); ?></strong>
		<?php else : ?>
			<ul id="translation-list">
			<?php for ( $i = 0; $i <= 5; $i++ ) : ?>
				<?php if ( '' != $translation->{'translation_' . $i} ) : ?>
					<li>
						<?php esc_html( $translation->{'translation_' . $i} ); ?>
					</li>
				<?php endif ?>
			<?php endfor ?>
			</ul>
		<?php endif ?>
	</p>
<?php elseif ( $existing_translations ) : ?>
	<?php foreach ( $existing_translations as $e ) : ?>
		<p>
			<?php echo esc_html( ucfirst( $e->status ) ); ?> translation:
			<?php
			if ( ( '' == $e->translation_1 ) && ( '' == $e->translation_2 ) &&
					   ( '' == $e->translation_3 ) && ( '' == $e->translation_4 ) &&
					   ( '' == $e->translation_5 ) ) :
				?>
				<strong><?php echo esc_html( $e->translation_0 ); ?></strong>
			<?php else : ?>
				<ul id="translation-list">
					<?php for ( $i = 0; $i <= 5; $i++ ) : ?>
						<?php if ( '' != $e->{'translation_' . $i} ) : ?>
							<li>
								<?php esc_html( $e->{'translation_' . $i} ); ?>
							</li>
						<?php endif ?>
					<?php endfor ?>
				</ul>
			<?php endif ?>
		</p>
	<?php endforeach; ?>
<?php else : ?>
	<p>
		<?php esc_html_e( 'This string has no translation in this language.' ); ?>
	</p>
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
		echo wp_kses_post( $section['content'] );
		echo '</div>';
		$is_first_class = '';
	}
	?>
</div>
</div>
</div>
<?php

gp_tmpl_footer();
