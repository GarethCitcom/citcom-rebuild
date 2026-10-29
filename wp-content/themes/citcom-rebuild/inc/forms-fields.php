<?php
/**
 * Theme forms: fields. Rendering, validation and the printable summary of a
 * submission, for every field type a form definition (forms/*.php) can use:
 *
 *   text, email, tel, url, textarea, select, range,
 *   checkbox (one tick box), checkboxes (a group, optionally with "Other"),
 *   radio, html (static text).
 *
 * A field can carry: label, required, required_message, placeholder,
 * description, autocomplete, options (value => label), default, other (bool,
 * checkboxes only), min / max / step / min_label / max_label (range), and
 * show_if: the name of a tick box, or array( 'field' => name, 'value' => v )
 * for a group or select. A hidden field is neither required nor stored.
 *
 * Three variants of the markup:
 * - "bootstrap" (default): the Bootstrap classes the old forminator-bootstrap.js
 *   put on Forminator's forms.
 * - "plain": no styling classes; the diner guest check styles it itself.
 * - "classic": Forminator's own "default" design, which the packages and client
 *   survey forms used (src/scss/elements/_forms.scss).
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

/**
 * A field's show_if as array( controlling field, value or null ).
 *
 * @return array{0:string,1:?string}
 */
function citcom_form_condition( array $field ): array {
	$condition = $field['show_if'] ?? '';
	if ( is_array( $condition ) ) {
		return array( (string) ( $condition['field'] ?? '' ), isset( $condition['value'] ) ? (string) $condition['value'] : null );
	}
	return array( (string) $condition, null );
}

/**
 * Whether a field's show_if condition is met by the submitted values.
 */
function citcom_form_field_shown( array $field, array $values ): bool {
	list( $controller, $value ) = citcom_form_condition( $field );
	if ( '' === $controller ) {
		return true;
	}
	$current = $values[ $controller ] ?? null;
	if ( null === $value ) {
		return ! empty( $current );
	}
	return is_array( $current ) ? in_array( $value, $current, true ) : (string) $current === $value;
}

/**
 * The options of a choice field, with "Other" appended when the field allows it.
 *
 * @return array<string,string>
 */
function citcom_form_options( array $field ): array {
	$options = array();
	foreach ( (array) ( $field['options'] ?? array() ) as $value => $label ) {
		$options[ (string) $value ] = (string) $label;
	}
	if ( ! empty( $field['other'] ) ) {
		$options['other'] = __( 'Other', 'citcom' );
	}
	return $options;
}

/**
 * Markup of a form.
 *
 * @param string|int $id   Slug or legacy Forminator id.
 * @param array      $args variant (bootstrap|plain|classic), submit (button label override).
 * @return string Empty when the form does not exist.
 */
