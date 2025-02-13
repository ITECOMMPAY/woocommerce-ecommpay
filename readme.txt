=== ECOMMPAY Payments ===
Contributors: ECOMMPAY
Tags: card payments, apple pay, google pay, open banking, subscriptions, paypal, sofort, ideal, klarna, giropay, payment gateway, woocommerce
Requires at least: 6.2
Tested up to: 6.7
Stable tag: 4.0.3
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept bank transfers, cards, local payment methods and cryptocurrencies. Boost conversion with a customisable checkout form. Enjoy 24/7 expert support.

== Description ==
ECOMMPAY’s WooCommerce plugin is a complete growth-focused payment solution for merchants looking to dominate local markets or expand globally, maximise profits and reduce operational costs.

Accept card, bank, eWallet and crypto payments. Offer flexible subscriptions and recurring payment plans. Make payouts in any local currency and receive weekly or even more frequent settlements in EUR or GBP. Enjoy industry-leading support, low and transparent fees and advanced checkout form customisation options, including full localisation to any language.

The plugin is available to every business in the EEA and the UK. The integration is quick and intuitive and usually takes 1-2 business days. Moving from another payment service provider? No worries. Our migration process is simple yet powerful enough to keep your subscriptions and recurring plans intact.

== Feature highlights ==

= Cards =
Accept VISA, Mastercard, American Express or Union Pay. Maximise acceptance rates and avoid double conversion with Smart Payment Rooting and Cascading technologies on board.
= Open Banking =
Let your customers pay with their bank of choice, reduce processing fees and eliminate the risk of chargebacks. Works with 2000+ banks in Europe and the UK.
= Cryptocurrencies =
Take payments in all the popular cryptocurrencies and settle in USD, GBP or EUR without the conversion risk.
= eWallets =
Offer an option to pay Apple Pay and Google Pay or local eWallets your customers know and trust, like Blik, Bancontact, EPS, Giropay, iDEAL, Multibanco, Neteller and more.
= Subscriptions and recurring payments =
Offer your customers subscriptions or flexible recurring payment plans. Migrate from your old payment service provider without any interruption.
= Payment links =
Create payment links with a few clicks and let your customers pay straight from their email, messenger apps or SMS.
= Payouts =
Make refunds or pay your suppliers and business partners in any currency. Payouts are delivered in 30 minutes after the approval.
= Customisation =
Fine-tune the look and feel of your checkout form to reach the maximum conversion. Customise the design, available payment methods and languages.
= Support =
Enjoy industry-leading support with an average response time of 15 minutes. We are always by your side to help with technical issues and share our knowledge of local markets.
= Settlements =
Receive weekly or even more frequent settlements in EUR, USD or GBP.

== Installation ==
1. Upload the 'woocommerce-ecommpay' folder to /wp-content/plugins/ on your server.
2. Log in to WordPress administration, click on the 'Plugins' tab.
3. Find ECOMMPAY in the plugin overview and activate it.
4. Go to WooCommerce -> Settings -> Payment Gateways -> ECOMMPAY.
5. Fill the fields "Project ID" and "Secret key" in the "General Settings" section on "General" tab and save the settings.
6. You are good to go.

== How do I start? ==
1. Download and install our free WooCommerce plugin. It’s quick and easy. Feel free to test it any time
2. [Create a merchant account](https://ecommpay.com/technologies/integrations/woocommerce-payment-gateway/?utm_source=plugin_description&utm_medium=link&utm_campaign=woocommerce) with ECOMMPAY and provide all the necessary documents
3. Once approved, go live and start accepting payments in just a couple of days.
4. Receive weekly or even more frequent settlements.
5. Scale your business easily and expand to new markets with the same plugin.

== Dependencies ==
General:
1. PHP: >= 7.4
2. WooCommerce: >= 8.2
3. If WooCommerce Subscriptions is used, the required minimum version is >= 5.6.1

== Changelog ==
= 4.0.1 =
* Dev: Code reformatting

= 4.0.0 =
* Feature: Two-Step Payments mode added

= 3.5.0 =
* Feature: Compatible with refunds via Ecommpay Dashboard
* Feature: Added dedicated Direct Debit methods for recurring payments. Learn more on our [website](https://ecommpay.com/payment-methods/direct-debit/?utm_source=plugin_description&utm_medium=link&utm_campaign=woocommerce)

= 3.4.0 =
* Dev: Compatible with WooCommerce 8.3.0 - 8.6.1
* Feature: Compatible with WooCommerce Block-based Checkout Page
* Feature: Compatible with WooCommerce High-Perfomance-Order-Storage

= 3.3.4 =
* Dev: Changed customer ip address for recurring payments
* Dev: Optimized checkout script for embedded mode

= 3.3.2 =
* Feature: Pressing the enter key starts the place ordering process in the embedded mode

= 3.3.1 =
* Fix: Added migration for orders and subscriptions from version 2.x.x to 3.x.x

= 3.3.0 =
* Feature: Integration of Brazil Online Banks as a payment method
* Fix: Implemented a preventive mechanism to restrict cart amount changes after opening the payment page in embedded mode

= 3.2.0 =
* Feature: Updated Payment Page Display: We have replaced the previous iFrame payment page display mode with the new "Embedded" mode. This change allows for a smoother payment flow by seamlessly integrating the payment page within the checkout page. Users no longer need to be redirected to a separate page to complete their payments.
* Feature: Simplified Plugin Settings: We have streamlined the plugin's settings to make it more user-friendly and easier to configure.

= 3.1.0 =
* Feature: Main payment methods added as standalone gateways to improve merchant and end users experience

= 3.0.0 =
* Feature: Main payment methods splitted into configured standalone solutions with better UX for the end users.

= 2.2.1 =
* Dev: Escaped all variables in the html view.
* Dev: Logic of formed a tooltips is modified.
* Dev: All incoming data are sanitized and verified.
* Dev: Replace CURL by WordPress HTTP Api library.
* Fix: Fatal error on trying cancel subscription.

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