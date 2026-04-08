/**
 * Shared checkout utilities for embedded payment widget modes.
 * Loaded before checkout-legacy.js and checkout.js.
 *
 * Version-specific scripts must register their implementations into
 * window.ECP.common.loadEmbeddedIframe, window.ECP.common.success, and
 * window.ECP.common.startEmbeddedIframeFlow during their own ready() callback.
 * This is safe because those functions are only invoked asynchronously (AJAX
 * callbacks or user interaction), which happens after both ready() callbacks
 * have already run.
 */

jQuery(document).ready(function () {
	const ACTIONS = {
		PROCESS: 'ecommpay_process',
		BREAK: 'ecommpay_break',
		GET_PAYMENT_FORM: 'get_data_for_payment_form',
		CHECK_CART_AMOUNT: 'check_cart_amount',
	}

	const SELECTORS = {
		PLACE_ORDER: '#place_order',
	}

	jQuery(document.body).append(
		'<div id="ecommpay-overlay-loader" class="blockUI blockOverlay ecommpay-loader-overlay" style="display: none;"></div>'
	)

	// Shared state
	window.ECP.isEmbeddedMode = false
	let targetForm = jQuery('form.checkout')
	let loader = jQuery('#ecommpay-loader')
	window.ECP.isPaymentRunning = false
	window.ECP.paramsForEmbeddedPP = false
	window.ECP.clarificationRunning = false
	window.ECP.redirectResult = false

	let pendingEmbeddedRequest = null

	jQuery('body').on('click', SELECTORS.PLACE_ORDER, function (e) {
		if (!isEcommpayPayment()) {
			return
		}
		targetForm = jQuery(e.target.form)
		e.preventDefault()
		if (window.ECP.isEmbeddedMode && isEcommpayCardPayment()) {
			window.ECP.common.startEmbeddedIframeFlow()
			return
		}
		showOverlayLoader()

		const extraData = ECP.order_id > 0 ? [{ name: 'order_id', value: ECP.order_id }] : []

		createWoocommerceOrder({
			extraData: extraData,
			onSuccess: window.ECP.common.success,
			onError: function (jqXHR, textStatus, errorThrown) {
				hideOverlayLoader()
				submit_error('<div class="woocommerce-error">' + errorThrown + '</div>')
			},
		})
	})

	// Update embedded iframe when cart changes
	jQuery(document.body).on('updated_checkout', function () {
		resetEmbeddedIframe()
	})

	getParamsForCreateEmbeddedPP()

	// ==================== Shared Functions ====================

	function resetEmbeddedIframe() {
		window.ECP.paramsForEmbeddedPP = false
		jQuery('#ecommpay-iframe-embedded').height(0).empty()
		const $loaderEmbedded = jQuery('#ecommpay-loader-embedded')
		if ($loaderEmbedded.length) {
			$loaderEmbedded.show()
		}
		getParamsForCreateEmbeddedPP()
	}

	function isEcommpayPayment() {
		return jQuery("input[name='payment_method']:checked").val().slice(0, 8) === 'ecommpay'
	}

	function isEcommpayCardPayment() {
		return jQuery("input[name='payment_method']:checked").val() === 'ecommpay-card'
	}

	function submit_error(error_message) {
		jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove()
		targetForm.prepend(
			'<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>'
		)
		targetForm.removeClass('processing').unblock()
		targetForm.find('.input-text, select, input:checkbox').trigger('validate').blur()
		scroll_to_notices()
		jQuery(document.body).trigger('checkout_error')
	}

	function clear_error() {
		jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove()
	}

	function scroll_to_notices() {
		let scrollElement = jQuery('.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout')

		if (!scrollElement.length) {
			scrollElement = loader
		}

		if (scrollElement.length) {
			scrollElement[0].scrollIntoView({ behavior: 'smooth' })
		}
	}

	function show_error(result, message) {
		console.error(message)

		if (true === result.reload) {
			window.location.reload()
			return
		}

		if (true === result.refresh) {
			jQuery(document.body).trigger('update_checkout')
		}

		if (result.messages) {
			submit_error(result.messages)
		} else {
			submit_error('<div class="woocommerce-error">' + wc_checkout_params.i18n_checkout_error + '</div>')
		}
	}

	function back() {
		const href = window.location.href.split('?')
		const query_string = href[1] === undefined ? '' : href[1]
		const data = []

		data.push({
			name: 'action',
			value: ACTIONS.BREAK,
		})

		if (ECP.order_id > 0) {
			data.push({
				name: 'order_id',
				value: ECP.order_id,
			})
		}

		jQuery.ajax({
			type: 'POST',
			url: ECP.ajax_url + '?' + query_string,
			data: data,
			dataType: 'json',
			success: function (result) {
				window.location.replace(result.redirect)
			},
			error: function (jqXHR, textStatus, errorThrown) {
				submit_error('<div class="woocommerce-error">' + errorThrown + '</div>')
			},
		})
	}

	function redirect(url) {
		window.location.href = url
	}

	/**
	 * Shared AJAX helper to POST the order process request.
	 * Shared by both embedded widget variants; callers supply mode-specific callbacks.
	 *
	 * @param {object} config
	 * @param {Array}    config.extraData  - Additional {name,value} pairs appended to the form data.
	 * @param {Function} config.onSuccess  - Called with the raw result object on HTTP 200.
	 * @param {Function} config.onError    - Called with (jqXHR, textStatus, errorThrown) on failure.
	 */
	function createWoocommerceOrder({ extraData = [], onSuccess, onError }) {
		const query_string = window.location.href.split('?')[1] || ''
		const data = targetForm.serializeArray()
		data.push({ name: 'action', value: ACTIONS.PROCESS })
		extraData.forEach(function (field) {
			data.push(field)
		})

		jQuery.ajax({
			type: 'POST',
			url: ECP.ajax_url + '?' + query_string,
			data: data,
			dataType: 'json',
			success: onSuccess,
			error: onError,
		})
	}

	function getParamsForCreateEmbeddedPP() {
    // Skip on pages without a checkout form (e.g. order-received) where is_checkout() is still true.
    if (!jQuery(SELECTORS.PLACE_ORDER).length) {
			return
		}
		const href = window.location.href.split('?')
		const data = [
			{
				name: 'action',
				value: ACTIONS.GET_PAYMENT_FORM,
			},
		]
		const query_string = href[1] === undefined ? '' : href[1]

		if (ECP.order_id > 0) {
			data.push({
				name: 'order_id',
				value: ECP.order_id,
			})
		}

		if (pendingEmbeddedRequest) {
			pendingEmbeddedRequest.abort()
		}

		pendingEmbeddedRequest = jQuery.ajax({
			type: 'POST',
			url: ECP.ajax_url + '?' + query_string,
			data: data,
			dataType: 'json',
			success: function (result) {
				pendingEmbeddedRequest = null
				window.ECP.paramsForEmbeddedPP = result
				window.ECP.common.loadEmbeddedIframe()
			},
			error: function (jqXHR, textStatus, errorThrown) {
				pendingEmbeddedRequest = null
				if (textStatus === 'abort') {
					return
				}
				submit_error('<div class="woocommerce-error">' + errorThrown + '</div>')
			},
		})
	}

	function showOverlayLoader() {
		jQuery('#ecommpay-overlay-loader').show()
	}

	function hideOverlayLoader() {
		jQuery('#ecommpay-overlay-loader').hide()
	}

	function onLoaded() {
		loader.hide()
		jQuery('#ecommpay-iframe-embedded').height('auto')
	}

	function onShowClarificationPage() {
		window.ECP.clarificationRunning = true
		hideOverlayLoader()
	}

	/**
	 * Shared loadEmbeddedIframe logic — performs the common setup
	 * (empty div, set embedded mode, configure loader, bind payment-method
	 * change handler) and then delegates to the version-specific showWidget().
	 */
	function loadEmbeddedIframe() {
		const embeddedIframeDiv = jQuery('#ecommpay-iframe-embedded')
		if (embeddedIframeDiv.length === 1 && window.ECP.paramsForEmbeddedPP) {
			embeddedIframeDiv.empty()
			window.ECP.isEmbeddedMode = true
			window.ECP.common.setLoader(jQuery('#ecommpay-loader-embedded'))
			window.ECP.common.showWidget(window.ECP.paramsForEmbeddedPP)

			jQuery('input[name="payment_method"]').off('change').on('change', function () {
				if (isEcommpayCardPayment()) {
					jQuery(window).trigger('resize')
				}
			})
		}
	}

	function postSafeMessage(payload) {
		window.postMessage(JSON.stringify(Object.assign({ from_another_domain: true }, payload)), window.location.origin)
	}

	// Export shared functions; version-specific scripts extend this object with
	// showWidget / success / startEmbeddedIframeFlow in their own ready().
	window.ECP.common = {
		ACTIONS,
		postSafeMessage,
		targetForm: () => targetForm,
		loader: () => loader,
		setLoader: (el) => { loader = el },
		resetEmbeddedIframe,
		isEcommpayCardPayment,
		submit_error,
		clear_error,
		scroll_to_notices,
		show_error,
		back,
		redirect,
		showOverlayLoader,
		hideOverlayLoader,
		onLoaded,
		onShowClarificationPage,
		loadEmbeddedIframe,
		createWoocommerceOrder,
		// Registered by a version-specific script:
		showWidget: null,
		success: null,
		startEmbeddedIframeFlow: null,
	}
})
