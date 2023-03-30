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
abstract class Ecp_Gateway extends WC_Payment_Gateway
{
    public $id = Ecp_Gateway_Settings_General::ID;

    public $supports = '';

    public function __construct()
    {
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [Ecp_Form::get_instance(), 'save']);
        add_filter('ecp_append_gateway_arguments_' . $this->id, [$this, 'apply_payment_args'], 10, 2);
        add_filter('ecp_api_refund_endpoint_' . $this->id, [$this, 'get_refund_endpoint'], 10, 1);
    }

    /**
     * <h2>Init settings for gateways.</h2>
     *
     * @override
     * @return void
     * @since 3.0.0
     */
    public function init_settings()
    {
        $this->settings = ecommpay()->get_option($this->id);
    }

    /**
     * @param Ecp_Gateway_Order $order
     * @param array $values
     * @return array
     */
    public function apply_payment_args($values, $order)
    {
        return $values;
    }

    /**
     * @return string
     */
    public function get_refund_endpoint($order)
    {
        return '';
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
     * Checks if a setting options is enabled by checking on yes/no data.
     *
     * @param string $value
     *
     * @return bool
     * @since 3.0.0
     */
    final public function is_enabled($value)
    {
        return $this->get_option($value, Ecp_Gateway_Settings::NO) === Ecp_Gateway_Settings::YES;
    }

    /**
     * @inheritDoc
     * @override
     * @return bool
     * @since 3.0.0
     */
    final public function update_option($key, $value = '')
    {
        return ecommpay()->update_pm_option($this->id, $key, $value);
    }

    /**
     * @return string
     * @since 3.0.0
     */
    final public function get_option_key()
    {
        return ecommpay()->get_option_key();
    }

    /**
     * @inheritDoc
     * @override
     * @return void
     * @since 3.0.0
     */
    final public function init_form_fields()
    {
        foreach (ecommpay()->settings()->get_form_fields($this->id) as $field) {
            $this->form_fields[$field['id']] = $field;
        }
    }


    final public function get_form_fields()
    {
        if (count($this->form_fields) <= 0) {
            $this->init_form_fields();
        }

        return $this->form_fields;
    }

    /**
     * <h2>Generate Settings HTML.</h2>
     * <p>Overrides the base function and does nothing.</p>
     *
     * @override
     * @return void
     * @since 3.0.0
     */
    final public function generate_settings_html($form_fields = [], $echo = true)
    {
    }

    /**
     * <h2>Output the admin options table.</h2>
     * <p>Overrides the base function and renders an HTML-page.</p>
     *
     * @override
     * @return void
     * @since 3.0.0
     */
    public function admin_options()
    {
        echo '<img src="' . ecp_img_url('ecommpay.svg') . '" alt="" class="ecp_logo right">';
        echo '<h2>' . esc_html($this->get_method_title());
        wc_back_link(__('Return to payments', 'woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout'));
        echo '</h2>';
        Ecp_Form::get_instance()->output($this->id);
    }
}
