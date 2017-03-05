$gp.translation_helpers = (
	function( $ ) {
		return {
			init: function( table ) {
				$gp.translation_helpers.table = table;
				$gp.translation_helpers.datatd = $('<td colspan="2" class="translation-helpers">Loading...</td>');
				$gp.translation_helpers.install_hooks();
			},
			install_hooks: function() {
				$( $gp.translation_helpers.table )
					.on( 'beforeShow', '.editor', $gp.translation_helpers.hooks.appendTd );
			},
			appendTd : function( $element ) {
				if ( $element.find('.translation-helpers').length > 0 ) {
					return;
				}
				var $first_td = $element.find('td:first');
				var row_id  = $element.attr('row');
				if ( $first_td.attr('colspan') > 3 ) {
					$first_td.attr('colspan', ( $first_td.attr('colspan') - 2 ) );
				}
				$gp.translation_helpers.fetch( row_id, $element );

				$element.append( $gp.translation_helpers.datatd.clone()  );
			},
			fetch : function( originalId, $element ) {
				$.get(
					$gp_translation_helpers_settings.th_url + '/'  + originalId,
					function( data ){
						$element.find('.translation-helpers').html( data );
					}
				);
			},
			hooks: {
				appendTd: function() {
					$gp.translation_helpers.appendTd( $( this ) );
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