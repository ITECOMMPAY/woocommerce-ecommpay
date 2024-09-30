<?php
/**
 * <h2>Autoloader for ECOMMPAY Gateway.</h2>
 */

// Import external helpers
require_once __DIR__ . '/../helpers/ecp-helper.php';                                // Base functions
require_once __DIR__ . '/../helpers/ecp-order.php';                                 // Order functions
require_once __DIR__ . '/../helpers/ecp-payment.php';                               // Payment functions
require_once __DIR__ . '/../helpers/ecp-subscription.php';                          // Subscription functions
require_once __DIR__ . '/../helpers/notices.php';                                   // Notice functions
require_once __DIR__ . '/../helpers/permissions.php';                               // Permission functions

// Import interfaces
require_once __DIR__ . '/interfaces/interface-ecp-gateway-serializer.php';

// Import install package
require_once __DIR__ . '/install/class-ecp-gateway-install.php';

// Import internal helpers
require_once __DIR__ . '/helpers/abstract-ecp-gateway-registry.php';                // Abstract registry
require_once __DIR__ . '/helpers/class-ecp-gateway-array.php';                      // Base array object
require_once __DIR__ . '/helpers/class-ecp-gateway-json.php';                       // Base JSON object
require_once __DIR__ . '/helpers/class-ecp-gateway-operation-status.php';           // Internal transaction statuses
require_once __DIR__ . '/helpers/class-ecp-gateway-operation-types.php';            // Internal transaction types
require_once __DIR__ . '/helpers/class-ecp-gateway-payment-status.php';             // Internal payment statuses
require_once __DIR__ . '/helpers/class-ecp-gateway-payment-status-transition.php';  // Payment statuses transition
require_once __DIR__ . '/helpers/class-ecp-gateway-payment-methods.php';            // Internal payment methods
require_once __DIR__ . '/helpers/class-ecp-gateway-recurring-status.php';           // Internal recurring statuses
require_once __DIR__ . '/helpers/class-ecp-gateway-recurring-types.php';            // Internal recurring types
require_once __DIR__ . '/helpers/class-ecp-gateway-api-protocol.php';               // Internal API protocol

// Import log package
require_once __DIR__ . '/log/class-ecp-gateway-log.php';

// Import exception package
require_once __DIR__ . '/exceptions/abstract-ecp-gateway-exception.php';
require_once __DIR__ . '/exceptions/class-ecp-gateway-error.php';
require_once __DIR__ . '/exceptions/class-ecp-gateway-error-exception.php';
require_once __DIR__ . '/exceptions/class-ecp-gateway-api-exception.php';
require_once __DIR__ . '/exceptions/class-ecp-gateway-duplicate-exception.php';
require_once __DIR__ . '/exceptions/class-ecp-gateway-invalid-argument-exception.php';
require_once __DIR__ . '/exceptions/class-ecp-gateway-key-not-found-exception.php';
require_once __DIR__ . '/exceptions/class-ecp-gateway-logic-exception.php';
require_once __DIR__ . '/exceptions/class-ecp-gateway-not-available-exception.php';
require_once __DIR__ . '/exceptions/class-ecp-gateway-not-implemented-exception.php';
require_once __DIR__ . '/exceptions/class-ecp-gateway-signature-exception.php';

// Import API package
require_once __DIR__ . '/api/class-ecp-gateway-api.php';                            // Base API
require_once __DIR__ . '/api/class-ecp-gateway-api-payment.php';                    // Payment API

// Import includes
require_once __DIR__ . '/includes/trait-ecp-gateway-order-extension.php';           // Trait order extension
require_once __DIR__ . '/includes/class-ecp-gateway-callbacks.php';                 // Callback handler
require_once __DIR__ . '/includes/class-ecp-gateway-form-handler.php';              // Form handler
require_once __DIR__ . '/includes/class-ecp-gateway-order.php';                     // Order wrapper
require_once __DIR__ . '/includes/class-ecp-gateway-payment.php';                   // Payment object
require_once __DIR__ . '/includes/class-ecp-gateway-refund.php';                    // Refund wrapper
require_once __DIR__ . '/includes/class-ecp-gateway-payment-provider.php';          // Payment provider

