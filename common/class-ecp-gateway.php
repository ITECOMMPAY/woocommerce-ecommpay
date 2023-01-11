<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * <h2>ECOMMPAY Gateway.</h2>
 *
 * @class    WC_Gateway_Ecommpay
 * @version  2.0.0
 * @package  Woocommerce_Ecommpay/Classes
 * @category Class
 */
class Ecp_Gateway extends WC_Payment_Gateway
{
    // region Constants

    /**
     * <h2>Test project identifier.</h2>
     *
     * @var int
     * @since 2.0.0
     */
    const TEST_PROJECT_ID = 112;

    /**
     * <h2>Secret key for the test project.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    const TEST_PROJECT_KEY = 'kHRhsQHHhOUHeD+rt4kgH7OZiwE=';

    /**
     * <h2>Identifier for interface type.</h2>
     *
     * @var int
     * @since 2.0.0
     */
    const INTERFACE_TYPE = 18;

    /**
     * <h2>Global prefix for internal actions.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    const CMS_PREFIX = 'wp_ecp';

    /**
     * <h2>Plugin version.</h2>
     * <p>Sent into headers for open PP and Gate 2025 API.</p>
     *
     * @var string
     * @since 2.0.0
     */
    const WC_ECP_VERSION = '2.2.0';

    // endregion

    // region Properties

    /**
     * @inheritDoc
     * @override
     * @var string[]
     * @since 1.0.0
     */
    public $supports = [
        'subscriptions',
        'products',
        'subscription_cancellation',
        'subscription_reactivation',
        'subscription_suspension',
        'subscription_amount_changes',
        'subscription_date_changes',
//        'subscription_payment_method_change_admin',
//        'subscription_payment_method_change_customer',
        'refunds',
        'multiple_subscriptions',
//        'pre-orders',
    ];

    /**
     * <h2>Instance of ECOMMPAY Gateway.</h2>
     *
     * @var Ecp_Gateway
     * @since 2.0.0
     */
    private static $_instance;

    // endregion

    // region Static methods

    /**
     * <h2>Returns a new instance of self, if it does not already exist.</h2>
     *
     * @return static
     * @since 2.0.0
     */
    public static function get_instance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * <h2>Returns the ECOMMPAY external interface type.</h2>
     *
     * @return array
     * @since 1.0.0
     */
    public static function get_interface_type()
    {
        return [
            'id' => self::INTERFACE_TYPE,
        ];
    }

    /**
     * <h2>Adds action links inside the plugin overview.</h2>
     *
     * @return array <p>Action link list.</p>
     * @since 2.0.0
     */
    public static function add_action_links($links)
    {
        return array_merge([
            '<a href="' . ecp_settings_page_url() . '">' . __('Settings', 'woo-ecommpay') . '</a>',
        ], $links);
    }
    // endregion

    /**
     * <h2>ECOMMPAY Gateway constructor.</h2>
     */
    public function __construct()
    {
        $this->id = 'ecommpay';
        $this->method_title = __('ECOMMPAY', 'woo-ecommpay');
        $this->method_description = __('Allows you to pay in various ways through the ECOMMPAY Payment Page.', 'woo-ecommpay');
        $this->icon = '';
        $this->has_fields = false;

        // Load the form fields and settings
        $this->init_settings();
        $this->init_form_fields();

        $this->title = $this->get_option(Ecp_Gateway_Settings_Page::OPTION_TITLE);
        $this->description = $this->get_option(Ecp_Gateway_Settings_Page::OPTION_DESCRIPTION);
    }

    /**
     * <h2>Init settings for gateways.</h2>
     *
     * @override
     * @return void
     * @since 2.0.3
     */
    public function init_settings()
    {
        $this->settings = get_option($this->get_option_key(), null);

        // If there are no settings defined, use defaults.
        if (!is_array($this->settings)) {
            $this->settings = Ecp_Gateway_Settings::get_instance()->get_default_settings();
        }

        $this->enabled = $this->settings[Ecp_Gateway_Settings_Page::OPTION_ENABLED];
    }

