<?php
/**
 * Strong Testimonials - Custom fields admin functions
 */

function wpmtst_form_admin() {
	do_action( 'wpmtst_form_admin' );
}

function wpmtst_form_admin2() {
	wpmtst_settings_custom_fields( 1 );
}

add_action( 'wpmtst_form_admin', 'wpmtst_form_admin2' );

/**
 * Save changes to custom fields.
 *
 * @since 2.28.5 As separate function on custom action.
 */
function wpmtst_update_custom_fields() {
	$goback = wp_get_referer();
	$goback = apply_filters( 'wpmtst_form_goback', $goback );

	if ( ! isset( $_POST['wpmtst_form_submitted'] ) ) {
		wp_redirect( $goback );
		exit;
	}

	if ( ! wp_verify_nonce( $_POST['wpmtst_form_submitted'], 'wpmtst_custom_fields_form' ) ) {
		wp_redirect( $goback );
		exit;
	}

	$form_id       = absint( $_POST['form_id'] );
	$forms         = get_option( 'wpmtst_custom_forms' );
	$field_options = apply_filters( 'wpmtst_fields', get_option( 'wpmtst_fields' ) );

	$notice = array(
		'status' => 'success',
		'source' => array(
			'slug' => 'strong-testimonials',
			'name' => 'Strong Testimonials',
		),
		'timed'  => 5000,
	);

	if ( isset( $_POST['reset'] ) ) {

		// Undo changes
		$notice['title']   = esc_html__( 'Changes cancelled', 'strong-testimonials' );
		$notice['message'] = esc_html__( 'The form changes have not been saved.', 'strong-testimonials' );
	} elseif ( isset( $_POST['restore-defaults'] ) ) {

		// Restore defaults
		$default_forms               = Strong_Testimonials_Defaults::get_base_forms();
		$fields                      = $default_forms['default']['fields'];
		$forms[ $form_id ]['fields'] = $fields;
		update_option( 'wpmtst_custom_forms', $forms );
		do_action( 'wpmtst_fields_updated', $fields );

		$notice['title']   = esc_html__( 'Defaults restored', 'strong-testimonials' );
		$notice['message'] = esc_html__( 'The form fields have been restored to the default state.', 'strong-testimonials' );
	} else {

		// Save changes
		$fields  = array();
		$new_key = 0;

		/**
		 * Strip the dang slashes from the dang magic quotes.
		 *
		 * @since 2.0.0
		 */
		$post_fields = isset( $_POST['fields'] ) ? stripslashes_deep( $_POST['fields'] ) : array();

		foreach ( $post_fields as $key => $field ) {

			/*
			 * Before merging onto base field, catch fields that are "off"
			 * which the form does not submit. Otherwise, the default "on"
			 * would override the requested (but not submitted) "off".
			 */
			$field['show_label'] = isset( $field['show_label'] ) ? 1 : 0;
			$field['required']   = isset( $field['required'] ) ? 1 : 0;

			$field = array_merge( $field_options['field_base'], $field );

			$field['name']  = sanitize_text_field( $field['name'] );
			$field['label'] = sanitize_text_field( $field['label'] );
			$field['text']  = sanitize_text_field( $field['text'] );

			// TODO Replace this special handling
			if ( 'checkbox' === $field['input_type'] ) {
				$field['default_form_value'] = wpmtst_sanitize_checkbox( $field, 'default_form_value' );
			} else {
				$field['default_form_value'] = sanitize_text_field( $field['default_form_value'] );
			}
			$field['action_input']  = isset( $field['action_input'] ) ? sanitize_text_field( $field['action_input'] ) : '';
			$field['action_output'] = isset( $field['action_output'] ) ? sanitize_text_field( $field['action_output'] ) : '';

			$field['default_display_value'] = sanitize_text_field( $field['default_display_value'] );

			$field['placeholder'] = sanitize_text_field( $field['placeholder'] );

			$field['before'] = sanitize_text_field( $field['before'] );
			$field['after']  = sanitize_text_field( $field['after'] );

			$field['shortcode_on_form']      = sanitize_text_field( $field['shortcode_on_form'] );
			$field['shortcode_on_display']   = sanitize_text_field( $field['shortcode_on_display'] );
			$field['show_shortcode_options'] = $field['show_shortcode_options'] ? 1 : 0;

			// Hidden options (no need to check if isset)
			$field['admin_table']             = $field['admin_table'] ? 1 : 0;
			$field['show_admin_table_option'] = $field['show_admin_table_option'] ? 1 : 0;
			$field['show_text_option']        = $field['show_text_option'] ? 1 : 0;
			$field['show_placeholder_option'] = $field['show_placeholder_option'] ? 1 : 0;
			$field['show_default_options']    = $field['show_default_options'] ? 1 : 0;
			$field['show_length_option']      = $field['show_length_option'] ? 1 : 0;

			$field = apply_filters( 'wpmtst_sanitize_form_field_options', $field );

			// add to fields array in display order
			$fields[ $new_key++ ] = $field;
		}

		$forms[ $form_id ]['fields'] = $fields;

		if ( isset( $_POST['field_group_label'] ) ) {
			// TODO Catch if empty.
			$new_label                  = sanitize_text_field( wp_unslash( $_POST['field_group_label'] ) );
			$forms[ $form_id ]['label'] = $new_label;
		}

		update_option( 'wpmtst_custom_forms', $forms );
		do_action( 'wpmtst_fields_updated', $fields );

		$notice['title']   = esc_html__( 'Fields saved', 'strong-testimonials' );
		$notice['message'] = esc_html__( 'The changes to the form fields have been successfully saved.', 'strong-testimonials' );
	}

	WPChill_Notifications::add_notification( 'wpmtst-update-custom-fields-notice', $notice );

	wp_safe_redirect( $goback );
	exit;
}

