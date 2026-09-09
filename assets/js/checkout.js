jQuery( document ).ready(
	function () {

		const ACTIONS = {
			PROCESS: 'ecommpay_process',
			BREAK: 'ecommpay_break',
			GET_PAYMENT_FORM: 'get_data_for_payment_form',
			CHECK_CART_AMOUNT: 'check_cart_amount',
		}

		const SELECTORS = {
			PLACE_ORDER: '#place_order',
		}

		// Shared state
		window.ECP.isEmbeddedMode       = false
		let targetForm                  = jQuery( 'form.checkout' )
		window.ECP.paramsForEmbeddedPP  = false
		window.ECP.redirectResult       = false

		let pendingEmbeddedRequest = null

		const embeddedLoader = {
			show: function () {
				jQuery('#ecommpay-loader-embedded').show()
			},
			hide: function () {
				jQuery('#ecommpay-loader-embedded').hide()
			}
		}

		jQuery( 'body' ).on(
			'click',
			SELECTORS.PLACE_ORDER,
			function (e) {
				if ( ! isEcommpayPayment()) {
					return
				}
				targetForm = jQuery( e.target.form )
				e.preventDefault()
				if (window.ECP.isEmbeddedMode && isEcommpayCardPayment()) {
					startEmbeddedIframeFlow()
					return
				}
				window.ECP.loader.show()

				const extraData = ECP.order_id > 0 ? [{ name : 'order_id', value : ECP.order_id }] : []

				createWoocommerceOrder(
					{
						extraData: extraData,
						onSuccess: success,
						onError: function (jqXHR, textStatus, errorThrown) {
							window.ECP.loader.hide()
							submit_error( '<div class="woocommerce-error">' + errorThrown + '</div>' )
						},
					}
				)
			}
		)

		// Update embedded iframe when cart changes
		jQuery( document.body ).on(
			'updated_checkout',
			function () {
				resetEmbeddedIframe()
			}
		)

		getParamsForCreateEmbeddedPP()

		function resetEmbeddedIframe() {
			window.ECP.paramsForEmbeddedPP = false
			embeddedLoader.show()
			getParamsForCreateEmbeddedPP()
		}

		function isEcommpayPayment() {
			return jQuery( "input[name='payment_method']:checked" ).val().slice( 0, 8 ) === 'ecommpay'
		}

		function isEcommpayCardPayment() {
			return jQuery( "input[name='payment_method']:checked" ).val() === 'ecommpay-card'
		}

		function submit_error(error_message) {
			jQuery( '.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message' ).remove()
			targetForm.prepend(
				'<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>'
			)
			targetForm.removeClass( 'processing' ).unblock()
			targetForm.find( '.input-text, select, input:checkbox' ).trigger( 'validate' ).blur()
			scroll_to_notices()
			jQuery( document.body ).trigger( 'checkout_error' )
		}

		function clear_error() {
			jQuery( '.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message' ).remove()
		}

		function scroll_to_notices() {
			let scrollElement = jQuery( '.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout' )

			if (scrollElement.length) {
				scrollElement[0].scrollIntoView( { behavior: 'smooth' } )
			}
		}

		function show_error(result, message) {
			console.error( message )

			if (true === result.reload) {
				window.location.reload()
				return
			}

			if (true === result.refresh) {
				jQuery( document.body ).trigger( 'update_checkout' )
			}

			if (result.messages) {
				submit_error( result.messages )
			} else {
				submit_error( '<div class="woocommerce-error">' + wc_checkout_params.i18n_checkout_error + '</div>' )
			}
		}

		function back() {
			const href         = window.location.href.split( '?' )
			const query_string = href[1] === undefined ? '' : href[1]
			const data         = []

			data.push(
				{
					name: 'action',
					value: ACTIONS.BREAK,
				}
			)

			if (ECP.order_id > 0) {
				data.push(
					{
						name: 'order_id',
						value: ECP.order_id,
					}
				)
			}

			jQuery.ajax(
				{
					type: 'POST',
					url: ECP.ajax_url + '?' + query_string,
					data: data,
					dataType: 'json',
					success: function (result) {
						window.location.replace( result.redirect )
					},
					error: function (jqXHR, textStatus, errorThrown) {
						submit_error( '<div class="woocommerce-error">' + errorThrown + '</div>' )
					},
				}
			)
		}

		/**
		 * AJAX helper to POST the order process request.
		 *
		 * @param {object} config
		 * @param {Array}    config.extraData  - Additional {name,value} pairs appended to the form data.
		 * @param {Function} config.onSuccess  - Called with the raw result object on HTTP 200.
		 * @param {Function} config.onError    - Called with (jqXHR, textStatus, errorThrown) on failure.
		 */
		function createWoocommerceOrder({ extraData = [], onSuccess, onError }) {
			const query_string = window.location.href.split( '?' )[1] || ''
			const data         = targetForm.serializeArray()
			data.push( { name: 'action', value: ACTIONS.PROCESS } )
			extraData.forEach(
				function (field) {
					data.push( field )
				}
			)

			jQuery.ajax(
				{
					type: 'POST',
					url: ECP.ajax_url + '?' + query_string,
					data: data,
					dataType: 'json',
					success: onSuccess,
					error: onError,
				}
			)
		}

		function getParamsForCreateEmbeddedPP() {
			// Skip on pages without a checkout form (e.g. order-received) where is_checkout() is still true.
			if ( ! jQuery( SELECTORS.PLACE_ORDER ).length) {
				return
			}
			const href         = window.location.href.split( '?' )
			const data         = [
			{
				name: 'action',
				value: ACTIONS.GET_PAYMENT_FORM,
			},
			]
			const query_string = href[1] === undefined ? '' : href[1]

			if (ECP.order_id > 0) {
				data.push(
					{
						name: 'order_id',
						value: ECP.order_id,
					}
				)
			}

			if (pendingEmbeddedRequest) {
				pendingEmbeddedRequest.abort()
			}

			pendingEmbeddedRequest = jQuery.ajax(
				{
					type: 'POST',
					url: ECP.ajax_url + '?' + query_string,
					data: data,
					dataType: 'json',
					success: function (result) {
						pendingEmbeddedRequest         = null
						window.ECP.paramsForEmbeddedPP = result
						loadEmbeddedIframe()
					},
					error: function (jqXHR, textStatus, errorThrown) {
						pendingEmbeddedRequest = null
						if (textStatus === 'abort') {
							return
						}
						submit_error( '<div class="woocommerce-error">' + errorThrown + '</div>' )
					},
				}
			)
		}

		function onLoaded() {
			embeddedLoader.hide()
			jQuery( '#ecommpay-iframe-embedded' ).height( 'auto' )
		}

		function loadEmbeddedIframe() {
			const embeddedIframeDiv = jQuery( '#ecommpay-iframe-embedded' )
			if (embeddedIframeDiv.length === 1 && window.ECP.paramsForEmbeddedPP) {
				embeddedIframeDiv.empty()
				window.ECP.isEmbeddedMode = true
				showEmbeddedWidget( window.ECP.paramsForEmbeddedPP )

				jQuery( 'input[name="payment_method"]' ).off( 'change' ).on(
					'change',
					function () {
						if (isEcommpayCardPayment()) {
							jQuery( window ).trigger( 'resize' )
						}
					}
				)
			}
		}

    /* Embedded mode functions */
    window.ECP.widgetInstance = null

    function safeReject(reject) {
      if (typeof reject === 'function') {
        reject()
      }
    }

    function handleAjaxError(reject, jqXHR, textStatus, errorThrown) {
      safeReject(reject)
      submit_error('<div class="woocommerce-error">' + errorThrown + '</div>')
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
          window.ECP.loader.hide()
          show_error(result, 'Result failure')
          break
        default:
          window.ECP.loader.hide()
          show_error(result, 'Invalid response')
      }
    }

    function showEmbeddedWidget(configObj) {
      configObj.onLoaded = onLoaded
      configObj.onValidationError = onValidationError
      configObj.onCheckSubmit = onCheckSubmit
      configObj.onPaymentFail = onPaymentFail
      configObj.onShowLoader = window.ECP.loader.show
      configObj.onHideLoader = window.ECP.loader.hide

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
          $errorDiv.append(jQuery('<span>').text(value)).append('<br>')
        })

        submit_error($errorDiv[0].outerHTML)
      }
    }

    function onCheckSubmit(_data, resolve, reject) {
      clear_error()

      const data = [
        { name: 'action', value: ACTIONS.CHECK_CART_AMOUNT },
        { name: 'amount', value: window.ECP.paramsForEmbeddedPP.payment_amount },
      ]

      jQuery.ajax({
        type: 'POST',
        url: ECP.ajax_url,
        data: data,
        dataType: 'json',
        success: function (result) {
          if (result.amount_is_equal) {
            createWoocommerceOrderForEmbeddedPP(resolve, reject)
          } else {
            window.location.reload()
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          handleAjaxError(reject, jqXHR, textStatus, errorThrown)
        },
      })
		}

    function createWoocommerceOrderForEmbeddedPP(resolve, reject) {
      const extraData = [{ name: 'payment_id', value: window.ECP.paramsForEmbeddedPP.payment_id }]
      if (ECP.order_id > 0) {
        extraData.push({ name: 'order_id', value: ECP.order_id })
      }

      createWoocommerceOrder({
        extraData: extraData,
        onSuccess: function (result) {
          if (result.result === 'success' && result.order_id) {
            ECP.order_id = result.order_id
            resolveCheckSubmitWithParameters(resolve, reject, result)
          } else {
            console.error('[ECP] createWoocommerceOrder: unexpected result', result)
            safeReject(reject)
            show_error(result, 'Order creation failed')
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
        'customer_state',
        'customer_email',
        'billing_address',
        'billing_city',
        'billing_country',
        'billing_postal',
        'billing_region',
        'billing_region_code',
      ]
      const params = fields.reduce(function (acc, field) {
        acc[field] = options[field] || ''
        return acc
      }, {})

      if (options.avs_post_code && options.avs_street_address) {
        params.avs_post_code = options.avs_post_code
        params.avs_street_address = options.avs_street_address
      }

      if (options.customer_shipping) {
        try {
          const decodedJson = decodeURIComponent(
            atob(options.customer_shipping)
              .split('')
              .map((c) => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
              .join('')
          )

          params.customer_shipping = JSON.parse(decodedJson)?.customer?.shipping
        } catch (e) {
          console.error('Failed to parse customer_shipping:', e)
        }
      }


      return params
    }

    function resolveCheckSubmitWithParameters(resolve, reject, orderResult) {
      try {
        const options = JSON.parse(orderResult.optionsJson)
        window.ECP.redirectResult = options

        if (typeof resolve === 'function') {
          resolve({additional_parameters: buildResolveParams(options)})
        }
      } catch (e) {
        console.error('[ECP] resolveCheckSubmit: error parsing optionsJson', e)
        safeReject(reject)
      }
    }

    function onPaymentFail() {
      if (window.ECP.redirectResult && window.ECP.redirectResult.redirect_fail_enabled) {
        window.location.replace(window.ECP.redirectResult.redirect_fail_url)
      } else {
        resetEmbeddedIframe()
      }
    }

    function startEmbeddedIframeFlow() {

      if (window.ECP.widgetInstance && typeof window.ECP.widgetInstance.trySubmit === 'function') {
        window.ECP.widgetInstance.trySubmit()
      } else {
        console.error('[ECP] widget instance or trySubmit not available')
      }
    }
	}
)
