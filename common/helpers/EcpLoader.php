<?php

namespace common\helpers;

defined( 'ABSPATH' ) || exit;


class EcpLoader {

	public function append_loader_on_page() {
		wp_enqueue_script(
			'ecommpay_loader_js',
			ecp_js_url( 'ecommpay-loader.js' ),
			array(),
			'1.0.0',
			true
		);

		wp_enqueue_style(
			'ecommpay_loader_css',
			ecp_css_url( 'loader.css' ),
			array(),
			'1.0.0'
		);
	}
}