add_action( 'admin_post_wpmtst_update_custom_fields', 'wpmtst_update_custom_fields' );

/**
 * Custom Fields form
 *
 * @param int $form_id
 */
function wpmtst_settings_custom_fields( $form_id = 1 ) {
	if ( ! current_user_can( 'strong_testimonials_fields' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'strong-testimonials' ) );
	}

	if ( ! $form_id ) {
		echo '<div class="wrap wpmtst"><p>' . esc_html__( 'No fields selected.', 'strong-testimonials' ) . '</p></div>';
		return;
	}

	$forms  = get_option( 'wpmtst_custom_forms' );
	$fields = apply_filters( 'wpmtst_form_fields', $forms[ $form_id ]['fields'] );
	?>

	<div class="wrap wpmtst">
	<?php do_action( 'wpmtst_fields_editor_before_fields_intro' ); ?>

	<div id="left-col">
		<div>
			<h3><?php esc_html_e( 'Editor', 'strong-testimonials' ); ?></h3>
			<p>
				<?php esc_html_e( 'Click a field to open its options panel.', 'strong-testimonials' ); ?>
				<a class="open-help-tab" href="#tab-panel-wpmtst-help"><?php esc_html_e( 'Help', 'strong-testimonials' ); ?></a>
			</p>
			<?php do_action( 'wpmtst_before_fields_settings', 'form-fields' ); ?>
		</div>

		<form id="wpmtst-custom-fields-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
			<?php wp_nonce_field( 'wpmtst_custom_fields_form', 'wpmtst_form_submitted' ); ?>
			<input type="hidden" name="action" value="wpmtst_update_custom_fields">
			<input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>">

			<?php do_action( 'wpmtst_fields_editor_before_fields_editor', $forms[ $form_id ] ); ?>

			<ul id="custom-field-list">
				<?php
				foreach ( $fields as $key => $field ) {
					echo '<li id="field-' . esc_attr( $key ) . '">' . wpmtst_show_field( $key, $field, false ) . '</li>' . "\n";
				}
				?>
			</ul>

			<div id="add-field-bar">
				<input id="add-field" type="button" class="button" name="add-field" value="<?php esc_attr_e( 'Add New Field', 'strong-testimonials' ); ?>">
			</div>

			<?php do_action( 'wpmtst_admin_after_form_fields' ); ?>

			<div id="field-group-actions">
				<div><?php submit_button( '', 'primary', 'submit-form', false ); ?></div>
				<div><?php submit_button( esc_html__( 'Cancel Changes', 'strong-testimonials' ), 'secondary', 'reset', false ); ?></div>
				<div><?php submit_button( esc_html__( 'Restore Defaults', 'strong-testimonials' ), 'secondary', 'restore-defaults', false ); ?></div>
			</div>
		</form>
	</div><!-- #left-col -->

	<div id="right-col">
		<div class="intro">
			<h3><?php esc_html_e( 'Basic Preview', 'strong-testimonials' ); ?></h3>
			<p><?php esc_html_e( 'Only to demonstrate the fields. May look different in your theme.', 'strong-testimonials' ); ?></p>
		</div>
		<?php do_action( 'wpmtst_fields_before_fields_editor_preview' ); ?>
		<div id="fields-editor-preview">
			<div><!-- placeholder --></div>
		</div>
	</div><!-- #right-col -->

	</div><!-- .wrap -->
	<?php
}

