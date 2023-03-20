/* Modifided script from the simple-page-ordering plugin */
/* global ajaxurl wpTermsOrdering */
jQuery( document ).ready( function( $ ) {
	var termDescendants;

	/**
	 * Find term descendants
	 *
	 * Given an item which represents a term in a sortable table, find all of the
	 * items which represent its descendant terms.
	 *
	 * @param {jQuery} item The item being sorted.
	 * @return {jQuery|null} The item’s descendants, or null if there are none.
	 */
	function findDescendants( item ) {
		var matches = item.attr( 'class' ).match( /\blevel-(\d+)\b/ ) || [];
		var i;
		var s = [];

		if ( matches.length > 1 ) {
			// Build an array of selector strings which will match items which are
			// NOT descendants.
			for ( i = Number( matches[1] ); i >= 0; i-- ) {
				s.push( '.level-' + i.toString() );
			}
			return item.nextUntil( s.join() );
		}

		return null;
	}

	/**
	 * Get term ID
	 *
	 * Given an item which represents a term in a sortable table,
	 * return its term ID.
	 *
	 * @param {jQuery} item The item being sorted.
	 * @return {number|void} The ID.
	 */
	function getTermId( item ) {
		if ( item.length ) {
			return (
				item.filter( '[id^=tag-]' ).attr( 'id' ).replace( /^tag-/, '' ) ||
				item.find( '.check-column input' ).val() ||
				1
			);
		}
	}

	/**
	 * Get term parent’s ID
	 *
	 * Given an item which represents a term in a sortable table,
	 * return its parent’s term ID.
	 *
	 * @param {jQuery} item The item being sorted.
	 * @return {number|void} The parent’s ID.
	 */
	function getParentTermId( item ) {
		if ( item.length ) {
			return item.find( '.parent' ).html() || 0;
		}
	}

	$( 'table.widefat.wp-list-table tbody' ).find( 'th, td' ).css( 'cursor', 'move' ).end().sortable( {
		items: 'tr:not(.inline-edit-row)',
		cursor: 'move',
		axis: 'y',
		containment: 'table.widefat',
		placeholder: 'product-cat-placeholder',
		scrollSensitivity: 40,
		helper: function( event, element ) {
			// Take this opportunity to find the term’s descendants, to use later.
			termDescendants = findDescendants( element );

			element.children().each( function() {
				$( this ).width( $( this ).width() );
			} );

			return element;
		},
		start: function( event, ui ) {
			ui.item.css( { backgroundColor: '#fff', outline: '1px solid #aaa' } );
		},
		update: function( event, ui ) {
			var item = ui.item;
			var termId = getTermId( item );
			var termParentId = getParentTermId( item );
			var prevTermId = getTermId( item.prev() );
			var nextTermId = getTermId( item.next() );
			var prevTermParentId;
			var nextTermParentId;

			if ( prevTermId !== undefined ) {
				prevTermParentId = getParentTermId( item.prev() );

				if ( prevTermParentId !== termParentId ) {
					prevTermId = undefined;
				}
			}

			if ( nextTermId !== undefined ) {
				nextTermParentId = getParentTermId( item.next() );

				if ( nextTermParentId !== termParentId ) {
					nextTermId = undefined;
				}
			}

			// If previous and next not at same tree level, or next not at same tree
			// level and the previous is the parent of the next, or just moved item
			// beneath its own children.
			if (
				( prevTermId === undefined && nextTermId === undefined ) ||
				( nextTermId === undefined && nextTermParentId === prevTermId ) ||
				( nextTermId !== undefined && prevTermParentId === termId )
			) {
				$( this ).sortable( 'cancel' );
				return;
			}

			// Show spinner.
			item.find( '.check-column input' ).hide().after( '<img alt="processing" src="images/wpspin_light-2x.gif" class="waiting" style="margin-left: 6px; width: 16px;">' );

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
					item.find( '.check-column input' ).show().siblings( 'img' ).remove();
				}
			} );

			// Fix order of children.
			if ( termDescendants ) {
				item.after( termDescendants );
			}
		},
		stop: function( event, ui ) {
			// Remove styles which were added in the 'start' event.
			ui.item.removeAttr( 'style' );
		},
	} );
} );
