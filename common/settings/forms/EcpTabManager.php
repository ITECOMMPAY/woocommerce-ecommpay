<?php

namespace common\settings\forms;

use common\includes\filters\EcpHtmlFilters;
use common\settings\EcpSettings;
use common\settings\EcpSettingsApplepay;
use common\settings\EcpSettingsBanks;
use common\settings\EcpSettingsBlik;
use common\settings\EcpSettingsBrazilOnline_Banks;
use common\settings\EcpSettingsCard;
use common\settings\EcpSettingsDirectDebitBACS;
use common\settings\EcpSettingsDirectDebitSEPA;
use common\settings\EcpSettingsGeneral;
use common\settings\EcpSettingsGooglepay;
use common\settings\EcpSettingsHumm;
use common\settings\EcpSettingsIdeal;
use common\settings\EcpSettingsKlarna;
use common\settings\EcpSettingsMore;
use common\settings\EcpSettingsPayPal;
use common\settings\EcpSettingsPayPalPayLater;
use common\settings\EcpSettingsProducts;
use common\settings\EcpSettingsSubscriptions;

class EcpTabManager {

	/**
	 * Setting pages.
	 *
	 * @var EcpSettings[]
	 */
	public array $tabs = array();

	/**
	 * @return array
	 */
	public function get_tabs(): array {
		return $this->tabs;
	}

	public function init_tabs() {
		if ( empty( $this->tabs ) ) {
			$tabs = array(
				new EcpSettingsGeneral(),
				new EcpSettingsProducts(),
				new EcpSettingsSubscriptions(),
				new EcpSettingsCard(),
				new EcpSettingsApplepay(),
				new EcpSettingsGooglepay(),
				new EcpSettingsDirectDebitBACS(),
				new EcpSettingsDirectDebitSEPA(),
				new EcpSettingsBanks(),
				new EcpSettingsPayPal(),
				new EcpSettingsPayPalPayLater(),
				new EcpSettingsIdeal(),
				new EcpSettingsKlarna(),
				new EcpSettingsBlik(),
				new EcpSettingsHumm(),
				new EcpSettingsBrazilOnline_Banks(),
				new EcpSettingsMore(),
			);

			$this->tabs = apply_filters( EcpHtmlFilters::ECP_GET_SETTINGS_PAGES, $tabs );
		}
	}

	/**
	 * Returns all registered tab IDs.
	 *
	 * @return string[]
	 */
	public function get_tab_ids(): array {
		return array_map(
			function ( EcpSettings $tab ) {
				return $tab->get_id();
			},
			$this->tabs
		);
	}

	/**
	 * Returns the section ID if it is a known registered tab, or the fallback otherwise.
	 *
	 * @param string $section  Raw section value to validate.
	 * @param string $fallback Fallback section ID.
	 *
	 * @return string
	 */
	public function resolve_section( string $section, string $fallback ): string {
		if ( in_array( $section, $this->get_tab_ids(), true ) ) {
			return $section;
		}

		return $fallback;
	}

	public function get_section(): string {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$raw_section = wc_get_var( $_REQUEST['section'], EcpSettingsGeneral::ID );
		$section     = ( ! is_array( $raw_section ) && '' !== (string) $raw_section )
			? sanitize_key( (string) $raw_section )
			: '';
		$current_tab = $this->resolve_section( $section, EcpSettingsGeneral::ID );

		$raw_sub = wc_get_var( $_REQUEST['sub'] );
		if ( ! empty( $raw_sub ) && ! is_array( $raw_sub ) ) {
			$sub         = sanitize_key( (string) $raw_sub );
			$current_tab = $this->resolve_section( $sub, $current_tab );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return $current_tab;
	}
}
