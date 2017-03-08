$gp.translation_helpers = (
	function( $ ) {
		return {
			init: function( table ) {
				$gp.translation_helpers.table = table;
				$gp.translation_helpers.install_hooks();
			},
			install_hooks: function() {
				$( $gp.translation_helpers.table )
					.on( 'beforeShow', '.editor', $gp.translation_helpers.hooks.fetch )
					.on( 'click', '.helpers-tabs li', $gp.translation_helpers.hooks.tab_select );
			},
			fetch : function( $element ) {
				var originalId  = $element.find('.translation-helpers').parent().attr('row');
				var $helpers = $element.find('.translation-helpers');

				if ( $helpers.hasClass('loaded') ) {
					return;
				}

				$helpers.addClass('loading');
				$.getJSON(
					$gp_translation_helpers_settings.th_url + '/'  + originalId,
					function( data ){
						$helpers.addClass('loaded').removeClass('loading');
						$.each( data, function( id, result ){
							jQuery('.helpers-tabs li[data-tab="' + id +'"]').find('.count').text( '(' + result.count + ')' );
							$( '#'  + id ).find('.loading').remove();
							$( '#'  + id ).prepend( result.content );
						} );

					}
				);
			},
			tab_select: function( $tab ) {
				var tab_id = $tab.attr('data-tab');

				$tab.siblings().removeClass( 'current');
				$tab.parents('.translation-helpers ').find('.helper').removeClass('current');

				$tab.addClass('current');
				$("#"+tab_id).addClass('current');
			},
			hooks: {
				fetch: function() {
					$gp.translation_helpers.fetch( $( this ) );
					return false;
				},
				tab_select: function() {
					$gp.translation_helpers.tab_select( $( this ) );
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