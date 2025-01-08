<?php

defined( 'ABSPATH' ) || exit;


/**
 * <h2>Autoloader for ECOMMPAY Gateway.</h2>
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Import external helpers
require_once __DIR__ . '/../helpers/ecp-helper.php';
require_once __DIR__ . '/../helpers/ecp-order.php';
require_once __DIR__ . '/../helpers/ecp-payment.php';
require_once __DIR__ . '/../helpers/ecp-subscription.php';
require_once __DIR__ . '/../helpers/notices.php';
require_once __DIR__ . '/../helpers/permissions.php';

// Import interfaces
require_once __DIR__ . '/interfaces/EcpGatewaySerializerInterface.php';

// Import install package
require_once __DIR__ . '/install/EcpGatewayInstall.php';

// Import internal helpers
require_once __DIR__ . '/helpers/EcpGatewayRegistry.php';
require_once __DIR__ . '/helpers/EcpGatewayArray.php';
require_once __DIR__ . '/helpers/EcpGatewayJson.php';
require_once __DIR__ . '/helpers/EcpGatewayOperationStatus.php';
require_once __DIR__ . '/helpers/EcpGatewayOperationType.php';
require_once __DIR__ . '/helpers/EcpGatewayPaymentStatus.php';
require_once __DIR__ . '/helpers/EcpGatewayPaymentStatusTransition.php';
require_once __DIR__ . '/helpers/EcpGatewayPaymentMethods.php';
require_once __DIR__ . '/helpers/EcpGatewayRecurringStatus.php';
require_once __DIR__ . '/helpers/EcpGatewayRecurringTypes.php';
require_once __DIR__ . '/helpers/EcpGatewayAPIProtocol.php';
require_once __DIR__ . '/helpers/WCOrderStatus.php';

// Import log package
require_once __DIR__ . '/log/EcpGatewayLog.php';

// Import exception package
require_once __DIR__ . '/exceptions/EcpGatewayError.php';
require_once __DIR__ . '/exceptions/EcpGatewayException.php';
require_once __DIR__ . '/exceptions/EcpGatewayDuplicateException.php';
require_once __DIR__ . '/exceptions/EcpGatewayErrorException.php';
require_once __DIR__ . '/exceptions/EcpGatewayAPIException.php';
require_once __DIR__ . '/exceptions/EcpGatewayDuplicateException.php';
require_once __DIR__ . '/exceptions/EcpGatewayInvalidArgumentException.php';
require_once __DIR__ . '/exceptions/EcpGatewayKeyNotFoundException.php';
require_once __DIR__ . '/exceptions/EcpGatewayLogicException.php';
require_once __DIR__ . '/exceptions/EcpGatewayNotAvailableException.php';
require_once __DIR__ . '/exceptions/EcpGatewayNotImplementedException.php';
require_once __DIR__ . '/exceptions/EcpGatewaySignatureException.php';

// Import API package
require_once __DIR__ . '/api/EcpGatewayAPI.php';
require_once __DIR__ . '/api/EcpGatewayAPIPayment.php';

// Import includes
require_once __DIR__ . '/includes/EcpGatewayOrderExtension.php';
require_once __DIR__ . '/includes/EcpCallbacksHandler.php';
require_once __DIR__ . '/includes/EcpGatewayFormHandler.php';
require_once __DIR__ . '/includes/EcpGatewayOrder.php';
require_once __DIR__ . '/includes/EcpGatewayPayment.php';
require_once __DIR__ . '/includes/EcpGatewayRefund.php';
require_once __DIR__ . '/includes/EcpGatewayPaymentProvider.php';


// Import modules
require_once __DIR__ . '/modules/EcpModuleAdminUI.php';
require_once __DIR__ . '/modules/EcpModulePaymentPage.php';
require_once __DIR__ . '/modules/EcpModuleRefund.php';
require_once __DIR__ . '/modules/EcpModuleAuth.php';
require_once __DIR__ . '/modules/EcpModuleCancel.php';
require_once __DIR__ . '/modules/EcpModuleCapture.php';
require_once __DIR__ . '/modules/EcpSigner.php';

// Import models
require_once __DIR__ . '/models/EcpGatewayInfoAccount.php';
require_once __DIR__ . '/models/EcpGatewayInfoACS.php';
require_once __DIR__ . '/models/EcpGatewayInfoBilling.php';
require_once __DIR__ . '/models/EcpGatewayInfoCallback.php';
require_once __DIR__ . '/models/EcpGatewayInfoCustomer.php';
require_once __DIR__ . '/models/EcpGatewayInfoError.php';
require_once __DIR__ . '/models/EcpGatewayInfoOperation.php';
require_once __DIR__ . '/models/EcpGatewayInfoOperationFee.php';
require_once __DIR__ . '/models/EcpGatewayInfoPayment.php';
require_once __DIR__ . '/models/EcpGatewayInfoProvider.php';
require_once __DIR__ . '/models/EcpGatewayInfoResponse.php';
require_once __DIR__ . '/models/EcpGatewayInfoStatus.php';
require_once __DIR__ . '/models/EcpGatewayInfoSum.php';

// Import settings
require_once __DIR__ . '/settings/EcpSettings.php';
require_once __DIR__ . '/settings/EcpSettingsGeneral.php';
require_once __DIR__ . '/settings/EcpSettingsProducts.php';
require_once __DIR__ . '/settings/EcpSettingsSubscriptions.php';
require_once __DIR__ . '/settings/EcpSettingsCard.php';
require_once __DIR__ . '/settings/EcpSettingsPayPal.php';
require_once __DIR__ . '/settings/EcpSettingsPayPalPayLater.php';
require_once __DIR__ . '/settings/EcpSettingsKlarna.php';
require_once __DIR__ . '/settings/EcpSettingsGiropay.php';
require_once __DIR__ . '/settings/EcpSettingsSofort.php';
require_once __DIR__ . '/settings/EcpSettingsBlik.php';
require_once __DIR__ . '/settings/EcpSettingsIdeal.php';
require_once __DIR__ . '/settings/EcpSettingsBanks.php';
require_once __DIR__ . '/settings/EcpSettingsApplepay.php';
require_once __DIR__ . '/settings/EcpSettingsMore.php';
require_once __DIR__ . '/settings/EcpSettingsGooglepay.php';
require_once __DIR__ . '/settings/EcpSettingsBrazilOnline_Banks.php';
require_once __DIR__ . '/settings/EcpSettingsDirectDebitBACS.php';
require_once __DIR__ . '/settings/EcpSettingsDirectDebitSEPA.php';
require_once __DIR__ . '/settings/forms/EcpForm.php';

if ( ecp_subscription_is_active() ) {
	require_once __DIR__ . '/api/EcpGatewayAPISubscription.php';
	require_once __DIR__ . '/includes/EcpGatewaySubscription.php';
	require_once __DIR__ . '/modules/EcpModuleSubscription.php';
	require_once __DIR__ . '/models/EcpGatewayInfoRecurring.php';
}

// Import main class
require_once __DIR__ . '/EcpCore.php';
require_once __DIR__ . '/gateways/EcpGateway.php';
require_once __DIR__ . '/gateways/EcpCard.php';
require_once __DIR__ . '/gateways/EcpPayPal.php';
require_once __DIR__ . '/gateways/EcpPayPalPayLater.php';
require_once __DIR__ . '/gateways/EcpKlarna.php';
require_once __DIR__ . '/gateways/EcpGiropay.php';
require_once __DIR__ . '/gateways/EcpSofort.php';
require_once __DIR__ . '/gateways/EcpBlik.php';
require_once __DIR__ . '/gateways/EcpIdeal.php';
require_once __DIR__ . '/gateways/EcpBanks.php';
require_once __DIR__ . '/gateways/EcpGooglepay.php';
require_once __DIR__ . '/gateways/EcpApplepay.php';
require_once __DIR__ . '/gateways/EcpMore.php';
require_once __DIR__ . '/gateways/EcpBrazilOnlineBanks.php';
require_once __DIR__ . '/gateways/EcpDirectDebitBACS.php';
require_once __DIR__ . '/gateways/EcpDirectDebitSEPA.php';


require_once __DIR__ . '/includes/EcpGatewayBlocksSupport.php';
