/* Modifided script from the simple-page-ordering plugin */
/* global ajaxurl wpTermsOrdering */
jQuery( document ).ready( function( $ ) {
	$( 'table.widefat.wp-list-table tbody th, table.widefat tbody td' ).css( 'cursor', 'move' );

	$( 'table.widefat.wp-list-table tbody' ).sortable( {
		items: 'tr:not(.inline-edit-row)',
		cursor: 'move',
		axis: 'y',
		containment: 'table.widefat',
		placeholder: 'product-cat-placeholder',
		scrollSensitivity: 40,
		helper: function( e, ui ) {
			ui.children().each( function() {
				jQuery( this ).width( jQuery( this ).width() );
			} );

			return ui;
		},
		start: function( event, ui ) {
			if ( ! ui.item.hasClass( 'alternate' ) ) {
				ui.item.css( 'background-color', '#fff' );
			}

			ui.item.children( 'td, th' ).css( 'border-bottom-width', '0' );
			ui.item.css( 'outline', '1px solid #aaa' );
		},
		stop: function( event, ui ) {
			ui.item.removeAttr( 'style' );
			ui.item.children( 'td, th' ).css( 'border-bottom-width', '1px' );
		},
		update: function( event, ui ) {
			var termId = ui.item.find( '.check-column input' ).val();	// This post id.
			var termParent = ui.item.find( '.parent' ).html(); // Post parent.
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
				$( 'table.widefat tbody' ).sortable( 'cancel' );
				return;
			}

			// Show spinner.
			ui.item.find( '.check-column input' ).hide().after( '<img alt="processing" src="images/wpspin_light.gif" class="waiting" style="margin-left: 6px;">' );

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

			// Fix cell colors.
			$( 'table.widefat tbody tr' ).each( function( index ) {
				if ( index % 2 === 0 ) {
					$( this ).addClass( 'alternate' );
				} else {
					$( this ).removeClass( 'alternate' );
				}
			} );
		},
	} );
} );