/**
 * Add a field to the form
 *
 * @param $key
 * @param $field
 * @param $adding
 *
 * @return string
 */
function wpmtst_show_field( $key, $field, $adding ) {
	$fields      = apply_filters( 'wpmtst_fields', get_option( 'wpmtst_fields' ) );
	$field_types = $fields['field_types'];

	ob_start();

	include 'partials/fields/field-header.php';
	?>
	<div class="custom-field" style="display: none;">

		<table class="field-table">
			<?php
			include 'partials/fields/field-type.php';
			include 'partials/fields/field-label.php';
			include 'partials/fields/field-name.php';

			if ( ! $adding ) {
				echo wpmtst_show_field_secondary( $key, $field );
				echo wpmtst_show_field_admin_table( $key, $field );
			}

			?>
		</table>

		<?php
		if ( ! $adding ) {
			echo wp_kses_post( wpmtst_show_field_hidden( $key, $field ) );
		}
		include 'partials/fields/field-controls.php';
		?>
	</div><!-- .custom-field -->

	<?php
	$html = ob_get_contents();
	ob_end_clean();

	return $html;
}


/**
 * Create the secondary inputs for a new custom field.
 * Called after field type is chosen (Post or Custom).
 *
 * @param $key
 * @param $field
 *
 * @return string
 */
