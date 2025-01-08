<?php

namespace common\settings\forms;

use common\settings\EcpSettings;

class EcpFieldRenderer {

	public function render_field_input( $value ) {
		ecp_get_view( 'fields/html-form-field-input.php', $value );
	}

	public function render_field_color( $value ) {
		ecp_get_view( 'fields/html-form-field-color.php', $value );
	}

	public function render_field_single_select_page( $value ) {
		$args = [
			'name'             => $value[ EcpSettings::FIELD_ID ],
			'id'               => $value[ EcpSettings::FIELD_ID ],
			'sort_column'      => 'menu_order',
			'sort_order'       => 'ASC',
			'show_option_none' => ' ',
			'class'            => $value[ EcpSettings::FIELD_CLASS ],
			'echo'             => false,
			'selected'         => absint( $value['option_value'] ),
			'post_status'      => 'publish,private,draft',
		];

		if ( isset ( $value[ EcpSettings::FIELD_ARGS ] ) ) {
			$value['args'] = wp_parse_args( $value[ EcpSettings::FIELD_ARGS ], $args );
		}

		ecp_get_view( 'fields/html-form-field-single_select_page.php', $value );
	}

	public function render_toggle_end( $value ) {
		ecp_get_view( 'fields/html-form-toggle-end.php', $value );
	}

	public function render_toggle_start( $value ) {
		ecp_get_view( 'fields/html-form-toggle-start.php', $value );
	}

	public function render_field_radio( $value ) {
		ecp_get_view( 'fields/html-form-field-radio.php', $value );
	}

	public function render_field_description( $value ) {
		ecp_get_view( 'fields/html-form-field-section-description.php', $value );
	}

	public function render_fieldset_start( $value ) {
		ecp_get_view( 'fields/html-form-fieldset-start.php', $value );
	}

	public function render_field_text( $value ) {
		ecp_get_view( 'fields/html-form-field-text.php', $value );
	}

	public function render_field_select( $value ) {
		ecp_get_view( 'fields/html-form-field-select.php', $value );
	}

	public function render_field_checkbox( $value ) {
		$visibility_class = [];

		if ( ! isset ( $value['hide_if_checked'] ) ) {
			$value['hide_if_checked'] = false;
		}
		if ( ! isset ( $value['show_if_checked'] ) ) {
			$value['show_if_checked'] = false;
		}
		if ( EcpSettings::VALUE_ENABLED === $value['hide_if_checked'] || EcpSettings::VALUE_ENABLED === $value['show_if_checked'] ) {
			$visibility_class[] = 'hidden_option';
		}
		if ( 'option' === $value['hide_if_checked'] ) {
			$visibility_class[] = 'hide_options_if_checked';
		}
		if ( 'option' === $value['show_if_checked'] ) {
			$visibility_class[] = 'show_options_if_checked';
		}

		$value['visibility_class'] = $visibility_class;

		ecp_get_view( 'fields/html-form-field-checkbox.php', $value );
	}

	public function render_fieldset_end( $value ) {
		ecp_get_view( 'fields/html-form-fieldset-end.php', $value );
	}

	public function render_field_single_select_country( $value ) {
		$country_setting = $value['option_value'];

		if ( strstr( $country_setting, ':' ) ) {
			$country_setting  = explode( ':', $country_setting );
			$value['country'] = current( $country_setting );
			$value['state']   = end( $country_setting );
		} else {
			$value['country'] = $country_setting;
			$value['state']   = '*';
		}

		ecp_get_view( 'fields/html-form-field-single-select-country.php', $value );
	}
}
