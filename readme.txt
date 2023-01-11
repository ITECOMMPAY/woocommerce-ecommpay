=== ECOMMPAY Payments ===
Contributors: ECOMMPAY
Tags: gateway, woo commerce, ecommpay, gateway, integration, woocommerce, woocommerce ecommpay, payment, payment gateway, psp
Requires at least: 4.0.0
Tested up to: 6.0
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrates your ECOMMPAY payment gateway into your WooCommerce installation.

== Description ==
With ECOMMPAY Payments, you are able to integrate your ECOMMPAY gateway to your WooCommerce install.
A wide list of API features including refunding payments directly from your WooCommerce order overview.
This is only a part of the many features found in this plugin.

== Installation ==
1. Upload the 'woocommerce-ecommpay' folder to /wp-content/plugins/ on your server.
2. Log in to WordPress administration, click on the 'Plugins' tab.
3. Find ECOMMPAY in the plugin overview and activate it.
4. Go to WooCommerce -> Settings -> Payment Gateways -> ECOMMPAY.
5. Fill the fields "Project ID" and "Secret key" in the "Integration" section on "General" tab and save the settings.
6. You are good to go.

== Dependencies ==
General:
1. PHP: >= 5.6
2. WooCommerce >= 5.0
3. If WooCommerce Subscriptions is used, the required minimum version is >= 2.0

== Changelog ==
= 2.2.0 =
* Feature: Implemented Awaiting confirmation payment state for OpenBanking.
* Feature: Implemented Receipt data.
* Dev: Potential compatibility issue with other payment platforms.
* Fix: Exception on invalid data from ECOMMPAY Payment Platform.

= 2.1.2 =
* Fix: Creating recurring data.

= 2.1.1 =
* Fix: Update version in WordPress database.
* Fix: Incorrect frame size in iFrame mode.

= 2.1.0 =
* Feature: Remove customer identifier for guest.
* Fix: Creates new order on cart change.
* Fix: Returns correct url on popup missclick.
* Fix: Fatal error in signer for callbacks.

= 2.0.3 =
* Feature: Remove last payment state from Subscription.
* Feature: Implemented migration from previous versions.
* Feature: Customer identifier always send to ECOMMPAY Payment Platform.
* Fix: Activation plugin switcher is not available.
* Fix: Cancelled subscription on refund.
* Fix: Attempt to receive an order by a canceled subscription.

= 2.0.2 =
* Fix: Exception on global handler error.

= 2.0.1 =
* Fix: Exception on undefined array key "ecommpay_test".

= 2.0.0 =
* Changed plugin structure.
* Implemented WooCommerce Subscription.
* Implemented payment state in order overview.
* Implemented billing and customer data.
* Refactoring.
* Easier installation.

= 1.0.0 =
* First release.