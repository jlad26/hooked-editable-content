(function( $ ) {
	'use strict';

	/**
	 * Javascript for checking that hook is firing on front end
	 */
	
	// On document ready
	$( function() {
		
		// Function for displaying results messages
		function displayHookFireMessage( hookId, fired ) {
			if ( fired ) {
				$( '#hec-hook-fired-msg-' + hookId ).show();
			} else {
				$( '#hec-hook-failed-msg-' + hookId ).show();
			}
		}

		// Get all hook editor ids and send via ajax for checking.
		var hookIds = [];
		$( '.hec-hook-id' ).each( function() {
			hookIds.push( $(this).val() );
		});
		
		if ( hookIds.length ) {
			
			// Request data.
			$.ajax({
				url : ajaxurl,
				type : 'post',
				data : {
					action				: 'hec_check_hook_firing',
					hecHookCheckNonce	: hooked_editable_content.hecHookCheckNonce,
					hookIds				: hookIds,
					postID				: $('input#post_ID').val()
				},
				xhrFields: {
					withCredentials: true
				},
				success : function( response ) {
					if ( 'undefined' != typeof( response ) ) {
						var hookChecks = $.parseJSON( response );
						if ( 'object' == typeof( hookChecks ) ) {
							for ( var i = 0; i < hookIds.length; i++ ) {
								if ( 'undefined' != typeof( hookChecks[ hookIds[i] ] ) ) {
									displayHookFireMessage( hookIds[i], hookChecks[ hookIds[i] ] );
								}
								
							}
						}
					}
				}
			});
			
		}
		
	});

})( jQuery );
