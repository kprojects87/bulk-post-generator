( function ( $ ) {
	'use strict';

	$( function () {
		var $btn          = $( '#bpg-generate-btn' );
		var $progressWrap = $( '#bpg-progress' );
		var $progressFill = $( '#bpg-progress-fill' );
		var $progressLabel = $( '#bpg-progress-label' );
		var $results       = $( '#bpg-results' );

		// Toggle the "Other" business-type text field next to each matching select.
		var businessTypePairs = [
			{ select: '#bpg-business-type', wrap: '#bpg-business-type-other-wrap' },
			{ select: '#business_type', wrap: '#business_type_other_wrap' }
		];
		businessTypePairs.forEach( function ( pair ) {
			var $select = $( pair.select );
			var $wrap   = $( pair.wrap );
			if ( ! $select.length ) {
				return;
			}
			// If a custom "Other" value was previously saved, pre-fill the text field.
			var $preset = $select.siblings( '.bpg-business-type-preset-other' );
			if ( $preset.length ) {
				$wrap.find( 'input[type="text"]' ).val( $preset.val() );
			}
			function sync() {
				$wrap.toggle( 'other' === $select.val() );
			}
			$select.on( 'change', sync );
			sync();
		} );

		if ( ! $btn.length ) {
			return;
		}

		$btn.on( 'click', function ( e ) {
			e.preventDefault();

			var niche         = $.trim( $( '#bpg-niche' ).val() );
			var keywords      = $.trim( $( '#bpg-keywords' ).val() );
			var count         = parseInt( $( '#bpg-count' ).val(), 10 ) || 6;
			var status        = $( '#bpg-status' ).val();
			var businessName  = $.trim( $( '#bpg-business-name' ).val() );
			var businessType  = $( '#bpg-business-type' ).val();
			var businessOther = $.trim( $( '#bpg-business-type-other' ).val() );

			if ( ! niche ) {
				alert( 'Please enter a topic/niche first.' );
				return;
			}

			$results.empty();
			$progressWrap.show();
			$progressFill.css( 'width', '5%' );
			$progressLabel.text( BPG.i18n.gettingTopics );
			$btn.prop( 'disabled', true );

			var meta = {
				status: status,
				business_name: businessName,
				business_type: businessType,
				business_type_other: businessOther
			};

			$.post( BPG.ajaxUrl, $.extend( {
				action: 'bpg_get_topics',
				nonce: BPG.nonce,
				niche: niche,
				keywords: keywords,
				count: count
			}, meta ) ).done( function ( response ) {
				if ( ! response.success ) {
					showError( response.data.message || BPG.i18n.error );
					resetButton();
					return;
				}
				generateSequentially( response.data.topics, niche, meta, 0 );
			} ).fail( function () {
				showError( BPG.i18n.error );
				resetButton();
			} );
		} );

		function generateSequentially( topics, niche, meta, index ) {
			if ( index >= topics.length ) {
				$progressFill.css( 'width', '100%' );
				$progressLabel.text( BPG.i18n.done );
				resetButton();
				return;
			}

			var title = topics[ index ];
			var pct   = Math.round( ( index / topics.length ) * 100 );
			$progressFill.css( 'width', pct + '%' );
			$progressLabel.text( BPG.i18n.writing + ' ' + ( index + 1 ) + '/' + topics.length + ': ' + title );

			$.post( BPG.ajaxUrl, $.extend( {
				action: 'bpg_generate_post',
				nonce: BPG.nonce,
				title: title,
				niche: niche
			}, meta ) ).done( function ( response ) {
				if ( response.success ) {
					showSuccess( response.data.title, response.data.edit_link, response.data.has_image );
				} else {
					showError( ( response.data && response.data.message ) || ( 'Failed: ' + title ) );
				}
			} ).fail( function () {
				showError( 'Failed: ' + title );
			} ).always( function () {
				generateSequentially( topics, niche, meta, index + 1 );
			} );
		}

		function showSuccess( title, editLink, hasImage ) {
			$results.append(
				$( '<li>' ).append(
					$( '<span>' ).text( ( hasImage ? '🖼️ ' : '' ) + title ),
					$( '<a>' ).attr( 'href', editLink ).attr( 'target', '_blank' ).text( 'Edit draft →' )
				)
			);
		}

		function showError( message ) {
			$results.append(
				$( '<li>' ).addClass( 'bpg-result-error' ).text( message )
			);
		}

		function resetButton() {
			$btn.prop( 'disabled', false );
		}
	} );
} )( jQuery );
