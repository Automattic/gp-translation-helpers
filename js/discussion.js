jQuery( function( $ ) {

	$('.discussion-wrapper').on( 'click', '.comments-selector a', function( e ){
		e.preventDefault();
		var $comments = jQuery(e.target).parents('h6').next('.discussion-list');
		var selector = $(e.target).data('selector');
		if ( 'all' === selector  ) {
			$comments.children().show();
		} else {
			$comments.children().hide();
			$comments.children( '.comment-locale-' + selector ).show();
		}
		return false;
	} );
});