$gp.translation_helpers = (
	function( $ ) {
		return {
			init: function( table ) {
				$gp.translation_helpers.table = table;
			//	$gp.translation_helpers.datatd = $('<td colspan="2" class="translation-helpers">Loading...</td>');
				$gp.translation_helpers.install_hooks();
			},
			install_hooks: function() {
				$( $gp.translation_helpers.table )
					.on( 'beforeShow', '.editor', $gp.translation_helpers.hooks.fetch );
			},
			fetch : function( $element ) {
				var originalId  = $element.find('.translation-helpers').parent().attr( 'row');
				var $helpers = $element.find('.translation-helpers');

				if ( $helpers.hasClass('loaded') ) {
					return;
				}

				$helpers.addClass('loading');
				$.getJSON(
					$gp_translation_helpers_settings.th_url + '/'  + originalId,
					function( data ){
						$helpers.addClass('loaded').removeClass('loading');
						$.each( data, function( id, html ){
							$( '#'  + id ).find('.loading').hide();
							$( '#'  + id ).prepend( html )	;
						} );

					}
				);
			},
			hooks: {
				fetch: function() {
					$gp.translation_helpers.fetch( $( this ) );
					return false;
				}
			}
		}
	}( jQuery )
);

jQuery( function( $ ) {
	$gp.translation_helpers.init( $( '#translations' ) );
	if ( typeof window.newShowFunctionAttached === 'undefined' ) {
		window.newShowFunctionAttached = true;
		var _oldShow = $.fn.show;
		$.fn.show = function( speed, oldCallback ) {
			return $( this ).each( function() {
				var obj = $( this ),
					newCallback = function() {
						if ( $.isFunction( oldCallback ) ) {
							oldCallback.apply( obj );
						}
					};

				obj.trigger( 'beforeShow' );
				_oldShow.apply( obj, [ speed, newCallback ] );
			} );
		}
	}
} );