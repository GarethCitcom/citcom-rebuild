/**
 * citcom_focuspoint ACF field: choose an image, click it to place the focal point.
 * Runs in the classic editor, the block editor sidebar and ACF block previews.
 */
( function ( $ ) {
	if ( typeof acf === 'undefined' ) {
		return;
	}

	var Field = acf.Field.extend( {
		type: 'citcom_focuspoint',

		events: {
			'click [data-name="add"]': 'onClickAdd',
			'click [data-name="remove"]': 'onClickRemove',
			'click .citcom-focuspoint-picker': 'onClickPicker',
		},

		$control: function () {
			return this.$( '.citcom-focuspoint' );
		},

		$input: function () {
			return this.$( 'input[data-name="id"]' );
		},

		getValue: function () {
			return this.$input().val();
		},

		initialize: function () {
			this.render();
		},

		render: function () {
			var id = this.$input().val();
			this.$control().toggleClass( 'has-value', !! id );
			this.positionMarker();
		},

		positionMarker: function () {
			var top = this.$( 'input[data-name="top"]' ).val() || '50';
			var left = this.$( 'input[data-name="left"]' ).val() || '50';
			this.$( '.citcom-focuspoint-marker' ).css( { top: top + '%', left: left + '%' } );
			this.$( '[data-name="readout"]' ).text( left + '% ' + top + '%' );
		},

		onClickAdd: function ( e ) {
			e.preventDefault();
			var self = this;
			var $control = this.$control();
			acf.newMediaPopup( {
				mode: 'select',
				type: 'image',
				title: acf.__( 'Select Image' ),
				field: this.get( 'key' ),
				multiple: false,
				library: $control.data( 'library' ) || 'all',
				allowedTypes: $control.data( 'mime_types' ) || '',
				select: function ( attachment ) {
					self.select( attachment );
				},
			} );
		},

		select: function ( attachment ) {
			var a = attachment.attributes || attachment;
			var sizes = a.sizes || {};
			var wanted = this.$control().data( 'preview_size' ) || 'large';
			var src = ( sizes[ wanted ] && sizes[ wanted ].url ) || ( sizes.large && sizes.large.url ) || a.url;

			this.$input().val( a.id );
			this.$( 'img[data-name="image"]' ).attr( 'src', src ).attr( 'alt', a.alt || '' );
			if ( ! this.$( 'input[data-name="top"]' ).val() ) {
				this.$( 'input[data-name="top"]' ).val( '50' );
			}
			if ( ! this.$( 'input[data-name="left"]' ).val() ) {
				this.$( 'input[data-name="left"]' ).val( '50' );
			}
			this.render();
			this.$input().trigger( 'change' );
		},

		onClickRemove: function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.$input().val( '' );
			this.$( 'input[data-name="top"]' ).val( '' );
			this.$( 'input[data-name="left"]' ).val( '' );
			this.$( 'img[data-name="image"]' ).attr( 'src', '' ).attr( 'alt', '' );
			this.render();
			this.$input().trigger( 'change' );
		},

		onClickPicker: function ( e ) {
			if ( ! this.$input().val() ) {
				return;
			}
			var $img = this.$( 'img[data-name="image"]' );
			var rect = $img[ 0 ].getBoundingClientRect();
			if ( ! rect.width || ! rect.height ) {
				return;
			}
			var clamp = function ( n ) {
				return Math.max( 0, Math.min( 100, n ) ).toFixed( 2 );
			};
			var left = clamp( ( ( e.clientX - rect.left ) / rect.width ) * 100 );
			var top = clamp( ( ( e.clientY - rect.top ) / rect.height ) * 100 );
			this.$( 'input[data-name="left"]' ).val( left );
			this.$( 'input[data-name="top"]' ).val( top );
			this.positionMarker();
			this.$input().trigger( 'change' );
		},
	} );

	acf.registerFieldType( Field );
} )( jQuery );
