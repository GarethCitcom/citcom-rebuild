<?php
/**
 * ACF field type "citcom_focuspoint": an image plus the point to keep in view.
 *
 * Replaces the acf-focuspoint mu-plugin with the same stored value, an array
 * of `id` (attachment), `top` and `left` (percentages), which the templates
 * turn into `object-position: {left}% {top}%`. Editors pick an image from the
 * media library and click on the preview to place the focal point.
 *
 * @package citcom
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'acf_field' ) ) {
	return;
}

/**
 * Focal point image field.
 */
class Citcom_Field_Focuspoint extends acf_field {

	/**
	 * Field type setup.
	 */
	public function initialize() {
		$this->name          = 'citcom_focuspoint';
		$this->label         = __( 'Image with focal point', 'citcom' );
		$this->category      = 'content';
		$this->description   = __( 'An image and the point, as percentages, that stays in view when it is cropped.', 'citcom' );
		$this->show_in_rest  = false;
		$this->defaults      = array(
			'preview_size' => 'large',
			'library'      => 'all',
			'min_width'    => 0,
			'min_height'   => 0,
			'min_size'     => 0,
			'max_width'    => 0,
			'max_height'   => 0,
			'max_size'     => 0,
			'mime_types'   => '',
		);
	}

	/**
	 * General settings.
	 *
	 * @param array $field Field.
	 */
	public function render_field_settings( $field ) {
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Library', 'acf' ),
				'instructions' => __( 'Limit the media library choice', 'acf' ),
				'type'         => 'radio',
				'name'         => 'library',
				'layout'       => 'horizontal',
				'choices'      => array(
					'all'        => __( 'All', 'acf' ),
					'uploadedTo' => __( 'Uploaded to post', 'acf' ),
				),
			)
		);
	}

	/**
	 * Validation settings.
	 *
	 * @param array $field Field.
	 */
	public function render_field_validation_settings( $field ) {
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Minimum', 'acf' ),
				'instructions' => __( 'Restrict which images can be uploaded', 'acf' ),
				'type'         => 'text',
				'name'         => 'min_width',
				'prepend'      => __( 'Width', 'acf' ),
				'append'       => 'px',
			)
		);
		acf_render_field_setting(
			$field,
			array(
				'label'   => '',
				'type'    => 'text',
				'name'    => 'min_height',
				'prepend' => __( 'Height', 'acf' ),
				'append'  => 'px',
				'_append' => 'min_width',
			)
		);
		acf_render_field_setting(
			$field,
			array(
				'label'   => '',
				'type'    => 'text',
				'name'    => 'min_size',
				'prepend' => __( 'File size', 'acf' ),
				'append'  => 'MB',
				'_append' => 'min_width',
			)
		);
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Maximum', 'acf' ),
				'instructions' => __( 'Restrict which images can be uploaded', 'acf' ),
				'type'         => 'text',
				'name'         => 'max_width',
				'prepend'      => __( 'Width', 'acf' ),
				'append'       => 'px',
			)
		);
		acf_render_field_setting(
			$field,
			array(
				'label'   => '',
				'type'    => 'text',
				'name'    => 'max_height',
				'prepend' => __( 'Height', 'acf' ),
				'append'  => 'px',
				'_append' => 'max_width',
			)
		);
		acf_render_field_setting(
			$field,
			array(
				'label'   => '',
				'type'    => 'text',
				'name'    => 'max_size',
				'prepend' => __( 'File size', 'acf' ),
				'append'  => 'MB',
				'_append' => 'max_width',
			)
		);
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Allowed file types', 'acf' ),
				'instructions' => __( 'Comma separated list. Leave blank for all types', 'acf' ),
				'type'         => 'text',
				'name'         => 'mime_types',
			)
		);
	}

	/**
	 * Presentation settings.
	 *
	 * @param array $field Field.
	 */
	public function render_field_presentation_settings( $field ) {
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Preview Size', 'acf' ),
				'instructions' => __( 'Shown when entering data', 'acf' ),
				'type'         => 'select',
				'name'         => 'preview_size',
				'choices'      => acf_get_image_sizes(),
			)
		);
	}

	/**
	 * The input.
	 *
	 * @param array $field Field with value.
	 */
	public function render_field( $field ) {
		$value = self::normalise( $field['value'] ?? null );
		$id    = $value['id'];
		$src   = '';
		$alt   = '';
		if ( $id && wp_attachment_is_image( $id ) ) {
			$image = wp_get_attachment_image_src( $id, $field['preview_size'] ?: 'large' );
			$src   = $image ? $image[0] : '';
			$alt   = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
		} else {
			$id = '';
		}
		$top  = '' !== $value['top'] ? $value['top'] : '50';
		$left = '' !== $value['left'] ? $value['left'] : '50';
		?>
		<div class="citcom-focuspoint <?php echo $id ? 'has-value' : ''; ?>" data-library="<?php echo esc_attr( $field['library'] ); ?>" data-mime_types="<?php echo esc_attr( $field['mime_types'] ); ?>" data-preview_size="<?php echo esc_attr( $field['preview_size'] ); ?>">
			<input type="hidden" data-name="id" name="<?php echo esc_attr( $field['name'] ); ?>[id]" value="<?php echo esc_attr( $id ); ?>" />
			<input type="hidden" data-name="top" name="<?php echo esc_attr( $field['name'] ); ?>[top]" value="<?php echo esc_attr( $id ? $value['top'] : '' ); ?>" />
			<input type="hidden" data-name="left" name="<?php echo esc_attr( $field['name'] ); ?>[left]" value="<?php echo esc_attr( $id ? $value['left'] : '' ); ?>" />

			<div class="citcom-focuspoint-picker" title="<?php esc_attr_e( 'Click to set the point that stays in view', 'citcom' ); ?>">
				<img data-name="image" src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( $alt ); ?>" />
				<span class="citcom-focuspoint-marker" style="left: <?php echo esc_attr( $left ); ?>%; top: <?php echo esc_attr( $top ); ?>%;"></span>
				<a href="#" class="acf-icon -cancel dark citcom-focuspoint-remove" data-name="remove" title="<?php esc_attr_e( 'Remove', 'acf' ); ?>"></a>
			</div>
			<p class="citcom-focuspoint-readout">
				<?php esc_html_e( 'Focal point', 'citcom' ); ?>
				<span data-name="readout"><?php echo esc_html( $left . '% ' . $top . '%' ); ?></span>
				<a href="#" class="citcom-focuspoint-change" data-name="add"><?php esc_html_e( 'Change image', 'citcom' ); ?></a>
			</p>

			<p class="citcom-focuspoint-empty">
				<?php esc_html_e( 'No image selected', 'acf' ); ?>
				<a href="#" class="acf-button button" data-name="add"><?php esc_html_e( 'Add Image', 'acf' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Admin assets for screens that render this field.
	 */
	public function input_admin_enqueue_scripts() {
		$version = defined( 'CITCOM_THEME_VERSION' ) ? CITCOM_THEME_VERSION : null;
		wp_enqueue_script( 'citcom-focuspoint', CITCOM_THEME_URI . '/assets/admin/focuspoint.js', array( 'acf-input', 'jquery' ), $version, true );
		wp_enqueue_style( 'citcom-focuspoint', CITCOM_THEME_URI . '/assets/admin/focuspoint.css', array( 'acf-input' ), $version );
		if ( function_exists( 'acf_enqueue_uploader' ) ) {
			acf_enqueue_uploader();
		}
	}

	/**
	 * Any stored shape to a clean array.
	 *
	 * @param mixed $value Raw value.
	 * @return array{id:string,top:string,left:string}
	 */
	public static function normalise( $value ): array {
		$clean = array(
			'id'   => '',
			'top'  => '',
			'left' => '',
		);
		if ( is_numeric( $value ) ) {
			$clean['id'] = (string) (int) $value;
		} elseif ( is_array( $value ) ) {
			$id = $value['id'] ?? $value['ID'] ?? '';
			if ( is_numeric( $id ) && (int) $id > 0 ) {
				$clean['id'] = (string) (int) $id;
			}
			foreach ( array( 'top', 'left' ) as $axis ) {
				if ( isset( $value[ $axis ] ) && '' !== $value[ $axis ] && is_numeric( $value[ $axis ] ) ) {
					$clean[ $axis ] = (string) round( max( 0, min( 100, (float) $value[ $axis ] ) ), 2 );
				}
			}
		}
		if ( '' !== $clean['id'] ) {
			if ( '' === $clean['top'] ) {
				$clean['top'] = '50';
			}
			if ( '' === $clean['left'] ) {
				$clean['left'] = '50';
			}
		}
		return $clean;
	}

	/**
	 * Stored value: always the array, with 50/50 defaults when an image is set.
	 *
	 * @param mixed $value   Submitted value.
	 * @param mixed $post_id Post ID.
	 * @param array $field   Field.
	 * @return array
	 */
	public function update_value( $value, $post_id, $field ) {
		return self::normalise( $value );
	}

	/**
	 * Loaded value in the same shape (tolerates the old plugin's data and strings).
	 *
	 * @param mixed $value   Stored value.
	 * @param mixed $post_id Post ID.
	 * @param array $field   Field.
	 * @return array
	 */
	public function load_value( $value, $post_id, $field ) {
		return self::normalise( $value );
	}

	/**
	 * Template value: id, top, left as the old plugin returned them, plus the
	 * full-size url, alt, width and height for convenience.
	 *
	 * @param mixed $value   Stored value.
	 * @param mixed $post_id Post ID.
	 * @param array $field   Field.
	 * @return array|null Null when no image is set.
	 */
	public function format_value( $value, $post_id, $field ) {
		$clean = self::normalise( $value );
		if ( '' === $clean['id'] ) {
			return null;
		}
		$id    = (int) $clean['id'];
		$image = wp_get_attachment_image_src( $id, 'full' );
		return array(
			'id'     => $clean['id'],
			'top'    => $clean['top'],
			'left'   => $clean['left'],
			'url'    => $image ? $image[0] : '',
			'width'  => $image ? $image[1] : 0,
			'height' => $image ? $image[2] : 0,
			'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
		);
	}

	/**
	 * Required check plus the size limits, like ACF's image field.
	 *
	 * @param bool|string $valid Current validity.
	 * @param mixed       $value Submitted value.
	 * @param array       $field Field.
	 * @param string      $input Input name.
	 * @return bool|string
	 */
	public function validate_value( $valid, $value, $field, $input ) {
		$clean = self::normalise( $value );

		if ( '' === $clean['id'] ) {
			// Block validation checks every key in the block data, hidden or not,
			// and ACF's own rule is not to enforce required on fields that carry
			// conditional logic (see acf_validate_block_from_local_meta). Same here.
			if ( ! empty( $field['required'] ) && empty( $field['conditional_logic'] ) ) {
				return sprintf( __( '%s value is required', 'acf' ), $field['label'] );
			}
			return $valid;
		}

		$id = (int) $clean['id'];
		if ( ! wp_attachment_is_image( $id ) ) {
			return __( 'File must be a valid image.', 'acf' );
		}

		$meta   = wp_get_attachment_metadata( $id );
		$width  = (int) ( $meta['width'] ?? 0 );
		$height = (int) ( $meta['height'] ?? 0 );
		$file   = get_attached_file( $id );
		$size   = $file && file_exists( $file ) ? filesize( $file ) / 1024 / 1024 : 0;

		$errors = array();
		if ( ! empty( $field['min_width'] ) && $width && $width < (int) $field['min_width'] ) {
			$errors[] = sprintf( __( 'Image width must not be less than %dpx.', 'acf' ), (int) $field['min_width'] );
		}
		if ( ! empty( $field['min_height'] ) && $height && $height < (int) $field['min_height'] ) {
			$errors[] = sprintf( __( 'Image height must not be less than %dpx.', 'acf' ), (int) $field['min_height'] );
		}
		if ( ! empty( $field['max_width'] ) && $width > (int) $field['max_width'] ) {
			$errors[] = sprintf( __( 'Image width must not exceed %dpx.', 'acf' ), (int) $field['max_width'] );
		}
		if ( ! empty( $field['max_height'] ) && $height > (int) $field['max_height'] ) {
			$errors[] = sprintf( __( 'Image height must not exceed %dpx.', 'acf' ), (int) $field['max_height'] );
		}
		if ( ! empty( $field['min_size'] ) && $size && $size < (float) $field['min_size'] ) {
			$errors[] = sprintf( __( 'File size must be at least %s.', 'acf' ), $field['min_size'] . 'MB' );
		}
		if ( ! empty( $field['max_size'] ) && $size > (float) $field['max_size'] ) {
			$errors[] = sprintf( __( 'File size must not exceed %s.', 'acf' ), $field['max_size'] . 'MB' );
		}
		if ( ! empty( $field['mime_types'] ) ) {
			$allowed = array_filter( array_map( 'trim', explode( ',', strtolower( $field['mime_types'] ) ) ) );
			$ext     = strtolower( pathinfo( (string) $file, PATHINFO_EXTENSION ) );
			if ( $allowed && ! in_array( $ext, $allowed, true ) ) {
				$errors[] = sprintf( __( 'File type must be %s.', 'acf' ), implode( ', ', $allowed ) );
			}
		}

		return $errors ? implode( ' ', $errors ) : $valid;
	}
}

acf_register_field_type( 'Citcom_Field_Focuspoint' );