function wpmtst_show_field_secondary( $key, $field ) {
	$html = '';

	/*
	 * Required
	 */
	if ( isset( $field['show_required_option'] ) && $field['show_required_option'] ) {
		// Disable option if this is a core field like post_content.
		if ( isset( $field['core'] ) && $field['core'] ) {
			$disabled = ' disabled="disabled"';
		} else {
			$disabled = '';
		}

		$html .= '<tr class="field-secondary">' . "\n";
		$html .= '<th>' . esc_html__( 'Required', 'strong-testimonials' ) . '</th>' . "\n";
		$html .= '<td>' . "\n";
		if ( $disabled ) {
			$html .= '<input type="hidden" name="fields[' . esc_attr( $key ) . '][required]" value="' . esc_attr( $field['required'] ) . '">';
			$html .= '<input type="checkbox" ' . checked( $field['required'], true, false ) . $disabled . '>';
		} else {
			$html .= '<input type="checkbox" name="fields[' . esc_attr( $key ) . '][required]" ' . checked( $field['required'], true, false ) . '>';
		}
		$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";
	}

	/*
	 * Placeholder
	 */
	if ( $field['show_placeholder_option'] ) {
		if ( isset( $field['placeholder'] ) ) {
			$html .= '<tr class="field-secondary">' . "\n";
			$html .= '<th>' . esc_html__( 'Placeholder', 'strong-testimonials' ) . '</th>' . "\n";
			$html .= '<td><input type="text" name="fields[' . esc_attr( $key ) . '][placeholder]" value="' . esc_attr( $field['placeholder'] ) . '"></td>' . "\n";
			$html .= '</tr>' . "\n";
		}
	}

		/*
		 * Length
		 */
	if ( $field['show_length_option'] ) {
		if ( isset( $field['max_length'] ) ) {
			$html .= '<tr class="field-secondary">' . "\n";
			$html .= '<th>' . esc_html__( 'Maximum Length', 'strong-testimonials' ) . '</th>' . "\n";
			$html .= '<td><input type="number" name="fields[' . esc_attr( $key ) . '][max_length]" value="' . esc_attr( $field['max_length'] ) . '"><span> ' . esc_html__( 'Limit the user imput to a certain number of characters', 'strong-testimonials' ) . '</span></td>' . "\n";
			$html .= '</tr>' . "\n";
		}
	}

	/**
	 * Text (checkbox, radio)
	 *
	 * @since 2.23.0
	 */
	if ( $field['show_text_option'] ) {
		if ( isset( $field['text'] ) ) {
			$html .= '<tr class="field-secondary">' . "\n";
			$html .= '<th>' . esc_html__( 'Checked value', 'strong-testimonials' ) . '</th>' . "\n";
			$html .= '<td><input type="text" name="fields[' . esc_attr( $key ) . '][text]" value="' . esc_attr( $field['text'] ) . '" placeholder="' . esc_html__( 'next to the checkbox', 'strong-testimonials' ) . '"></td>' . "\n";
			$html .= '</tr>' . "\n";
		}
	}

	/*
	 * Before
	 */
	$html .= '<tr class="field-secondary">' . "\n";
	$html .= '<th>' . esc_html__( 'Before', 'strong-testimonials' ) . '</th>' . "\n";
	$html .= '<td><input type="text" name="fields[' . esc_attr( $key ) . '][before]" value="' . esc_attr( $field['before'] ) . '"></td>' . "\n";
	$html .= '</tr>' . "\n";

	/*
	 * After
	 */
	$html .= '<tr class="field-secondary">' . "\n";
	$html .= '<th>' . esc_html__( 'After', 'strong-testimonials' ) . '</th>' . "\n";
	$html .= '<td><input type="text" name="fields[' . esc_attr( $key ) . '][after]" value="' . esc_attr( $field['after'] ) . '"></td>' . "\n";
	$html .= '</tr>' . "\n";

	/*
	 * Default Form Value
	 */
	if ( $field['show_default_options'] ) {
		if ( isset( $field['default_form_value'] ) ) {
			$html .= '<tr class="field-secondary">' . "\n";
			$html .= '<th>' . esc_html__( 'Default Form Value', 'strong-testimonials' ) . '</th>' . "\n";
			$html .= '<td>' . "\n";

			// TODO Replace this special handling
			if ( 'rating' === $field['input_type'] ) {
				$html .= '<input type="text" name="fields[' . esc_attr( $key ) . '][default_form_value]" value="' . esc_attr( $field['default_form_value'] ) . '" class="as-number">';
				$html .= '<span class="help inline">' . esc_html__( 'stars', 'strong-testimonials' ) . '</span>';
				$html .= '<span class="help">' . esc_html__( 'Populate the field with this value.', 'strong-testimonials' ) . '</span>';

			} elseif ( 'checkbox' === $field['input_type'] ) {
				$html .= '<label>';
				$html .= '<input type="checkbox" name="fields[' . esc_attr( $key ) . '][default_form_value]" ' . checked( $field['default_form_value'], true, false ) . '>';
				$html .= '<span class="help inline">' . esc_html__( 'Checked by default.', 'strong-testimonials' ) . '</span>';
				$html .= '</label>';
			} else {
				$html .= '<input type="text" name="fields[' . esc_attr( $key ) . '][default_form_value]" value="' . esc_attr( $field['default_form_value'] ) . '">';
				$html .= '<span class="help">' . wp_kses_post( __( 'Populate the field with this custom value or use <code>{user_name}</code>, <code>{user_first_name}</code>, <code>{user_last_name}</code>, <code>{user_email}</code> placeholders.', 'strong-testimonials' ) ) . '</span>';
			}

			$html .= '</td>' . "\n";
			$html .= '</tr>' . "\n";
		}
	}

	/*
	 * Default Display Value
	 */
	if ( $field['show_default_options'] ) {
		// TODO Replace this special handling for checkbox type
		if ( 'checkbox' !== $field['input_type'] ) {
			if ( isset( $field['default_display_value'] ) ) {
				$html .= '<tr class="field-secondary">' . "\n";
				$html .= '<th>' . esc_html__( 'Default Display Value', 'strong-testimonials' ) . '</th>' . "\n";
				$html .= '<td>' . "\n";

				// TODO Replace this special handling
				if ( 'rating' === $field['input_type'] ) {
					$html .= '<input type="text" name="fields[' . esc_attr( $key ) . '][default_display_value]" value="' . esc_attr( $field['default_display_value'] ) . '" class="as-number">';
					$html .= '<span class="help inline">' . esc_html__( 'stars', 'strong-testimonials' ) . '</span>';
				} else {
					$html .= '<input type="text" name="fields[' . esc_attr( $key ) . '][default_display_value]" value="' . esc_attr( $field['default_display_value'] ) . '">';
				}

				$html .= '<span class="help">' . esc_html__( 'Display this on the testimonial if no value is submitted.', 'strong-testimonials' ) . '</span>';
				$html .= '</td>' . "\n";
				$html .= '</tr>' . "\n";
			}
		}
	}

	/*
	 * Shortcode Options
	 */
	if ( $field['show_shortcode_options'] ) {
		if ( isset( $field['shortcode_on_form'] ) ) {
			$html .= '<tr class="field-secondary">' . "\n";
			$html .= '<th>' . esc_html__( 'Shortcode on form', 'strong-testimonials' ) . '</th>' . "\n";
			$html .= '<td>' . "\n";
			$html .= '<input type="text" name="fields[' . esc_attr( $key ) . '][shortcode_on_form]" value="' . esc_attr( $field['shortcode_on_form'] ) . '">';
			$html .= '</td>' . "\n";
			$html .= '</tr>' . "\n";
		}
		if ( isset( $field['shortcode_on_display'] ) ) {
			$html .= '<tr class="field-secondary">' . "\n";
			$html .= '<th>' . esc_html__( 'Shortcode on display', 'strong-testimonials' ) . '</th>' . "\n";
			$html .= '<td>' . "\n";
			$html .= '<input type="text" name="fields[' . esc_attr( $key ) . '][shortcode_on_display]" value="' . esc_attr( $field['shortcode_on_display'] ) . '">';
			$html .= '</td>' . "\n";
			$html .= '</tr>' . "\n";
		}
	}

	$html = apply_filters( 'wpmtst_fields_secondary', $html, $key, $field );

	return $html;
}


