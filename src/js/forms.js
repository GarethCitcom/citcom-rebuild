/**
 * Theme forms (inc/forms.php): conditional rows, submission over the REST API,
 * field errors and the thank-you message. No jQuery.
 *
 * The message and error classes are the ones the old forminator-bootstrap.js
 * put on Forminator's markup, so the states look as they did. The GTM events
 * keep their names (formsuccess, formfailed); the form's slug is sent instead
 * of the submitted data.
 */
const pageLoaded = Date.now();

function toggleConditionalRows( form ) {
	form.querySelectorAll( '[data-show-if]' ).forEach( ( row ) => {
		const controller = form.elements[ row.dataset.showIf ];
		row.hidden = ! ( controller && controller.checked );
	} );
}

function clearErrors( form ) {
	form.querySelectorAll( '.citcom-form-error' ).forEach( ( el ) => el.remove() );
	form.querySelectorAll( '.is-invalid' ).forEach( ( el ) => {
		el.classList.remove( 'is-invalid' );
		el.removeAttribute( 'aria-invalid' );
	} );
}

function showMessage( form, text, isError ) {
	const box = form.querySelector( '.citcom-form-message' );
	if ( ! box ) {
		return;
	}
	box.className = 'citcom-form-message alert alert-light rounded-4 px-4 fw-bold' + ( isError ? ' alert-danger' : '' );
	box.textContent = text;
	box.hidden = false;
}

function showFieldErrors( form, errors ) {
	Object.entries( errors ).forEach( ( [ name, text ] ) => {
		const control = form.elements[ name ];
		const wrapper = form.querySelector( '[data-field="' + name + '"]' );
		if ( ! control || ! wrapper ) {
			return;
		}
		control.classList.add( 'is-invalid' );
		control.setAttribute( 'aria-invalid', 'true' );
		const message = document.createElement( 'span' );
		message.className = 'citcom-form-error invalid-feedback d-block';
		message.textContent = text;
		wrapper.appendChild( message );
	} );
	const first = form.querySelector( '.is-invalid' );
	if ( first ) {
		first.focus();
	}
}

async function submit( form ) {
	const button = form.querySelector( '.citcom-form-submit' );
	const data = new FormData( form );
	data.append( '_elapsed', String( Date.now() - pageLoaded ) );
	data.append( '_page', window.location.href );

	clearErrors( form );
	form.setAttribute( 'aria-busy', 'true' );
	if ( button ) {
		button.disabled = true;
	}

	let result = null;
	try {
		const response = await fetch( form.action, { method: 'POST', body: data, headers: { Accept: 'application/json' } } );
		result = await response.json();
	} catch ( e ) {
		result = null;
	}

	form.removeAttribute( 'aria-busy' );
	if ( button ) {
		button.disabled = false;
	}

	if ( result && result.success ) {
		showMessage( form, result.message, false );
		form.reset();
		toggleConditionalRows( form );
		if ( window.dataLayer ) {
			window.dataLayer.push( { event: 'formsuccess', form: form.dataset.citcomForm } );
		}
		// In a popup: close it once the message has been read, as before.
		const modal = form.closest( '.modal' );
		if ( modal ) {
			setTimeout( () => {
				const close = modal.querySelector( '[data-bs-dismiss="modal"]' );
				if ( close ) {
					close.click();
				}
			}, 5000 );
		}
		return;
	}

	showMessage( form, ( result && result.message ) || 'Sorry, something went wrong. Please try again.', true );
	if ( result && result.errors ) {
		showFieldErrors( form, result.errors );
	}
	if ( window.dataLayer ) {
		window.dataLayer.push( { event: 'formfailed', form: form.dataset.citcomForm } );
	}
}

export function forms() {
	document.querySelectorAll( 'form[data-citcom-form]' ).forEach( toggleConditionalRows );

	document.addEventListener( 'change', ( e ) => {
		const form = e.target.closest ? e.target.closest( 'form[data-citcom-form]' ) : null;
		if ( form ) {
			toggleConditionalRows( form );
		}
	} );

	document.addEventListener( 'submit', ( e ) => {
		const form = e.target.closest ? e.target.closest( 'form[data-citcom-form]' ) : null;
		if ( ! form ) {
			return;
		}
		e.preventDefault();
		submit( form );
	} );
}
