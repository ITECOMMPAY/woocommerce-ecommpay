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
interface Ecp_Gateway_Interface
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
     * <h2>Global prefix for internal actions.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    const CMS_PREFIX = 'wp_ecp';

    // endregion

    /**
     * <h2>Returns a new instance of self, if it does not already exist.</h2>
     *
     * @return static
     * @since 2.0.0
     */
    public static function get_instance();

    /**
     * <h2>Returns the ECOMMPAY external interface type.</h2>
     *
     * @return array
     * @since 1.0.0
     */
    public static function get_interface_type();

    /**
     * <h2>Adds action links inside the plugin overview.</h2>
     *
     * @return array <p>Action link list.</p>
     * @since 2.0.0
     */
    public static function add_action_links($links);
    // endregion

    /**
     * @return ?string
     * @since 3.0.0
     */
    public function get_force();

    /**
     * @return string
     * @since 3.0.0
     */
    public function get_pp_mode();

    /**
     * <h2>Returns the merchant project identifier.</h2>
     *
     * @return int
     * @since 2.0.0
     */
    public function get_project_id();

    /**
     * <h2>Processes and saves options.</h2>
     * <p>Overrides the base function and always return true.</p>
     *
     * @override
     * @return bool
     * @since 2.0.0
     */
    public function process_admin_options();

    /**
     * @inheritDoc
     * @override
     * @return void
     * @since 2.0.0
     */
    public function init_form_fields();

    /**
     * <h2>Generate Settings HTML.</h2>
     * <p>Overrides the base function and does nothing.</p>
     *
     * @override
     * @return void
     * @since 2.0.0
     */
    public function generate_settings_html($form_fields = [], $echo = true);

    /**
     * <h2>Output the admin options table.</h2>
     * <p>Overrides the base function and renders an HTML-page.</p>
     *
     * @override
     * @return void
     * @since 2.0.0
     */
    public function admin_options();

    /**
     * @inheritDoc
     * @override
     * @return array <p>Settings for redirecting to the ECOMMPAY payment page.</p>
     * @throws Ecp_Gateway_Signature_Exception <p>If the signature could not be created.</p>
     * @since 2.0.0
     */
    public function process_payment($order_id);

    /**
     * @inheritDoc
     * @override
     * @return bool <p><b>TRUE</b> on process completed successfully, <b>FALSE</b> otherwise.</p>
     * @throws Ecp_Gateway_Logic_Exception <p>If a refund is not available for the selected order.</p>
     * @throws Ecp_Gateway_API_Exception <p>If the API response does not contain the required information.</p>
     * @throws WC_Data_Exception <p>If the data is corrupted while saving.</p>
     * @since 2.0.0
     */
    public function process_refund($order_id, $amount = null, $reason = '');

    /**
     * @inheritDoc
     * <p>If false, the automatic refund button is hidden in the UI.</p>
     *
     * @param WC_Order $order <p>Order object.</p>
     * @override
     * @return bool <p><b>TRUE</b> if a refund available for the order, or <b>FALSE</b> otherwise.</p>
     * @since 2.0.0
     */
    public function can_refund_order($order);

    /**
     * <h2>Returns the redeclaration of the class name for the object type.</h2>
     *
     * @param string $classname <p>Base class name.</p>
     * @param string $type <p>Object type.</p>
     * @return string <p>Wrapped or base class name.</p>
     * @since 2.0.0
     */
    public function type_wrapper($classname, $type);
}
