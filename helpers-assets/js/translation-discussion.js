jQuery( function( $ ) {
	$('.helper-translation-discussion').on( 'click', '.comments-selector a', function( e ){
		e.preventDefault();
		$('.comments-selector a').removeClass('active-link');
		$(this).addClass('active-link');

		var $comments = jQuery(e.target).parents('h6').next('.discussion-list');
		var selector = $(e.target).data('selector');
		if ( 'all' === selector  ) {
			$comments.children().show();
		} else {
			$comments.children().hide();
			$comments.children( '.comment-locale-' + selector ).show();
			$comments.children( '.comment-locale-' + selector ).next('ul').show();
		}
		return false;
	} );
	$('.helper-translation-discussion').on( 'submit', '.comment-form', function( e ){
		e.preventDefault();
		e.stopImmediatePropagation();
		var $commentform = $( e.target );
		var formdata = {
			content: $commentform.find('textarea[name=comment]').val(),
			parent: $commentform.find('input[name=comment_parent]').val(),
			post: $commentform.attr('id').split( '-' )[ 1 ],
			meta: {
				translation_id : $commentform.find('input[name=translation_id]').val(),
				locale         : $commentform.find('input[name=comment_locale]').val(),
				comment_topic  : $commentform.find('select[name=comment_topic]').val(),
			}
		}
		$.ajax( {
			url: wpApiSettings.root + 'wp/v2/comments',
			method: 'POST',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
			},
			data: formdata
		}).done( function( response ){
			if ( 'undefined' !== typeof ( response.data ) ) {
				// There's probably a better way, but response.data is only set for errors.
				// TODO: error handling.
			} else {
				$commentform.find('textarea[name=comment]').val('');
				$gp.translation_helpers.fetch( 'discussion' );
			}
		} );

		return false;
	});
});