    /**
     * <h2>Returns the merchant project identifier.</h2>
     *
     * @return int
     * @since 2.0.0
     */
    public function get_project_id()
    {
        return ecp_is_enabled(Ecp_Gateway_Settings_Page::OPTION_TEST)
            ? self::TEST_PROJECT_ID
            : (int)$this->get_option(Ecp_Gateway_Settings_Page::OPTION_PROJECT_ID);
    }

    /**
     * <h2>Applies plugin hooks and filters.</h2>
     *
     * @return void
     * @since 2.0.0
     */
    public function hooks()
    {
        Ecp_Gateway_Module_Admin_UI::get_instance();
        Ecp_Gateway_Module_Payment_Page::get_instance();
        Ecp_Gateway_Module_Refund::get_instance();
        Ecp_Gateway_API_Protocol::get_instance();

        if (ecp_subscription_is_active()) {
            WC_Gateway_Ecommpay_Module_Subscription::get_instance();
        }

        add_action('woocommerce_api_wc_' . $this->id, [Ecp_Gateway_Callbacks::class, 'handle']);

        $this->installation_hooks();

        add_filter(
            'plugin_action_links_plugin-ecommpay/gateway-ecommpay.php',
            [$this, 'add_action_links']
        );
    }

    /**
     * <h2>Show plugin changes. Code adapted from W3 Total Cache.</h2>
     *
     * @return void
     * @since 2.0.0
     * @todo Place this plugin into WordPress SVN repository!!!
     */
    public function in_plugin_update_message($args)
    {
        $transient_name = 'wc_ecp_upgrade_notice_' . $args['Version'];
        if (false === ($upgrade_notice = get_transient($transient_name))) {
            $response = wp_remote_get('https://plugins.svn.wordpress.org/woocommerce-ecommpay/trunk/README.txt');

            if (!is_wp_error($response) && !empty($response['body'])) {
                $upgrade_notice = self::parse_update_notice($response['body']);
                set_transient($transient_name, $upgrade_notice, DAY_IN_SECONDS);
            }
        }

        echo wp_kses_post($upgrade_notice);
    }

    /**
     * <h2>Processes and saves options.</h2>
     * <p>Overrides the base function and always return true.</p>
     *
     * @override
     * @return bool
     * @since 2.0.0
     */
    public function process_admin_options()
    {
        return true;
    }

    /**
     * @inheritDoc
     * @override
     * @return void
     * @since 2.0.0
     */
    public function init_form_fields()
    {
        $this->form_fields = Ecp_Gateway_Settings::get_instance()->get_form_fields();
    }

    /**
     * <h2>Generate Settings HTML.</h2>
     * <p>Overrides the base function and does nothing.</p>
     *
     * @override
     * @return void
     * @since 2.0.0
     */
    public function generate_settings_html($form_fields = [], $echo = true)
    {
    }

    /**
     * <h2>Output the admin options table.</h2>
     * <p>Overrides the base function and renders an HTML-page.</p>
     *
     * @override
     * @return void
     * @since 2.0.0
     */
    public function admin_options()
    {
        echo '<img src="' . ecp_img_url('ecommpay.svg') . '" alt="" class="ecp_logo right">';
        echo '<h2>' . esc_html($this->get_method_title());
        wc_back_link(__('Return to payments', 'woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout'));
        echo '</h2>';
        echo wp_kses_post(wpautop($this->get_method_description()));
        Ecp_Gateway_Settings::get_instance()->output();
    }

    /**
     * @inheritDoc
     * @override
     * @return array <p>Settings for redirecting to the ECOMMPAY payment page.</p>
     * @throws Ecp_Gateway_Signature_Exception <p>If the signature could not be created.</p>
     * @since 2.0.0
     */
    public function process_payment($order_id)
    {
        $order = ecp_get_order($order_id);
        $order->update_status('pending', _x('Awaiting payment', 'Status payment', 'woo-ecommpay'));

        return [
            'result' => 'success',
            'redirect' => ecp_payment_page()->get_request_url($order),
            'order_id' => $order_id,
        ];
    }