// Import modules
require_once __DIR__ . '/modules/class-ecp-gateway-module-admin-ui.php';            // Admin UI
require_once __DIR__ . '/modules/class-ecp-gateway-module-payment-page.php';        // Payment Page
require_once __DIR__ . '/modules/class-ecp-gateway-module-refund.php';              // Refund controller
require_once __DIR__ . '/modules/class-ecp-gateway-signer.php';                     // Signer

// Import models
require_once __DIR__ . '/models/class-ecp-gateway-info-account.php';                // Account data
require_once __DIR__ . '/models/class-ecp-gateway-info-acs.php';                    // ACS data
require_once __DIR__ . '/models/class-ecp-gateway-info-billing.php';                // Billing data
require_once __DIR__ . '/models/class-ecp-gateway-info-callback.php';               // Callback data
require_once __DIR__ . '/models/class-ecp-gateway-info-customer.php';               // Customer data
require_once __DIR__ . '/models/class-ecp-gateway-info-error.php';                  // Error data
require_once __DIR__ . '/models/class-ecp-gateway-info-operation.php';              // Transaction data
require_once __DIR__ . '/models/class-ecp-gateway-info-operation-fee.php';          // Transaction fee data
require_once __DIR__ . '/models/class-ecp-gateway-info-payment.php';                // Payment data
require_once __DIR__ . '/models/class-ecp-gateway-info-provider.php';               // Provider data
require_once __DIR__ . '/models/class-ecp-gateway-info-response.php';               // Response data
require_once __DIR__ . '/models/class-ecp-gateway-info-status.php';                 // Status data
require_once __DIR__ . '/models/class-ecp-gateway-info-sum.php';                    // Amount data

// Import settings
require_once __DIR__ . '/settings/abstract-ecp-gateway-settings.php';               // Abstract settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-general.php';          // General settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-card.php';             // Card settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-paypal.php';           // PayPal settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-paypal-paylater.php';  // PayPal PayLater settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-klarna.php';           // Klarna settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-giropay.php';          // Giropay settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-sofort.php';           // Sofort settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-blik.php';             // Blik settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-ideal.php';            // iDEAL settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-banks.php';            // Banks settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-applepay.php';         // Apple Pay settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-more.php';             // More payments settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-googlepay.php';        // GooglePay settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-brazil.php';           // Brazil online banks settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-directdebit-bacs.php'; // Direct Debit BACS settings
require_once __DIR__ . '/settings/class-ecp-gateway-settings-directdebit-sepa.php'; // Direct Debit SEPA settings
require_once __DIR__ . '/settings/class-ecp-form.php';                              // Settings main page

if ( ecp_subscription_is_active() ) {
	require_once __DIR__ . '/api/class-ecp-gateway-api-subscription.php';               // Subscription API
	require_once __DIR__ . '/includes/class-ecp-gateway-subscription.php';              // Subscription wrapper
	require_once __DIR__ . '/modules/class-ecp-gateway-module-subscription.php';        // Subscription controller
	require_once __DIR__ . '/models/class-ecp-gateway-info-recurring.php';              // Recurring data
}

// Import main class
require_once __DIR__ . '/class-ecp-core.php';                                            // Core
require_once __DIR__ . '/gateways/abstract-ecp-gateway.php';                             // Abstract Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-card.php';                           // Card Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-paypal.php';                         // PayPal Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-paypal-paylater.php';                // PayPal PayLater Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-klarna.php';                         // Klarna Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-giropay.php';                        // Giropay Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-sofort.php';                         // Sofort Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-blik.php';                           // Blik Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-ideal.php';                          // iDEAL Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-banks.php';                          // Banks Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-googlepay.php';                      // Banks Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-applepay.php';                       // Apple Pay Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-more.php';                           // More PM Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-brazil.php';                         // Brazil online banks Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-directdebit-bacs.php';               // Direct Debit BACS Gateway
require_once __DIR__ . '/gateways/class-ecp-gateway-directdebit-sepa.php';               // Direct Debit SEPA Gateway


// Import payment method class for checkout blocks
require_once __DIR__ . '/includes/class-ecp-gateway-blocks-support.php';
