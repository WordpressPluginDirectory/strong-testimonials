( function ( $ ) {
	'use strict';

	$( document ).ready( function () {
		$( '#wpmtst_import_testimonials' ).click( function ( evt ) {
			evt.preventDefault();
			$( this ).addClass( 'updating-message' );
			let deleteImported = 0;
			let skipImported = 0;
			if ( $( '#wpmtst-migrator-delete-imported' ).is( ':checked' ) ) {
				deleteImported = 1;
			}
			if ( $( '#wpmtst-migrator-skip-imported' ).is( ':checked' ) ) {
				skipImported = 1;
			}
			$.ajax( {
				method: 'POST',
				// eslint-disable-next-line no-undef
				url: wpmtst.ajaxUrl,
				data: {
					action: 'wpmtst_import_from_easy_testimonials',
					// eslint-disable-next-line no-undef
					nonce: wpmtst.nonce,
					skipImported,
					deleteImported,
				},
			} ).done( function ( response ) {
				if ( response.success ) {
					$( '#wpmtst-import-response' ).removeClass(
						'wpmtst-warning'
					);
					$( '#wpmtst-import-response' ).addClass( 'wpmtst-success' );
				} else {
					$( '#wpmtst-import-response' ).removeClass(
						'wpmtst-success'
					);
					$( '#wpmtst-import-response' ).addClass( 'wpmtst-warning' );
				}
				$( '#wpmtst-import-response' ).html( response.data );
				$( '#wpmtst-import-response' ).show();
				$( '#wpmtst_import_testimonials' ).removeClass(
					'updating-message'
				);
			} );
		} );
	} );
} )( jQuery );
