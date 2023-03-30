<?php

/**
 * <h2>Plugin installer and updater.</h2>
 *
 * @class    Ecp_Gateway_Install
 * @version  2.0.0
 * @package  Ecp_Gateway/Install
 * @category Class
 */
class Ecp_Gateway_Install
{
    const UPDATE_NONCE = 'woocommerce-ecp-gateway-run-upgrade-nonce';
    const FIELD_NONCE = 'nonce';
    const SETTINGS_NAME = 'woocommerce_ecommpay_settings';
    const VERSION_NAME = 'woocommerce_ecommpay_version';
    const MAINTENANCE = 'woocommerce_ecommpay_maintenance';

    // region Properties

    /**
     * <h2>Instance of current object.</h2>
     *
     * @var ?static
     * @since 2.0.0
     */
    private static $instance;

    /**
     * <h2>Contains version numbers and the path to the update files.</h2>
     *
     * @var array
     * @since 2.0.0
     */
    private static $updates = [
        '3.0.0' => __DIR__ . '/migrations/upgrade_settings_to_version_3.php',
    ];

    // endregion

    // region Static methods

    /**
     * <h2>Returns instance of the current object.</h2>
     *
     * @since 2.0.0
     * @return static
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    // endregion

    /**
     * <h2>Returns the result of checking the previous installation.</h2>
     *
     * @since 2.0.0
     * @return bool <p><b>TRUE</b> if this is the first install, or <b>FALSE</b> otherwise.</p>
     */
    public function is_first_install()
    {
        return get_option(self::SETTINGS_NAME, false) === false;
    }

    /**
     * <h2>Runs on first install</h2>
     *
     * @since 2.0.0
     * @return void
     */
    public function install()
    {
        add_option(self::VERSION_NAME, Ecp_Core::WC_ECP_VERSION);
    }

    /**
     * <h2>Iterates over updates and executes them.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    public function update()
    {
        // Don't lock up other requests while processing
        session_write_close();

        $this->enable_maintenance_mode();

        foreach (self::$updates as $version => $updater) {
            if ($this->is_update_required()) {
                include($updater);
                $this->update_version($version);
            }
        }

        $this->update_version(Ecp_Core::WC_ECP_VERSION);

        $this->disable_maintenance_mode();
    }

    /**
     * <h2>Shows an admin notice informing about required updates.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    public function show_update_warning()
    {
        if (!$this->is_update_required()) {
            return;
        }

        ecp_get_view(!$this->is_in_maintenance_mode() ? 'html-notice-update.php' : 'html-notice-upgrading.php');
    }

    /**
     * <h2>Asynchronous data upgrade action</h2>
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_run_upgrade()
    {
        $nonce = wc_get_post_data_by_key(self::FIELD_NONCE, null);

        if (
            !wp_verify_nonce($nonce, self::UPDATE_NONCE)
            && !current_user_can('administrator')
        ) {
            echo json_encode(
                [
                    'status' => 'error',
                    'message' => __('You are not authorized to perform this action', 'woo-ecommpay')
                ]
            );
            exit;
        }

        $this->update();

        echo json_encode(['status' => 'success']);
        exit;
    }

    /**
     * <h2>Returns a cryptographic token tied.</h2>
     *
     * @since 2.0.0
     * @return string <p>Cryptographic token.</p>
     */
    public function create_run_upgrade_nonce()
    {
        return wp_create_nonce(self::UPDATE_NONCE);
    }

    /**
     * <h2>Returns the result of checking if the current version of the database is out of date.</h2>
     *
     * @since 2.0.3
     * @return bool <p><b>TRUE</b> if update is required, or <b>FALSE</b> otherwise.</p>
     */
    public function is_update_required()
    {
        $version = $this->get_version();

        return version_compare($version, Ecp_Core::WC_ECP_VERSION, '<');
    }

    // region Private methods.

    /**
     * <h2>Updates the version.</h2>
     *
     * @param string $version [optional] <p>The version number to update to.</p>
     * @since 2.0.0
     * @return void
     */
    private function update_version($version = null)
    {
        delete_option(self::VERSION_NAME);
        add_option(
            self::VERSION_NAME,
            $version === null ? Ecp_Core::WC_ECP_VERSION : $version
        );
    }

    /**
     * <h2>Returns the result of checking that maintenance mode is enabled.</h2>
     *
     * @since 2.0.0
     * @return bool <p><b>TRUE</b> if the maintenance mode is enabled, or <b>FALSE</b> otherwise.</p>
     */
    private function is_in_maintenance_mode()
    {
        return get_option(self::MAINTENANCE, false);
    }

    /**
     * <h2>Enables maintenance mode.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    private function enable_maintenance_mode()
    {
        add_option(self::MAINTENANCE, true);
    }

    /**
     * <h2>Disables maintenance mode.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    private function disable_maintenance_mode()
    {
        delete_option(self::MAINTENANCE);
    }

    /**
     * <h2>Returns the current plugin version stored in the database.</h2>
     *
     * @since 2.0.0
     * @return string <p>The stored plugin version number.</p>
     */
    private function get_version()
    {
        return get_option(self::VERSION_NAME, '');
    }

    // endregion
}
