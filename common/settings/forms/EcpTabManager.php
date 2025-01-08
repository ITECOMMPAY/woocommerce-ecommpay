<?php

namespace common\settings\forms;

use common\includes\filters\EcpHtmlFilterList;
use common\settings\EcpSettings;
use common\settings\EcpSettingsApplepay;
use common\settings\EcpSettingsBanks;
use common\settings\EcpSettingsBlik;
use common\settings\EcpSettingsBrazilOnline_Banks;
use common\settings\EcpSettingsCard;
use common\settings\EcpSettingsDirectDebitBACS;
use common\settings\EcpSettingsDirectDebitSEPA;
use common\settings\EcpSettingsGeneral;
use common\settings\EcpSettingsGiropay;
use common\settings\EcpSettingsGooglepay;
use common\settings\EcpSettingsIdeal;
use common\settings\EcpSettingsKlarna;
use common\settings\EcpSettingsMore;
use common\settings\EcpSettingsPayPal;
use common\settings\EcpSettingsPayPalPayLater;
use common\settings\EcpSettingsProducts;
use common\settings\EcpSettingsSofort;
use common\settings\EcpSettingsSubscriptions;

class EcpTabManager {

	/**
	 * Setting pages.
	 *
	 * @var EcpSettings[]
	 */
	public array $tabs = [];

	/**
	 * @return array
	 */
	public function get_tabs(): array {
		return $this->tabs;
	}

	public function init_tabs() {
		if ( empty ( $this->tabs ) ) {
			$tabs = [
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
				new EcpSettingsSofort(),
				new EcpSettingsIdeal(),
				new EcpSettingsKlarna(),
				new EcpSettingsBlik(),
				new EcpSettingsGiropay(),
				new EcpSettingsBrazilOnline_Banks(),
				new EcpSettingsMore(),
			];

			$this->tabs = apply_filters( EcpHtmlFilterList::ECP_GET_SETTINGS_PAGES, $tabs );
		}
	}

	public function get_section() {
		$current_tab = $_REQUEST['section'];
		if ( ! empty( wc_get_var( $_REQUEST['sub'] ) ) ) {
			$current_tab = $_REQUEST['sub'];
		}

		return $current_tab;
	}
}