function citcom_render_form( $id, array $args = array() ): string {
	$form = citcom_form( $id );
	if ( ! $form ) {
		return '';
	}
	$variant   = (string) ( $args['variant'] ?? $form['variant'] ?? 'bootstrap' );
	$bootstrap = 'bootstrap' === $variant;
	$instance  = 'cf' . citcom_counter( 'form' );
	$class     = static function ( string $own, string $boot ) use ( $bootstrap ): string {
		return $bootstrap ? trim( $own . ' ' . $boot ) : $own;
	};
	$show_attr = static function ( array $field ): string {
		list( $controller, $value ) = citcom_form_condition( $field );
		if ( '' === $controller ) {
			return '';
		}
		return ' data-show-if="' . esc_attr( $controller ) . '"' . ( null !== $value ? ' data-show-if-value="' . esc_attr( $value ) . '"' : '' ) . ' hidden';
	};

	ob_start();
	?>
	<form class="citcom-form citcom-form--<?php echo esc_attr( $form['slug'] ); ?><?php echo $bootstrap ? '' : ' citcom-form--' . esc_attr( $variant ); ?>" data-citcom-form="<?php echo esc_attr( $form['slug'] ); ?>" method="post" action="<?php echo esc_url( rest_url( 'citcom/v1/forms/' . $form['slug'] ) ); ?>" novalidate>
		<div class="citcom-form-message" role="alert" aria-live="polite" hidden></div>
		<?php
		foreach ( (array) $form['rows'] as $row ) :
			$row = array_values( (array) $row );

			// A row is hidden as a whole when all its fields share one condition;
			// otherwise each conditional field hides on its own.
			$conditions = array_unique( array_map( static fn( $f ) => wp_json_encode( citcom_form_condition( (array) $f ) ), $row ) );
			$row_level  = 1 === count( $conditions ) && '' !== citcom_form_condition( $row[0] )[0];
			?>
			<div class="<?php echo esc_attr( $class( 'citcom-form-row', 'mb-0' ) ); ?>"<?php echo $row_level ? $show_attr( $row[0] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php
				foreach ( $row as $field ) :
					$type        = (string) ( $field['type'] ?? 'text' );
					$name        = (string) ( $field['name'] ?? '' );
					$label       = (string) ( $field['label'] ?? '' );
					$required    = ! empty( $field['required'] );
					$field_id    = $instance . '-' . $name;
					$star        = $required ? ' <span class="citcom-form-required">*</span>' : '';
					$hidden      = $row_level ? '' : $show_attr( $field );
					$description = (string) ( $field['description'] ?? '' );
					$describe    = '' !== $description ? ' aria-describedby="' . esc_attr( $field_id ) . '-description"' : '';
					// "grid_margin": the column keeps Forminator's own grid spacing instead of
					// Bootstrap's mb-3. The old script skipped Forminator's single "name" field
					// when it added mb-3, which only shows once the columns stack.
					$grid_margin = ! empty( $field['grid_margin'] );
					$col         = $class( 'citcom-form-col citcom-form-field citcom-form-field--' . $type . ( $grid_margin ? ' citcom-form-col--grid' : '' ), $grid_margin ? '' : 'mb-3' );

					if ( 'html' === $type ) :
						?>
						<div class="<?php echo esc_attr( $col . ' no-label' ); ?>"<?php echo $hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<?php echo wp_kses_post( (string) ( $field['html'] ?? '' ) ); ?>
						</div>
						<?php
						continue;
					endif;

					if ( 'checkbox' === $type ) :
						?>
						<div class="<?php echo esc_attr( $col . ' no-label' ); ?>" data-field="<?php echo esc_attr( $name ); ?>"<?php echo $hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<?php if ( ! empty( $field['group_label'] ) ) : ?>
								<span class="visually-hidden" id="<?php echo esc_attr( $field_id ); ?>-group"><?php echo esc_html( $field['group_label'] ); ?></span>
							<?php endif; ?>
							<?php if ( $bootstrap ) : ?>
								<div class="form-check">
									<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" id="<?php echo esc_attr( $field_id ); ?>" class="form-check-input"<?php echo $required ? ' required aria-required="true"' : ''; ?><?php echo ! empty( $field['group_label'] ) ? ' aria-describedby="' . esc_attr( $field_id ) . '-group"' : ''; ?>>
									<label for="<?php echo esc_attr( $field_id ); ?>" class="citcom-form-checkbox form-check-label lh-md"><span class="citcom-form-checkbox-label"><?php echo esc_html( $label ); ?></span></label>
								</div>
							<?php else : ?>
								<label for="<?php echo esc_attr( $field_id ); ?>" class="citcom-form-checkbox">
									<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" id="<?php echo esc_attr( $field_id ); ?>"<?php echo $required ? ' required aria-required="true"' : ''; ?>>
									<span class="citcom-form-checkbox-box" aria-hidden="true"></span>
									<span class="citcom-form-checkbox-label"><?php echo esc_html( $label ); ?></span>
								</label>
							<?php endif; ?>
						</div>
						<?php
						continue;
					endif;

					if ( 'checkboxes' === $type || 'radio' === $type ) :
						$is_radio = 'radio' === $type;
						$input    = $is_radio ? 'radio' : 'checkbox';
						$part     = $is_radio ? 'radio' : 'checkbox';
						$mark     = $is_radio ? 'citcom-form-radio-bullet' : 'citcom-form-checkbox-box';
						?>
						<div class="<?php echo esc_attr( $col ); ?>" data-field="<?php echo esc_attr( $name ); ?>" role="<?php echo $is_radio ? 'radiogroup' : 'group'; ?>" aria-labelledby="<?php echo esc_attr( $field_id ); ?>-label"<?php echo $hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<span class="<?php echo esc_attr( $class( 'citcom-form-label', 'form-label rounded-label d-block' ) ); ?>" id="<?php echo esc_attr( $field_id ); ?>-label"><?php echo esc_html( $label ) . $star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php
							$n = 0;
							foreach ( citcom_form_options( $field ) as $value => $text ) :
								++$n;
								$option_id  = $field_id . '-' . $n;
								$input_attr = 'type="' . $input . '" name="' . esc_attr( $name . ( $is_radio ? '' : '[]' ) ) . '" value="' . esc_attr( $value ) . '" id="' . esc_attr( $option_id ) . '"';
								?>
								<?php if ( $bootstrap ) : ?>
									<div class="form-check">
										<input <?php echo $input_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="form-check-input">
										<label for="<?php echo esc_attr( $option_id ); ?>" class="citcom-form-<?php echo esc_attr( $part ); ?> form-check-label lh-md"><span class="citcom-form-<?php echo esc_attr( $part ); ?>-label"><?php echo esc_html( $text ); ?></span></label>
									</div>
								<?php else : ?>
									<label for="<?php echo esc_attr( $option_id ); ?>" class="citcom-form-<?php echo esc_attr( $part ); ?>">
										<input <?php echo $input_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
										<span class="<?php echo esc_attr( $mark ); ?>" aria-hidden="true"></span>
										<span class="citcom-form-<?php echo esc_attr( $part ); ?>-label"><?php echo esc_html( $text ); ?></span>
									</label>
								<?php endif; ?>
							<?php endforeach; ?>
							<?php if ( ! $is_radio && ! empty( $field['other'] ) ) : ?>
								<div class="citcom-form-custom-input" data-other-for="<?php echo esc_attr( $name ); ?>" hidden>
									<input type="text" name="<?php echo esc_attr( $name ); ?>_other" class="<?php echo esc_attr( $class( 'citcom-form-input', 'form-control rounded-pill px-4' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( '%s: %s', $label, __( 'Other', 'citcom' ) ) ); ?>">
								</div>
							<?php endif; ?>
						</div>
						<?php
						continue;
					endif;

					$common = ' name="' . esc_attr( $name ) . '" id="' . esc_attr( $field_id ) . '"'
						. ( $required ? ' required aria-required="true"' : '' )
						. ( ! empty( $field['autocomplete'] ) ? ' autocomplete="' . esc_attr( $field['autocomplete'] ) . '"' : '' )
						. $describe;
					?>
					<div class="<?php echo esc_attr( $col ); ?>" data-field="<?php echo esc_attr( $name ); ?>"<?php echo $hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<label for="<?php echo esc_attr( $field_id ); ?>" class="<?php echo esc_attr( $class( 'citcom-form-label', 'form-label rounded-label' ) ); ?>"><?php echo esc_html( $label ) . $star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
						<?php if ( '' !== $description ) : ?>
							<?php
							// Bootstrap forms had the description on its own line under the label.
							?>
							<?php echo $bootstrap ? '<div>' : ''; ?><span class="citcom-form-description" id="<?php echo esc_attr( $field_id ); ?>-description"><?php echo esc_html( $description ); ?></span><?php echo $bootstrap ? '</div>' : ''; ?>
						<?php endif; ?>
						<?php if ( 'textarea' === $type ) : ?>
							<textarea<?php echo $common; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> placeholder="<?php echo esc_attr( (string) ( $field['placeholder'] ?? '' ) ); ?>" class="<?php echo esc_attr( $class( 'citcom-form-textarea', 'form-control rounded-3 p-4' ) ); ?>"></textarea>
						<?php elseif ( 'select' === $type ) : ?>
							<select<?php echo $common; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="<?php echo esc_attr( $class( 'citcom-form-select', 'form-select rounded-pill px-4' ) ); ?>">
								<?php $default = (string) ( $field['default'] ?? '' ); ?>
								<?php if ( ! empty( $field['placeholder'] ) ) : ?>
									<option value="" disabled<?php selected( '', $default ); ?>><?php echo esc_html( $field['placeholder'] ); ?></option>
								<?php endif; ?>
								<?php foreach ( citcom_form_options( $field ) as $value => $text ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $value, $default ); ?>><?php echo esc_html( $text ); ?></option>
								<?php endforeach; ?>
							</select>
						<?php elseif ( 'range' === $type ) : ?>
							<input type="range"<?php echo $common; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="citcom-form-range form-range" min="<?php echo esc_attr( (string) ( $field['min'] ?? 0 ) ); ?>" max="<?php echo esc_attr( (string) ( $field['max'] ?? 100 ) ); ?>" step="<?php echo esc_attr( (string) ( $field['step'] ?? 1 ) ); ?>" value="<?php echo esc_attr( (string) ( $field['default'] ?? $field['min'] ?? 0 ) ); ?>">
							<?php if ( ! empty( $field['min_label'] ) || ! empty( $field['max_label'] ) ) : ?>
								<div class="d-flex justify-content-between" style="margin-top:-10px">
									<span class="citcom-form-range-min small text-muted"><?php echo esc_html( (string) ( $field['min_label'] ?? '' ) ); ?></span>
									<span class="citcom-form-range-max small text-muted"><?php echo esc_html( (string) ( $field['max_label'] ?? '' ) ); ?></span>
								</div>
							<?php endif; ?>
						<?php else : ?>
							<input type="<?php echo esc_attr( in_array( $type, array( 'email', 'tel', 'url' ), true ) ? $type : 'text' ); ?>"<?php echo $common; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> value="" placeholder="<?php echo esc_attr( (string) ( $field['placeholder'] ?? '' ) ); ?>" class="<?php echo esc_attr( $class( 'citcom-form-input', 'form-control rounded-pill px-4' ) ); ?>">
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
		<div class="<?php echo esc_attr( $class( 'citcom-form-row citcom-form-row-last', 'mb-0' ) ); ?>">
			<div class="citcom-form-col">
				<button type="submit" class="<?php echo esc_attr( $class( 'citcom-form-submit', 'btn btn-primary px-4 rounded-pill' ) ); ?>"><?php echo esc_html( (string) ( $args['submit'] ?? $form['submit'] ?? __( 'Send', 'citcom' ) ) ); ?></button>
			</div>
		</div>
		<label class="citcom-form-hp" aria-hidden="true">Please do not fill in this field. <input type="text" name="citcom_hp" value="" autocomplete="off" tabindex="-1"></label>
	</form>
	<?php
	return (string) ob_get_clean();
}

/**
 * Sanitise and validate a submission against a form definition.
 *
 * @param array $form Form definition.
 * @param array $raw  Request parameters (unslashed, as REST delivers them).
 * @return array{0:array<string,mixed>,1:array<string,string>} Values and errors by field name.
 */
function citcom_form_validate( array $form, array $raw ): array {
	$fields  = citcom_form_fields( $form );
	$values  = array();
	$errors  = array();
	$choices = array( 'checkbox', 'checkboxes', 'radio', 'select' );
	$select  = __( 'This field is required. Please select a value.', 'citcom' );

	// Choice fields first: other fields' show_if conditions read them.
	foreach ( $fields as $name => $field ) {
		$type = (string) ( $field['type'] ?? 'text' );
		if ( 'checkbox' === $type ) {
			$values[ $name ] = ! empty( $raw[ $name ] );
		} elseif ( 'checkboxes' === $type ) {
			$values[ $name ]            = array_values( array_intersect( array_map( 'strval', array_filter( (array) ( $raw[ $name ] ?? array() ), 'is_scalar' ) ), array_keys( citcom_form_options( $field ) ) ) );
			$values[ $name . '_other' ] = in_array( 'other', $values[ $name ], true ) && is_scalar( $raw[ $name . '_other' ] ?? null ) ? mb_substr( sanitize_text_field( (string) $raw[ $name . '_other' ] ), 0, 200 ) : '';
		} elseif ( 'radio' === $type || 'select' === $type ) {
			$value           = is_scalar( $raw[ $name ] ?? null ) ? (string) $raw[ $name ] : '';
			$values[ $name ] = array_key_exists( $value, citcom_form_options( $field ) ) ? $value : '';
		}
	}

	foreach ( $fields as $name => $field ) {
		$type     = (string) ( $field['type'] ?? 'text' );
		$label    = (string) ( $field['label'] ?? $name );
		$shown    = citcom_form_field_shown( $field, $values );
		$required = ! empty( $field['required'] ) && $shown;

		if ( in_array( $type, $choices, true ) ) {
			if ( ! $shown ) {
				$values[ $name ] = 'checkboxes' === $type ? array() : ( 'checkbox' === $type ? false : '' );
				continue;
			}
			if ( $required && empty( $values[ $name ] ) ) {
				$errors[ $name ] = (string) ( $field['required_message'] ?? ( 'checkbox' === $type ? __( 'This field is required. Please check it.', 'citcom' ) : $select ) );
			} elseif ( 'checkboxes' === $type && in_array( 'other', $values[ $name ], true ) && '' === $values[ $name . '_other' ] ) {
				$errors[ $name ] = __( 'Please, enter a custom value', 'citcom' );
			}
			continue;
		}

		$value = is_scalar( $raw[ $name ] ?? null ) ? trim( (string) $raw[ $name ] ) : '';
		$error = '';
		switch ( $type ) {
			case 'email':
				$typed = $value;
				$value = sanitize_email( $typed );
				if ( '' !== $typed && ! is_email( $value ) ) {
					// Something was typed but it is not an address: say so, not "required".
					$value = '';
					$error = __( 'Valid email required', 'citcom' );
				}
				break;
			case 'textarea':
				$value = mb_substr( sanitize_textarea_field( $value ), 0, 5000 );
				break;
			case 'url':
				if ( '' !== $value ) {
					$typed = preg_match( '#^https?://#i', $value ) ? $value : 'https://' . $value;
					$value = esc_url_raw( $typed );
					if ( '' === $value || ! filter_var( $value, FILTER_VALIDATE_URL ) || false === strpos( (string) wp_parse_url( $value, PHP_URL_HOST ), '.' ) ) {
						$value = '';
						$error = __( 'Please enter a valid website address (for example https://example.com/).', 'citcom' );
					}
				}
				break;
			case 'range':
				$min   = (float) ( $field['min'] ?? 0 );
				$max   = (float) ( $field['max'] ?? 100 );
				$value = '' === $value || ! is_numeric( $value ) ? '' : (string) ( 0 + max( $min, min( $max, (float) $value ) ) );
				break;
			default:
				$value = mb_substr( sanitize_text_field( $value ), 0, 200 );
		}
		$values[ $name ] = $shown ? $value : '';

		if ( ! $shown ) {
			continue;
		}
		if ( '' !== $error ) {
			$errors[ $name ] = $error;
		} elseif ( $required && '' === $value ) {
			/* translators: %s: field label */
			$errors[ $name ] = (string) ( $field['required_message'] ?? sprintf( __( '%s is required', 'citcom' ), $label ) );
		}
	}

	return array( $values, $errors );
}

/**
 * Label => printable value, in form order, for the email and the stored copy.
 *
 * @return array<string,string>
 */
function citcom_form_summary( array $form, array $values ): array {
	$summary = array();
	foreach ( citcom_form_fields( $form ) as $name => $field ) {
		if ( ! citcom_form_field_shown( $field, $values ) ) {
			continue;
		}
		$type    = (string) ( $field['type'] ?? 'text' );
		$value   = $values[ $name ] ?? '';
		$options = citcom_form_options( $field );

		if ( 'checkbox' === $type ) {
			$value = $value ? __( 'Yes', 'citcom' ) : __( 'No', 'citcom' );
		} elseif ( 'checkboxes' === $type ) {
			$picked = array();
			foreach ( (array) $value as $option ) {
				$picked[] = 'other' === $option && ! empty( $field['other'] ) ? __( 'Other', 'citcom' ) . ': ' . (string) ( $values[ $name . '_other' ] ?? '' ) : ( $options[ $option ] ?? $option );
			}
			$value = implode( "\n", $picked );
		} elseif ( 'radio' === $type || 'select' === $type ) {
			$value = $options[ (string) $value ] ?? '';
		}
		$summary[ (string) ( $field['label'] ?? $name ) ] = (string) $value;
	}
	return $summary;
}
