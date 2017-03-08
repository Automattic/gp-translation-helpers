<td colspan="2" class="translation-helpers">
	<div class="helpers-content">
	<h3>More info</h3>
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
		echo $section['content'];
		echo '</div>';
		$is_first_class = '';
	}
	?>
	</div>
	<?php if ( '' !== $css ) : ?>
	<style>
		<?php echo $css; ?>
	</style>
	<?php endif; ?>
	<?php if ( '' !== $js ) : ?>
	<script>
		<?php echo $js; ?>
	</script>
	<?php endif; ?>
</td>