/**
 * Add type-specific [Admin Table] setting to form.
 */
function wpmtst_show_field_admin_table( $key, $field ) {
	// -------------------
	// Show in Admin Table
	// -------------------
	if ( ! $field['show_admin_table_option'] ) {
		$html = '<input type="hidden" name="fields[' . esc_attr( $key ) . '][show_admin_table_option]" value="' . esc_attr( $field['show_admin_table_option'] ) . '">';
		return $html;
	}

	$html  = '<tr class="field-admin-table">' . "\n";
	$html .= '<th>' . esc_html__( 'Admin List', 'strong-testimonials' ) . '</th>' . "\n";
	$html .= '<td>' . "\n";
	if ( $field['admin_table_option'] ) {
		$html .= '<label><input type="checkbox" class="field-admin-table" name="fields[' . esc_attr( $key ) . '][admin_table]" ' . checked( $field['admin_table'], 1, false ) . '>';
	} else {
		$html .= '<input type="checkbox" ' . checked( $field['admin_table'], 1, false ) . ' disabled="disabled"> <em>' . esc_html__( 'required', 'strong-testimonials' ) . '</em>';
		$html .= '<input type="hidden" name="fields[' . esc_attr( $key ) . '][admin_table]" value="' . esc_attr( $field['admin_table'] ) . '">';
	}
	$html .= '<span class="help inline">' . esc_html__( 'Show this field in the admin list table.', 'strong-testimonials' ) . '</span>';
	$html .= '</label>';
	$html .= '</td>' . "\n";
	$html .= '</tr>' . "\n";

	return $html;
}


/**
 * Add type-specific select options field
 */
function wpmtst_show_field_select_options( $key, $field ) {

	if ( 'select' !== $field['input_type'] ) {
		return;
	}

	ob_start();
	include 'partials/fields/field-select-options.php';
	return ob_get_clean();
}


/**
 * Add hidden fields to form.
 *
 * @param $key
 * @param $field
 *
 * @return string
 */
function wpmtst_show_field_hidden( $key, $field ) {
	$pattern = '<input type="hidden" name="fields[%s][%s]" value="%s">';

	$html  = sprintf( $pattern, $key, 'record_type', $field['record_type'] ) . "\n";
	$html .= sprintf( $pattern, $key, 'input_type', $field['input_type'] ) . "\n";
	if ( isset( $field['action_input'] ) ) {
		$html .= sprintf( $pattern, $key, 'action_input', $field['action_input'] ) . "\n";
	}
	if ( isset( $field['action_output'] ) ) {
		$html .= sprintf( $pattern, $key, 'action_output', $field['action_output'] ) . "\n";
	}
	$html     .= sprintf( $pattern, $key, 'name_mutable', $field['name_mutable'] ) . "\n";
	$html     .= sprintf( $pattern, $key, 'show_text_option', $field['show_text_option'] ) . "\n";
	$html     .= sprintf( $pattern, $key, 'show_placeholder_option', $field['show_placeholder_option'] ) . "\n";
	$html     .= sprintf( $pattern, $key, 'show_default_options', $field['show_default_options'] ) . "\n";
	$html     .= sprintf( $pattern, $key, 'admin_table_option', $field['admin_table_option'] ) . "\n";
	$html     .= sprintf( $pattern, $key, 'show_admin_table_option', $field['show_admin_table_option'] ) . "\n";
	$html     .= sprintf( $pattern, $key, 'show_shortcode_options', $field['show_shortcode_options'] ) . "\n";
		$html .= sprintf( $pattern, $key, 'show_length_option', $field['show_length_option'] ) . "\n";

	if ( isset( $field['map'] ) ) {
		$html .= sprintf( $pattern, $key, 'map', $field['map'] ) . "\n";
	}

	if ( isset( $field['core'] ) ) {
		$html .= sprintf( $pattern, $key, 'core', $field['core'] ) . "\n";
	}

	return $html;
}
