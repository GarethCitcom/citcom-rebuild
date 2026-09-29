/**
 * Restyle Forminator forms with Bootstrap classes and push form events to GTM.
 * Port of assets/_dev/js/functions/forminatorBootstrap.js. Forminator only emits
 * jQuery events, so this module keeps jQuery for now.
 */
const $ = window.jQuery;

export function forminatorBootstrap() {
	$( document ).on( 'forminator:form:submit:success', function ( e, formData ) {
		if ( window.dataLayer ) {
			window.dataLayer.push( { event: 'formsuccess', formData } );
		}
	} );
	$( document ).on( 'forminator:form:submit:failed', function () {
		if ( window.dataLayer ) {
			window.dataLayer.push( { event: 'formfailed' } );
		}
	} );

	$( document ).on( 'before:forminator:form:submit', function () {
		$( '.forminator-response-message' ).addClass( 'alert alert-light rounded-4 px-4 fw-bold' );
	} );

	$( document ).on( 'forminator:form:submit:failed', function () {
		setTimeout( function () {
			$( '.forminator-error-message' ).each( function () {
				$( this ).addClass( 'invalid-feedback' );
			} );
			$( '.forminator-has_error' ).each( function () {
				$( this ).find( 'input' ).addClass( 'is-invalid' );
			} );
			$( '.forminator-response-message.forminator-error' ).addClass( 'alert-danger' );
		}, 100 );
	} );

	$( document ).on( 'forminator:form:submit:success', function () {
		setTimeout( function () {
			$( '[data-bs-dismiss=modal]' ).trigger( { type: 'click' } );
		}, 5000 );
	} );

	$( document ).on( 'after.load.forminator', function () {
		$( '.forminator-row' ).each( function () {
			$( this ).addClass( 'mb-0' );
		} );
		$( '.forminator-col:not(.forminator-field-name)' ).each( function () {
			$( this ).addClass( 'mb-3' );
		} );
		$( '.forminator-label' ).each( function () {
			$( this ).addClass( 'form-label rounded-label' );
		} );
		$( '.no-label' ).each( function () {
			$( this ).parent().addClass( 'mb-0' );
		} );
		$( '.forminator-input' ).each( function () {
			$( this ).addClass( 'form-control' );
			$( this ).addClass( 'rounded-pill px-4' );
		} );
		$( '.forminator-select--field' ).each( function () {
			$( this ).addClass( 'form-select' );
			$( this ).addClass( 'rounded-pill px-4' );
		} );
		$( '.forminator-textarea' ).each( function () {
			$( this ).addClass( 'form-control' );
			$( this ).addClass( 'rounded-3 p-4' );
		} );
		$( '.forminator-button-submit' ).each( function () {
			$( this ).addClass( 'btn btn-primary px-4 rounded-pill' );
		} );

		// Radio buttons.
		$( '.forminator-field-radio .forminator-field' ).each( function () {
			$( this ).addClass( 'form-check' );
			const mainLabel = $( this ).find( '.forminator-label' );
			mainLabel.insertBefore( $( this ) );
			$( this )
				.find( '.forminator-radio' )
				.each( function () {
					const radioInput = $( this ).find( 'input[type="radio"]' );
					const radioLabel = $( this ).find( '.forminator-radio-label' );
					const labelText = radioLabel.text();
					radioInput.addClass( 'form-check-input' );
					radioInput.insertBefore( radioLabel );
					radioLabel.addClass( 'form-check-label lh-md' ).text( labelText );
					$( this ).removeClass( 'form-check-inline' );
					$( this ).addClass( 'form-check' );
				} );
		} );

		// Range sliders.
		$( '.forminator-field-slider .forminator-field' ).each( function () {
			const mainLabel = $( this ).find( '.forminator-label' );
			mainLabel.addClass( 'form-label' );
			const slider = $( this ).find( '.forminator-slider' );
			const slide = slider.find( '.forminator-slide' );
			const valueInput = slider.find( 'input[type="hidden"]' );
			const minLabel = slider.find( '.forminator-slider-label-min' ).clone().addClass( 'small text-muted' );
			const maxLabel = slider.find( '.forminator-slider-label-max' ).clone().addClass( 'small text-muted' );
			if ( slide.length && valueInput.length ) {
				const min = slide.data( 'min' ) || 0;
				const max = slide.data( 'max' ) || 100;
				const step = slide.data( 'step' ) || 1;
				const value = valueInput.val() || min;
				const rangeInput = $( '<input type="range" class="form-range" />' );
				rangeInput.attr( {
					min,
					max,
					step,
					value,
					name: valueInput.attr( 'name' ),
					id: valueInput.attr( 'id' ),
				} );
				const sliderParent = slider.parent();
				slider.remove();
				sliderParent.append( rangeInput );
				const flexRow = $( '<div class="d-flex justify-content-between" style="margin-top:-10px"></div>' );
				flexRow.append( minLabel );
				flexRow.append( maxLabel );
				sliderParent.append( flexRow );
			}
		} );

		// Checkboxes.
		$( '.forminator-field-checkbox .forminator-field' ).each( function () {
			$( this )
				.find( '.form-check' )
				.each( function () {
					$( this ).children().unwrap();
				} );
			const checkboxes = $( this ).find( 'input[type="checkbox"]' );
			const labels = $( this ).find( 'label.forminator-checkbox' );
			labels.detach();
			checkboxes.detach();
			$( this ).empty();
			checkboxes.each(
				function ( i, checkbox ) {
					const $checkbox = $( checkbox );
					const labelId = $checkbox.attr( 'aria-labelledby' ) || $checkbox.attr( 'id' );
					const $label = labels.filter( '[for="' + $checkbox.attr( 'id' ) + '"], [id="' + labelId + '"]' ).first();
					$checkbox.addClass( 'form-check-input' );
					$label.addClass( 'form-check-label lh-md' );
					const $wrapper = $( '<div class="form-check"></div>' );
					$wrapper.append( $checkbox );
					$wrapper.append( $label );
					$( this ).append( $wrapper );
				}.bind( this )
			);
			const mainLabel = $( this ).siblings( '.forminator-label' );
			if ( mainLabel.length ) {
				mainLabel.addClass( 'mb-2 d-block' );
			}
		} );

		// Consent checkbox.
		$( '.forminator-field-consent .forminator-field' ).each( function () {
			const mainLabel = $( this ).find( '.forminator-label' );
			mainLabel.insertBefore( $( this ) );
			const elem1 = $( this ).find( '.forminator-checkbox input' );
			const elem2 = $( this ).find( '.forminator-checkbox' );
			const label = elem2.attr( 'title' );
			elem1.insertBefore( elem2 );
			elem1.addClass( 'form-check-input' );
			elem2.html( label );
			elem2.addClass( 'form-check-label lh-md' );
		} );
	} );
}
