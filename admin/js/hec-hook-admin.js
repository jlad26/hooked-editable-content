(function( $ ) {
	'use strict';

	/**
	 * Javascript for hook post type edit screen
	 */
	
	// On document ready
	$( function() {
		
		// Check 'own' permissions tickbox when 'others' is set
		$( '#hec-hook-editing-permissions-display-mb td.hec-others input' ).click( function() {
			if ( $(this).prop( 'checked' ) ) {
				$(this).closest( 'tr' ).find( '.hec-own input' ).prop( 'checked', true );
			}
		});
		
		// Show / hide filter content placement dropdown depending on choice of hook type
		function showHideContentDropdown() {
			var container = $( '#hec-hook-filter_content_placement' ).closest( 'div' );
			if ( 'action' == $( '#hec-hook-type' ).val() ) {
				container.addClass( 'hide-if-js' );
			} else {
				container.removeClass( 'hide-if-js' );
			}
		}
		
		$( '#hec-hook-type' ).change( function() {
			showHideContentDropdown()
		});
		showHideContentDropdown();
		
		// Show / hide Disble wpautop checkbox depending on choice of editor
		function showHideWpautopChkbox() {
			var container = $( '#hec-hook-disable_wpautop' ).closest( 'div' );
			if ( 'wp' == $( '#hec-hook-editor' ).val() ) {
				container.removeClass( 'hide-if-js' );
			} else {
				container.addClass( 'hide-if-js' );
			}
		}
		showHideWpautopChkbox();
		
		// Switch editor types on change of dropdown
		$( '#hec-hook-editor' ).change( function() {
			showHideWpautopChkbox();
			var editor = $(this).val();
			var hideEditor, contentContainer, newContainer;
			if ( 'text' == editor ) {
				hideEditor = 'wp';
				contentContainer = $( '#content' );
				newContainer = $( '#hec-generic-content-text-editor' );
				
				// Switch between text editor and visual editor to update the text editor contents
				if ( 'none' == contentContainer.css( 'display' ) ) {
					$( '#content-html' ).click();
					$( '#content-tmce' ).click();
				}
				
			} else {
				hideEditor = 'text';
				contentContainer = $( '#hec-generic-content-text-editor' );
				newContainer = $( '#content' );
			}

			$( '#hec-generic-content-' + editor + '-editor' ).removeClass( 'hec-hide-editor' );
			$( '#hec-generic-content-' + hideEditor + '-editor' ).addClass( 'hec-hide-editor' );
			$( newContainer ).val( contentContainer.val() );
			$(window).scrollTop($(window).scrollTop()+1);
			
		});
		
	});

})( jQuery );
