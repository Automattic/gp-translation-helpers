<?php
$breadcrumbs = array(
	gp_project_links_from_root( $project )
);

if ( $translation_set ) {
	gp_title( sprintf( __( 'Discussion &lt; %s &lt; %s &lt; GlotPress', 'glotpress' ), $translation_set->name, $project->name ) );
	$breadcrumbs[] = $translation_set->name;
} else {
	gp_title( sprintf( __( 'Discussion &lt; %s &lt; GlotPress', 'glotpress' ), $project->name ) );
}

gp_breadcrumb( $breadcrumbs );
gp_enqueue_scripts( array( 'gp-editor', 'gp-translations-page', 'gp-translation-discussion-js') );
wp_localize_script( 'gp-translations-page', '$gp_translations_options', array( 'sort' => __( 'Sort', 'glotpress' ), 'filter' => __( 'Filter', 'glotpress' ) ) );
gp_enqueue_style( 'gp-discussion-css' );
gp_tmpl_header();
?>

<h1 class="discussion-heading"><?php echo esc_html( $original->singular ); ?></h1>

<?php if ( $translations[0] ) : ?>
	<h2>Translation: <?php echo $translations[0]->translations[0]; ?></h2>
<?php endif; ?>

<?php if ( $original_translation_permalink ) : ?>
<a href="<?php echo esc_url( $original_translation_permalink ); ?>">View translation</a>
<?php endif; ?>

<div class="discussion-wrapper">
	<?php if ( $number = count( $comments ) ) : ?>
		<h4><?php printf( _n( '%s Comment', '%s Comments', $number ), number_format_i18n( $number ) ); ?>
		<?php if ( $original_translation_permalink ) : ?>
			<span class="comments-selector">
				<a href="<?php echo $original_permalink; ?>">Original Permalink page</a>
			</span>
		<?php endif; ?>
		</h4>
	<?php endif; ?>
	
	<ul class="discussion-list">
	
		<?php
		wp_list_comments( array(
			'style'       => 'ul',
			'type'       => 'comment',
			'callback' => 'gth_discussion_callback',
			'translation_id' => $translation_id,
			'original_permalink' => $original_permalink,
		), $comments );
		?>
	</ul><!-- .discussion-list -->
	<?php
	comment_form( $args = array(
		'title_reply'          => __( 'Discuss this string' ),
		'title_reply_to'       => __( 'Reply to %s' ),
		'title_reply_before'   => '<h6 id="reply-title" class="discuss-title">',
		'title_reply_after'    => '</h6>',
		'id_form'              => 'commentform-' . $post_id,
		'comment_notes_after'  => implode( "\n",
			array(
				'<input type="hidden" name="comment_locale" value="' . esc_attr( $locale_slug ) . '" />',
				'<input type="hidden" name="translation_id" value="' . esc_attr( $translation_id ) . '" />',
				'<input type="hidden" name="redirect_to" value="' . esc_url( home_url($_SERVER['REQUEST_URI']) ) . '" />',
				
			) ),
	), $post_id);
	?>
</div>
