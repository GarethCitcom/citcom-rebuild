/**
 * Theme forms (inc/forms.php): conditional fields, the "Other" text box,
 * submission over the REST API, field errors and the thank-you message.
 * No jQuery.
 *
 * On the default (Bootstrap) forms the message and error classes are the ones
 * the old forminator-bootstrap.js put on Forminator's markup, so the states
 * look as they did; the "classic" forms have their own (elements/_forms.scss).
 * The GTM events keep their names (formsuccess, formfailed); the form's slug is
 * sent instead of the submitted data.
 */
const pageLoaded = Date.now();

function controls( form, name ) {
	return Array.from( form.querySelectorAll( '[name="' + name + '"], [name="' + name + '[]"]' ) );
}

// A condition holds when the controlling field has the wanted value ticked or
// selected, or, with no wanted value, when it is ticked at all.
function conditionMet( form, name, value ) {
	return controls( form, name ).some( ( control ) => {
		if ( control.type === 'checkbox' || control.type === 'radio' ) {
			return control.checked && ( value === undefined || control.value === value );
		}
		return value === undefined ? control.value !== '' : control.value === value;
	} );
}

function toggleConditional( form ) {
	form.querySelectorAll( '[data-show-if]' ).forEach( ( el ) => {
		el.hidden = ! conditionMet( form, el.dataset.showIf, el.dataset.showIfValue );
	} );
	form.querySelectorAll( '[data-other-for]' ).forEach( ( el ) => {
		el.hidden = ! conditionMet( form, el.dataset.otherFor, 'other' );
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
	// Classic forms draw their own message box; every other form uses the alert.
	const classic = form.classList.contains( 'citcom-form--classic' );
	let state = ' alert alert-light rounded-4 px-4 fw-bold' + ( isError ? ' alert-danger' : '' );
	if ( classic ) {
		state = isError ? ' is-error' : ' is-success';
	}
	box.className = 'citcom-form-message' + state;
	box.textContent = text;
	box.hidden = false;
	box.scrollIntoView( { block: 'nearest', behavior: 'smooth' } );
}

function showFieldErrors( form, errors ) {
	Object.entries( errors ).forEach( ( [ name, text ] ) => {
		const wrapper = form.querySelector( '[data-field="' + name + '"]' );
		if ( ! wrapper ) {
			return;
		}
		controls( form, name ).forEach( ( control ) => {
			control.classList.add( 'is-invalid' );
			control.setAttribute( 'aria-invalid', 'true' );
		} );
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
		toggleConditional( form );
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
	document.querySelectorAll( 'form[data-citcom-form]' ).forEach( toggleConditional );

	document.addEventListener( 'change', ( e ) => {
		const form = e.target.closest ? e.target.closest( 'form[data-citcom-form]' ) : null;
		if ( form ) {
			toggleConditional( form );
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
