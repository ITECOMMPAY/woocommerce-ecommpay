jQuery(document).ready(function () {
	const common = window.ECP.common

	const PM_EMBEDDED_SUBMIT = 'epframe.embedded_mode.submit'
	const PM_EMBEDDED_CHECK_VALIDATION = 'epframe.embedded_mode.check_validation'

	common.showWidget = showIFrame
	common.success = success
	common.startEmbeddedIframeFlow = startEmbeddedIframeFlow

	function success(result) {
		switch (result.result) {
			case 'success':
				ECP.order_id = result.order_id
				if (window.ECP.isEmbeddedMode && common.isEcommpayCardPayment()) {
					processOrderWithEmbeddedIframe(result)
					break
				}
				if (result.optionsJson) {
					try {
						const options = JSON.parse(result.optionsJson)
						if (options.frame_mode === 'popup') {
							showPopup(options)
							break
						}
					} catch (e) {
						console.error('Failed to parse optionsJson:', e)
						common.hideOverlayLoader()
						common.show_error({}, 'Invalid response')
						break
					}
				}
				common.redirect(result.redirect)
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

	function runWidget(configObj) {
		configObj.onExit = common.back
		configObj.onDestroy = common.back
		EPayWidget.run(configObj, 'POST')
	}

	function showPopup(configObj) {
		runWidget(configObj)
	}

	function showIFrame(configObj) {
		configObj.onLoaded = common.onLoaded
		configObj.onEmbeddedModeCheckValidationResponse = onEmbeddedModeCheckValidationResponse
		configObj.onEnterKeyPressed = onEnterKeyPressed
		configObj.onPaymentSent = common.showOverlayLoader
		configObj.onSubmitClarificationForm = common.showOverlayLoader
		configObj.onShowClarificationPage = common.onShowClarificationPage
		configObj.onEmbeddedModeRedirect3dsParentPage = onEmbeddedModeRedirect3dsParentPage
		configObj.onPaymentSuccess = redirectOnSuccess
		configObj.onCardVerifySuccess = redirectOnSuccess
		configObj.onPaymentFail = redirectOnFail
		configObj.onCardVerifyFail = redirectOnFail

		common.loader().show()
		common.scroll_to_notices()
		runWidget(configObj)
	}

	function onEmbeddedModeCheckValidationResponse(data) {
		if (data && typeof data === 'object' && Object.keys(data).length > 0) {
			const errors = []
			jQuery.each(data, function (key, value) {
				errors.push(value)
			})
			const errorsUnique = [...new Set(errors)]
			const $errorDiv = jQuery('<div class="woocommerce-error"></div>')
			jQuery.each(errorsUnique, function (key, value) {
				$errorDiv
					.append(jQuery('<span>').text(value))
					.append('<br>')
			})
			common.submit_error($errorDiv[0].outerHTML)
			window.ECP.isPaymentRunning = false
		} else {
			common.clear_error()
			if (window.ECP.clarificationRunning) {
				postSubmitClarification()
			} else {
				createWoocommerceOrder()
			}
		}
	}

	function createWoocommerceOrder() {
		const extraData = [{ name: 'payment_id', value: window.ECP.paramsForEmbeddedPP.payment_id }]
		if (ECP.order_id > 0) {
			extraData.push({ name: 'order_id', value: ECP.order_id })
		}
		common.createWoocommerceOrder({
			extraData: extraData,
			onSuccess: success,
			onError: function (jqXHR, textStatus, errorThrown) {
				window.ECP.isPaymentRunning = false
				common.submit_error('<div class="woocommerce-error">' + errorThrown + '</div>')
			},
		})
	}

	function processOrderWithEmbeddedIframe(result) {
		let parsedOptions
		try {
			parsedOptions = JSON.parse(result.optionsJson)
		} catch (e) {
			console.error('Failed to parse optionsJson in processOrderWithEmbeddedIframe:', e)
			common.hideOverlayLoader()
			common.show_error({}, 'Invalid response')
			return
		}

		window.ECP.redirectResult = parsedOptions
		window.ECP.redirectResult.frame_mode = 'iframe'
		window.ECP.redirectResult.payment_id = window.ECP.paramsForEmbeddedPP.payment_id
		const billingFields = [
			'billing_address',
			'billing_city',
			'billing_country',
			'billing_postal',
			'customer_first_name',
			'customer_last_name',
			'customer_phone',
			'customer_zip',
			'customer_address',
			'customer_city',
			'customer_country',
			'customer_email',
		]
		const fieldsObject = {}
		Object.keys(window.ECP.redirectResult).forEach((key) => {
			let name = key
			if (billingFields.includes(key)) {
				name = 'BillingInfo[' + name + ']'
			}
			fieldsObject[name] = window.ECP.redirectResult[key]
		})

		postSubmit(fieldsObject)
	}

	function postSubmit(fields) {
		common.postSafeMessage({ message: PM_EMBEDDED_SUBMIT, fields: fields })
	}

	function postSubmitClarification() {
		postSubmit({})
	}

	function postCheckValidation() {
		common.postSafeMessage({ message: PM_EMBEDDED_CHECK_VALIDATION })
	}

	function onEnterKeyPressed() {
		jQuery('#place_order').click()
	}

	function redirectOnSuccess() {
		if (window.ECP.redirectResult.redirect_success_enabled) {
			common.hideOverlayLoader()
			window.location.replace(window.ECP.redirectResult.redirect_success_url)
		}
	}

	function redirectOnFail() {
		common.hideOverlayLoader()
		if (window.ECP.redirectResult.redirect_fail_enabled) {
			window.location.replace(window.ECP.redirectResult.redirect_fail_url)
		} else {
			common.resetEmbeddedIframe()
		}
	}

	function onEmbeddedModeRedirect3dsParentPage(data) {
		const form = document.createElement('form')
		form.setAttribute('method', data.method)
		form.setAttribute('action', data.url)
		form.setAttribute('style', 'display:none;')
		form.setAttribute('name', '3dsForm')
		for (const [key, value] of Object.entries(data.body)) {
			const input = document.createElement('input')
			input.name = key
			input.value = value
			form.appendChild(input)
		}
		document.body.appendChild(form)
		form.submit()
	}

	function startEmbeddedIframeFlow() {
		window.ECP.isPaymentRunning = true
		if (ECP.order_id) {
			postCheckValidation()
			return
		}

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
					postCheckValidation()
				} else {
					window.location.reload()
				}
			},
			error: function (jqXHR, textStatus) {
				common.submit_error('<div class="woocommerce-error">' + textStatus + '</div>')
			},
		})
	}
})
