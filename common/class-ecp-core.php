<?php

use common\modules\EcpModuleAuth;
use common\modules\EcpModuleCancel;
use common\modules\EcpModuleCapture;

final class Ecp_Core extends WC_Settings_API
{
    /**
     * <h2>Identifier for interface type.</h2>
     *
     * @var int
     * @since 2.0.0
     */
    const INTERFACE_TYPE = 18;

    /**
     * <h2>Plugin version.</h2>
     * <p>Sent into headers for open PP and Gate 2025 API.</p>
     *
     * @var string
     * @since 2.0.0
     */
    const WC_ECP_VERSION = '4.0.0';


	/**
     * @var ?Ecp_Core
     */
    private static $instance;

    /**
     * @var ?Ecp_Form
     */
    private $form;

    /**
     * @var ?Ecp_Gateway[]
     */
	private ?array $methods;

    /**
     * @var string
     */
    public $id = 'ecommpay';

	private static array $classes = [
        Ecp_Gateway_Card::class,
        Ecp_Gateway_Applepay::class,
        Ecp_Gateway_Googlepay::class,
        Ecp_Gateway_DirectDebit_BACS::class,
        Ecp_Gateway_DirectDebit_SEPA::class,
        Ecp_Gateway_Banks::class,
        Ecp_Gateway_PayPal::class,
        Ecp_Gateway_PayPal_PayLater::class,
        Ecp_Gateway_Sofort::class,
        Ecp_Gateway_Ideal::class,
        Ecp_Gateway_Klarna::class,
        Ecp_Gateway_Blik::class,
        Ecp_Gateway_Giropay::class,
        Ecp_Gateway_Brazil_Online_Banks::class,
        Ecp_Gateway_More::class,
    ];


	/**
     * <h2>Adds action links inside the plugin overview.</h2>
     *
     * @return array <p>Action link list.</p>
     * @since 2.0.0
     */
	public static function add_action_links( $links ): array {
		return array_merge( [
			'<a href="' . ecp_settings_page_url() . '">' . __( 'Settings', 'woo-ecommpay' ) . '</a>',
        ], $links);
    }


	public static function get_instance(): ?Ecp_Core {
        if (!self::$instance) {
            self::$instance = new Ecp_Core();
        }

        return self::$instance;
    }

