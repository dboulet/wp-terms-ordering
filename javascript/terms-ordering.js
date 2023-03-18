/* Modifided script from the simple-page-ordering plugin */
/* global ajaxurl wpTermsOrdering */
jQuery( document ).ready( function( $ ) {

	$( 'table.widefat.wp-list-table tbody' ).find( 'th, td' ).css( 'cursor', 'move' ).end().sortable( {
		items: 'tr:not(.inline-edit-row)',
		cursor: 'move',
		axis: 'y',
		containment: 'table.widefat',
		placeholder: 'product-cat-placeholder',
		scrollSensitivity: 40,
		helper: function( event, ui ) {
			ui.children().each( function() {
				$( this ).width( $( this ).width() );
			} );

			return ui;
		},
		start: function( event, ui ) {
			ui.item.css( { backgroundColor: '#fff', outline: '1px solid #aaa' } );
		},
		update: function( event, ui ) {
			var termId = ui.item.find( '.check-column input' ).val();	// The term’s ID.
			var termParent = ui.item.find( '.parent' ).html(); // The term’s parent ID.
			var prevTermId = ui.item.prev().find( '.check-column input' ).val();
			var nextTermId = ui.item.next().find( '.check-column input' ).val();
			var prevTermParent;
			var nextTermParent;

			if ( termId === undefined ) {
				termId = 1;
			}

			if ( prevTermId !== undefined ) {
				prevTermParent = ui.item.prev().find( '.parent' ).html();

				if ( prevTermParent !== termParent ) {
					prevTermId = undefined;
				}
			}

			if ( nextTermId !== undefined ) {
				nextTermParent = ui.item.next().find( '.parent' ).html();

				if ( nextTermParent !== termParent ) {
					nextTermId = undefined;
				}
			}

			// If previous and next not at same tree level, or next not at same tree
			// level and the previous is the parent of the next, or just moved item
			// beneath its own children.
			if (
				( prevTermId === undefined && nextTermId === undefined ) ||
				( nextTermId === undefined && nextTermParent === prevTermId ) ||
				( nextTermId !== undefined && prevTermParent === termId )
			) {
				$( this ).sortable( 'cancel' );
				return;
			}

			// Show spinner.
			ui.item.find( '.check-column input' ).hide().after( '<img alt="processing" src="images/wpspin_light-2x.gif" class="waiting" style="margin-left: 6px; width: 16px;">' );

			// Go do the sorting stuff via ajax.
			$.post( ajaxurl, {
				action: 'terms-ordering',
				id: termId,
				nextid: nextTermId,
				taxonomy: wpTermsOrdering.taxonomy,
				nonce: wpTermsOrdering.nonce,
			}, function( response ) {
				if ( response === 'children' ) {
					window.location.reload();
				} else {
					ui.item.find( '.check-column input' ).show().siblings( 'img' ).remove();
				}
			} );

		},
		stop: function( event, ui ) {
			// Remove styles which were added in the 'start' event.
			ui.item.removeAttr( 'style' );
		},
	} );
} );
