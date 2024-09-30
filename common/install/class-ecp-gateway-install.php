<?php

/**
 * <h2>Plugin installer and updater.</h2>
 *
 * @class    Ecp_Gateway_Install
 * @version  2.0.0
 * @package  Ecp_Gateway/Install
 * @category Class
 */
class Ecp_Gateway_Install {
	const UPDATE_NONCE = 'woocommerce-ecp-gateway-run-upgrade-nonce';
	const FIELD_NONCE = 'nonce';
	const SETTINGS_NAME = 'woocommerce_ecommpay_settings';
	const VERSION_NAME = 'woocommerce_ecommpay_version';
	const MAINTENANCE = 'woocommerce_ecommpay_maintenance';


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
		'3.3.1' => __DIR__ . '/migrations/upgrade_orders_to_version_3.3.1.php'
	];


	/**
	 * <h2>Returns instance of the current object.</h2>
	 *
	 * @return static
	 * @since 2.0.0
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new static();
		}

		return self::$instance;
	}


	/**
	 * <h2>Returns the result of checking the previous installation.</h2>
	 *
	 * @return bool <p><b>TRUE</b> if this is the first install, or <b>FALSE</b> otherwise.</p>
	 * @since 2.0.0
	 */
	public function is_first_install() {
		return get_option( self::SETTINGS_NAME, false ) === false;
	}

	/**
	 * <h2>Runs on first install</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function install() {
		add_option( self::VERSION_NAME, Ecp_Core::WC_ECP_VERSION );
	}

	/**
	 * <h2>Shows an admin notice informing about required updates.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function show_update_warning() {
		if ( ! $this->is_update_required() ) {
			return;
		}

		ecp_get_view( ! $this->is_in_maintenance_mode() ? 'html-notice-update.php' : 'html-notice-upgrading.php' );
	}

	/**
	 * <h2>Returns the result of checking if the current version of the database is out of date.</h2>
	 *
	 * @return bool <p><b>TRUE</b> if update is required, or <b>FALSE</b> otherwise.</p>
	 * @since 2.0.3
	 */
	public function is_update_required() {
		$version = $this->get_version();
		foreach ( self::$updates as $new_version => $updater ) {
			if ( version_compare( $version, $new_version, '<' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * <h2>Returns the current plugin version stored in the database.</h2>
	 *
	 * @return string <p>The stored plugin version number.</p>
	 * @since 2.0.0
	 */
	private function get_version() {
		return get_option( self::VERSION_NAME, '' );
	}

	/**
	 * <h2>Returns the result of checking that maintenance mode is enabled.</h2>
	 *
	 * @return bool <p><b>TRUE</b> if the maintenance mode is enabled, or <b>FALSE</b> otherwise.</p>
	 * @since 2.0.0
	 */
	private function is_in_maintenance_mode() {
		return get_option( self::MAINTENANCE, false );
	}

	/**
	 * <h2>Asynchronous data upgrade action</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function ajax_run_upgrade() {
		$nonce = wc_get_post_data_by_key( self::FIELD_NONCE, null );

		if (
			! wp_verify_nonce( $nonce, self::UPDATE_NONCE )
			&& ! current_user_can( 'administrator' )
		) {
			echo json_encode(
				[
					'status'  => 'error',
					'message' => __( 'You are not authorized to perform this action', 'woo-ecommpay' )
				]
			);
			exit;
		}

		$this->update();

		echo json_encode( [ 'status' => 'success' ] );
		exit;
	}

	/**
	 * <h2>Iterates over updates and executes them.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function update() {
		// Don't lock up other requests while processing
		session_write_close();

		$this->enable_maintenance_mode();

		foreach ( self::$updates as $new_version => $updater ) {
			if ( version_compare( $this->get_version(), $new_version, '<' ) ) {
				include( $updater );
			}
		}

		$this->update_version( Ecp_Core::WC_ECP_VERSION );

		$this->disable_maintenance_mode();
	}

	/**
	 * <h2>Enables maintenance mode.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	private function enable_maintenance_mode() {
		add_option( self::MAINTENANCE, true );
	}

	/**
	 * <h2>Updates the version.</h2>
	 *
	 * @param string $version [optional] <p>The version number to update to.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	private function update_version( $version = null ) {
		delete_option( self::VERSION_NAME );
		add_option(
			self::VERSION_NAME,
			$version === null ? Ecp_Core::WC_ECP_VERSION : $version
		);
	}

	/**
	 * <h2>Disables maintenance mode.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	private function disable_maintenance_mode() {
		delete_option( self::MAINTENANCE );
	}

	/**
	 * <h2>Returns a cryptographic token tied.</h2>
	 *
	 * @return string <p>Cryptographic token.</p>
	 * @since 2.0.0
	 */
	public function create_run_upgrade_nonce() {
		return wp_create_nonce( self::UPDATE_NONCE );
	}


}