    /**
     * <h2>Returns the ECOMMPAY external interface type.</h2>
     *
     * @return array
     * @since 1.0.0
     */
	public function get_interface_type(): array {
        return [
            'id' => self::INTERFACE_TYPE,
        ];
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
	    EcpModuleAuth::get_instance();
	    EcpModuleCapture::get_instance();
	    EcpModuleCancel::get_instance();
        Ecp_Gateway_API_Protocol::get_instance();

        $this->set_payment_methods();

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

    private function set_payment_methods(): void
    {
        $this->methods = [
            Ecp_Gateway_Settings_Card::ID => Ecp_Gateway_Card::get_instance(),
            Ecp_Gateway_Settings_PayPal::ID => Ecp_Gateway_PayPal::get_instance(),
            Ecp_Gateway_Settings_PayPal_PayLater::ID => Ecp_Gateway_PayPal_PayLater::get_instance(),
            Ecp_Gateway_Settings_Klarna::ID => Ecp_Gateway_Klarna::get_instance(),
            Ecp_Gateway_Settings_Sofort::ID => Ecp_Gateway_Sofort::get_instance(),
            Ecp_Gateway_Settings_Blik::ID => Ecp_Gateway_Blik::get_instance(),
            Ecp_Gateway_Settings_Ideal::ID => Ecp_Gateway_Ideal::get_instance(),
            Ecp_Gateway_Settings_Banks::ID => Ecp_Gateway_Banks::get_instance(),
            Ecp_Gateway_Settings_Giropay::ID => Ecp_Gateway_Giropay::get_instance(),
            Ecp_Gateway_Settings_Brazil_Online_Banks::ID => Ecp_Gateway_Brazil_Online_Banks::get_instance(),
            Ecp_Gateway_Settings_Googlepay::ID => Ecp_Gateway_Googlepay::get_instance(),
            Ecp_Gateway_Settings_Applepay::ID => Ecp_Gateway_Applepay::get_instance(),
            Ecp_Gateway_Settings_DirectDebit_BACS::ID => Ecp_Gateway_DirectDebit_BACS::get_instance(),
            Ecp_Gateway_Settings_DirectDebit_SEPA::ID => Ecp_Gateway_DirectDebit_SEPA::get_instance(),
            Ecp_Gateway_Settings_More::ID => Ecp_Gateway_More::get_instance(),
        ];
    }

    /**
     * <h2>Returns the merchant project identifier.</h2>
     *
     * @return int
     * @since 3.0.0
     */
	public function get_project_id(): int {
		return (int) ecommpay()->get_general_option( Ecp_Gateway_Settings_General::OPTION_PROJECT_ID );
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
        echo '<h2>ECOMMPAY';
        wc_back_link(__('Return to payments', 'woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout'));
        echo '</h2>';
	    $this->settings()->output();
    }

    /**
     * <h2>Show plugin changes. Code adapted from W3 Total Cache.</h2>
     *
     * @return void
     * @since 3.0.0
     */
    public function in_plugin_update_message($args)
    {
        $upgrade_notice = '';
        echo wp_kses_post($upgrade_notice);
    }

    public function settings()
    {
        if (empty ($this->form)) {
            $this->form = Ecp_Form::get_instance();
        }

        return $this->form;
    }

	public function get_payment_methods(): ?array {
        if (empty ($this->methods)) {
            $this->set_payment_methods();
        }
        return $this->methods;
    }

	public function get_payment_classnames(): array {
        return self::$classes;
    }

    public function get_option($key, $empty_value = [])
    {
        if (empty ($this->settings)) {
            $this->init_settings();
        }

        // If there are no settings defined, use defaults.
        if (!is_array($this->settings)) {
            $this->settings = $this->settings()->get_default_settings();
        }

        return array_key_exists($key, $this->settings)
            ? $this->settings[$key]
            : $empty_value;
    }

    /**
     * @inheritDoc
     * @override
     * @return bool
     * @since 3.0.0
     */
	public function update_option( $key, $value = '' ): bool {
        if (empty ($this->settings)) {
            $this->init_settings();
        }

        $this->settings[$key] = $value;

        return update_option(
            $this->get_option_key(),
            apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings),
            'yes'
        );
    }

	public function update_pm_option( $payment_method, $key, $value = '' ): bool {
        $settings = $this->get_option($payment_method);
        $settings[$key] = $value;
        return $this->update_option($payment_method, $settings);
    }

    public function get_pm_option($payment_method, $key, $default = null)
    {
        $settings = $this->get_option($payment_method);

        // Get option default if unset.
        if (!isset ($settings[$key])) {
            $form_fields = $this->get_form_fields();
            $settings[$key] = isset ($form_fields[$key]) ? $this->get_field_default($form_fields[$key]) : '';
        }
		
	    return ! is_null( $default ) && in_array( $settings[ $key ], [ '', 'no' ] ) ? $default : $settings[ $key ];
    }

    public function get_general_option($key, $default = null)
    {
	    return $this->get_pm_option( Ecp_Gateway_Settings_General::ID, $key, $default );
    }

    /**
     * @inheritDoc
     * @override
     * @return string
     * @since 3.0.0
     */
	public function get_option_key(): string {
        return $this->plugin_id . $this->id . '_settings';
    }

    /**
     * <h2>Returns the redeclaration of the class name for the object type.</h2>
     *
     * @param string $classname <p>Base class name.</p>
     * @param string $type <p>Object type.</p>
     * @return string <p>Wrapped or base class name.</p>
     * @since 3.0.0
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

    /**
     * <h2>Setup plugin installation hooks.</h2>
     *
     * @return void
     * @since 3.0.0
     */
    private function installation_hooks()
    {
        add_action('wp_ajax_ecommpay_run_data_upgrader', [Ecp_Gateway_Install::get_instance(), 'ajax_run_upgrade']);
        add_action(
            'in_plugin_update_message-woocommerce-ecommpay/woocommerce-ecommpay.php',
            [$this, 'in_plugin_update_message']
        );
    }


}