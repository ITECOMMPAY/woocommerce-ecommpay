jQuery(document).ready(function () {
	const common = window.ECP.common

	// Register version-specific functions. Called asynchronously after both
	// ready() callbacks have run, so common is already initialised.
	common.showWidget = showEmbeddedWidget
	common.success = success
	common.startEmbeddedIframeFlow = startEmbeddedIframeFlow

	window.ECP.isEmbeddedMode = true
	window.ECP.widgetInstance = null

	function safeReject(reject) {
		if (typeof reject === 'function') {
			reject()
		}
	}

	function handleAjaxError(reject, jqXHR, textStatus, errorThrown) {
		safeReject(reject)
		common.submit_error('<div class="woocommerce-error">' + errorThrown + '</div>')
		window.ECP.isPaymentRunning = false
	}

	function success(result) {
		switch (result.result) {
			case 'success':
				ECP.order_id = result.order_id
				if (result.redirect) {
					window.location.href = result.redirect
				}
				break
			case 'failure':
				common.hideOverlayLoader()
				common.show_error(result, 'Result failure')
				break
			default:
				common.hideOverlayLoader()
				common.show_error(result, 'Invalid response')
		}
	}

	function showEmbeddedWidget(configObj) {
		configObj.onLoaded = common.onLoaded
		configObj.onValidationError = onValidationError
		configObj.onCheckSubmit = onCheckSubmit
		configObj.onPaymentSuccess = onPaymentSuccess
		configObj.onPaymentFail = onPaymentFail
		configObj.onShowLoader = common.showOverlayLoader
		configObj.onHideLoader = common.hideOverlayLoader

		common.loader().show()
		common.scroll_to_notices()

		window.ECP.widgetInstance = EPayWidget.runEmbedded(configObj)
	}

	// Embedded widget handlers

	function onValidationError(errors) {
		if (errors && Object.keys(errors).length > 0) {
			const errorMessages = []
			jQuery.each(errors, function (field, message) {
				errorMessages.push(message)
			})

			const uniqueErrors = [...new Set(errorMessages)]
			const $errorDiv = jQuery('<div class="woocommerce-error"></div>')
			jQuery.each(uniqueErrors, function (key, value) {
				$errorDiv
					.append(jQuery('<span>').text(value))
					.append('<br>')
			})

			common.submit_error($errorDiv[0].outerHTML)
			window.ECP.isPaymentRunning = false
		}
	}

	function onCheckSubmit(_data, resolve, reject) {
		window.ECP.isPaymentRunning = true

		common.clear_error()

		// If order not yet created, check cart amount first.
		if (!ECP.order_id) {
			const data = [
				{ name: 'action', value: common.ACTIONS.CHECK_CART_AMOUNT },
				{ name: 'amount', value: window.ECP.paramsForEmbeddedPP.payment_amount },
			]

			jQuery.ajax({
				type: 'POST',
				url: ECP.ajax_url,
				data: data,
				dataType: 'json',
				success: function (result) {
					if (result.amount_is_equal) {
						createWoocommerceOrder(resolve, reject)
					} else {
						window.location.reload()
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					handleAjaxError(reject, jqXHR, textStatus, errorThrown)
				},
			})
		} else {
			getOrderParametersAndResolve(resolve, reject)
		}
	}

	function createWoocommerceOrder(resolve, reject) {
		const extraData = [{ name: 'payment_id', value: window.ECP.paramsForEmbeddedPP.payment_id }]
		if (ECP.order_id > 0) {
			extraData.push({ name: 'order_id', value: ECP.order_id })
		}

		common.createWoocommerceOrder({
			extraData: extraData,
			onSuccess: function (result) {
				if (result.result === 'success' && result.order_id) {
					ECP.order_id = result.order_id
					resolveCheckSubmitWithParameters(resolve, reject, result)
				} else {
					console.error('[ECP] createWoocommerceOrder: unexpected result', result)
					safeReject(reject)
					common.show_error(result, 'Order creation failed')
					window.ECP.isPaymentRunning = false
				}
			},
			onError: function (jqXHR, textStatus, errorThrown) {
				handleAjaxError(reject, jqXHR, textStatus, errorThrown)
			},
		})
	}

	function getOrderParametersAndResolve(resolve, reject) {
		common.createWoocommerceOrder({
			extraData: [{ name: 'order_id', value: ECP.order_id }],
			onSuccess: function (result) {
				if (result.result === 'success' && result.optionsJson) {
					resolveCheckSubmitWithParameters(resolve, reject, result)
				} else {
					console.error('[ECP] getOrderParameters: missing optionsJson', result)
					safeReject(reject)
					window.ECP.isPaymentRunning = false
				}
			},
			onError: function (jqXHR, textStatus, errorThrown) {
				handleAjaxError(reject, jqXHR, textStatus, errorThrown)
			},
		})
	}

	function buildResolveParams(options) {
		const fields = [
			'redirect_success_url',
			'customer_first_name',
			'customer_last_name',
			'customer_phone',
			'customer_zip',
			'customer_address',
			'customer_city',
			'customer_country',
			'customer_email',
			'billing_address',
			'billing_city',
			'billing_country',
			'billing_postal',
			'billing_region',
		]
		const params = fields.reduce(function (acc, field) {
			acc[field] = options[field] || ''
			return acc
		}, {})

		if (options.avs_post_code && options.avs_street_address) {
			params.avs_post_code = options.avs_post_code
			params.avs_street_address = options.avs_street_address
		}

		return params
	}

	function resolveCheckSubmitWithParameters(resolve, reject, orderResult) {
		try {
			const options = JSON.parse(orderResult.optionsJson)
			window.ECP.redirectResult = options

			if (typeof resolve === 'function') {
				resolve(buildResolveParams(options))
			}
		} catch (e) {
			console.error('[ECP] resolveCheckSubmit: error parsing optionsJson', e)
			safeReject(reject)
			window.ECP.isPaymentRunning = false
		}
	}

	function onPaymentSuccess() {
		common.hideOverlayLoader()
		if (window.ECP.redirectResult && window.ECP.redirectResult.redirect_success_enabled) {
			window.location.replace(window.ECP.redirectResult.redirect_success_url)
		}
	}

	function onPaymentFail() {
		common.hideOverlayLoader()

		if (window.ECP.redirectResult && window.ECP.redirectResult.redirect_fail_enabled) {
			window.location.replace(window.ECP.redirectResult.redirect_fail_url)
		} else {
			common.resetEmbeddedIframe()
		}
	}

	function startEmbeddedIframeFlow() {
		window.ECP.isPaymentRunning = true

		if (window.ECP.widgetInstance && typeof window.ECP.widgetInstance.trySubmit === 'function') {
			window.ECP.widgetInstance.trySubmit()
		} else {
			console.error('[ECP] widget instance or trySubmit not available')
			window.ECP.isPaymentRunning = false
		}
	}
})