    /**
     * @inheritDoc
     * @override
     * @return bool <p><b>TRUE</b> on process completed successfully, <b>FALSE</b> otherwise.</p>
     * @throws Ecp_Gateway_Logic_Exception <p>If a refund is not available for the selected order.</p>
     * @throws Ecp_Gateway_API_Exception <p>If the API response does not contain the required information.</p>
     * @throws WC_Data_Exception <p>If the data is corrupted while saving.</p>
     * @since 2.0.0
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        return Ecp_Gateway_Module_Refund::get_instance()->process($order_id, $amount, $reason);
    }

    /**
     * @inheritDoc
     * <p>If false, the automatic refund button is hidden in the UI.</p>
     *
     * @param WC_Order $order <p>Order object.</p>
     * @override
     * @return bool <p><b>TRUE</b> if a refund available for the order, or <b>FALSE</b> otherwise.</p>
     * @since 2.0.0
     */
    public function can_refund_order($order)
    {
        if (!$order) {
            ecp_get_log()->debug(
                _x('Undefined argument order. Hide refund via ECOMMPAY button.', 'Log information', 'woo-ecommpay')
            );
            return false;
        }

        $order = ecp_get_order($order);

        // Check if there is a ECOMMPAY payment
        if (!$order->is_ecp()) {
            return false;
        }

        return Ecp_Gateway_Module_Refund::get_instance()->is_available($order);
    }

    /**
     * <h2>Returns the redeclaration of the class name for the object type.</h2>
     *
     * @param string $classname <p>Base class name.</p>
     * @param string $type <p>Object type.</p>
     * @return string <p>Wrapped or base class name.</p>
     * @since 2.0.0
     */
    public function type_wrapper($classname, $type)
    {
        switch ($type) {
            case 'shop_order':
                return Ecp_Gateway_Order::class;
            case 'shop_order_refund':
                return Ecp_Gateway_Refund::class;
            case 'shop_subscription':
                return Ecp_Gateway_Subscription::class;
            default:
                return $classname;
        }
    }

    // region Private methods

    /**
     * <h2>Parse update notice from readme file.</h2>
     *
     * @param string $content
     * @return string
     * @since 2.0.0
     */
    private function parse_update_notice($content)
    {
        // Output Upgrade Notice
        $matches = null;
        $regexp = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*'
            . preg_quote(self::WC_ECP_VERSION, '/') . '\s*=|$)~Uis';
        $upgrade_notice = '';

        if (preg_match($regexp, $content, $matches)) {
            $version = trim($matches[1]);
            $notices = (array)preg_split('~[\r\n]+~', trim($matches[2]));

            if (version_compare(self::WC_ECP_VERSION, $version, '<')) {

                $upgrade_notice .= '<div class="wc_plugin_upgrade_notice">';

                foreach ($notices as $line) {
                    /** @noinspection HtmlUnknownTarget */
                    $upgrade_notice .= wp_kses_post(preg_replace(
                            '~\[([^]]*)]\(([^)]*)\)~',
                            '<a href="${2}">${1}</a>',
                            $line)
                    );
                }

                $upgrade_notice .= '</div> ';
            }
        }

        return wp_kses_post($upgrade_notice);
    }

    /**
     * <h2>Setup plugin installation hooks.</h2>
     *
     * @return void
     * @since 2.0.0
     */
    private function installation_hooks()
    {
        add_action('wp_ajax_ecommpay_run_data_upgrader', [Ecp_Gateway_Install::get_instance(), 'ajax_run_upgrade']);
        add_action(
            'in_plugin_update_message-woocommerce-ecommpay/woocommerce-ecommpay.php',
            [$this, 'in_plugin_update_message']
        );
    }

    // endregion
}
