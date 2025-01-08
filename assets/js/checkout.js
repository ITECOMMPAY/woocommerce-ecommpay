jQuery(document).ready(function () {
  jQuery(document.body).append(
    '<div id="ecommpay-overlay-loader" class="blockUI blockOverlay ecommpay-loader-overlay" style="display: none;"></div>'
  )
  window.ECP.isEmbeddedMode = false
  var targetForm
  var loader = jQuery('#ecommpay-loader')
  window.ECP.isPaymentRunning = false
  window.ECP.paramsForEmbeddedPP = false
  window.ECP.clarificationRunning = false
  window.ECP.lastEmbeddedRequestTime = 0
  window.ECP.redirectResult = false

  // Create order via AJAX in case of "popup" or "iframe" mode
  jQuery('body').on('click', '#place_order', function (e) {
    if (!isEcommpayPayment()) {
      return
    }
    targetForm = jQuery(e.target.form)
    e.preventDefault()
    if (window.ECP.isEmbeddedMode && isEcommpayCardPayment()) {
      startEmbeddedIframeFlow()
      return
    }

    var href = window.location.href.split('?')
    var data = targetForm.serializeArray()
    var query_string = href[1] === undefined ? '' : href[1]

    data.push({
      name: 'action',
      value: 'ecommpay_process',
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
      success: success,
      error: function (jqXHR, textStatus, errorThrown) {
        submit_error('<div class="woocommerce-error">' + errorThrown + '</div>')
      },
    })
  })

  // update embedded iframe when updating cart (taxes, delivery, etc)
  jQuery(document.body).on('updated_checkout', function () {
    resetEmbeddedIframe()
  })

  getParamsForCreateEmbeddedPP()

  function resetEmbeddedIframe() {
    window.ECP.paramsForEmbeddedPP = false
    jQuery('#ecommpay-iframe-embedded').height(0).empty()
    jQuery('#ecommpay-loader-embedded').show()
    getParamsForCreateEmbeddedPP()
  }

  function loadEmbeddedIframe() {
    var embeddedIframeDiv = jQuery('#ecommpay-iframe-embedded')
    if (embeddedIframeDiv.length === 1 && window.ECP.paramsForEmbeddedPP) {
      jQuery('#ecommpay-iframe-embedded').empty()
      window.ECP.isEmbeddedMode = true
      loader = jQuery('#ecommpay-loader-embedded')
      showIFrame(window.ECP.paramsForEmbeddedPP)
      jQuery('input[name="payment_method"]').change(function () {
        if (isEcommpayCardPayment()) {
          jQuery(window).trigger('resize')
        }
      })
    }
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
    var scrollElement = jQuery('.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout'),
      isSmoothScrollSupported = 'scrollBehavior' in document.documentElement.style

    if (!scrollElement.length) {
      scrollElement = loader
    }

    if (scrollElement.length) {
      if (isSmoothScrollSupported) {
        scrollElement[0].scrollIntoView({
          behavior: 'smooth',
        })
      } else {
        jQuery('html, body').animate(
          {
            scrollTop: scrollElement.offset().top - 100,
          },
          1000
        )
      }
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

  function success(result) {
    switch (result.result) {
      case 'success':
        ECP.order_id = result.order_id
        if (window.ECP.isEmbeddedMode && isEcommpayCardPayment()) {
          processOrderWithEmbeddedIframe(result)
          break
        }
        if (result.optionsJson) {
          const options = JSON.parse(result.optionsJson)
          if (options.frame_mode === 'popup') {
            showPopup(options)
            break
          }
        }
        redirect(result.redirect)
        break
      case 'failure':
        show_error(result, 'Result failure')
        break
      default:
        show_error(result, 'Invalid response')
    }
  }

  function runWidget(configObj) {
    configObj.onExit = back
    configObj.onDestroy = back
    EPayWidget.run(configObj, 'POST')
  }

  function showPopup(configObj) {
    runWidget(configObj)
  }

  function showIFrame(configObj) {
    configObj.onLoaded = onLoaded
    configObj.onEmbeddedModeCheckValidationResponse = onEmbeddedModeCheckValidationResponse
    configObj.onEnterKeyPressed = onEnterKeyPressed
    configObj.onPaymentSent = showOverlayLoader
    configObj.onSubmitClarificationForm = showOverlayLoader
    configObj.onShowClarificationPage = onShowClarificationPage
    configObj.onEmbeddedModeRedirect3dsParentPage = onEmbeddedModeRedirect3dsParentPage
    configObj.onPaymentSuccess = redirectOnSuccess
    configObj.onCardVerifySuccess = redirectOnSuccess
    configObj.onPaymentFail = redirectOnFail
    configObj.onCardVerifyFail = redirectOnFail

    loader.show()
    scroll_to_notices()
    runWidget(configObj)
  }

  function onLoaded() {
    loader.hide()
    jQuery('#ecommpay-iframe-embedded').height('auto')
  }

  function back() {
    var href = window.location.href.split('?')
    var query_string = href[1] === undefined ? '' : href[1]
    var data = []

    data.push({
      name: 'action',
      value: 'ecommpay_break',
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

  /* Embedded iFrame flow */

  // Step1 . On page load - init payment form with minimum params
  function getParamsForCreateEmbeddedPP() {
    var href = window.location.href.split('?')
    var data = [
      {
        name: 'action',
        value: 'get_data_for_payment_form',
      },
    ]
    var query_string = href[1] === undefined ? '' : href[1]

    if (ECP.order_id > 0) {
      data.push({
        name: 'order_id',
        value: ECP.order_id,
      })
    }

    const requestTime = Date.now()
    window.ECP.lastEmbeddedRequestTime = requestTime

    jQuery.ajax({
      type: 'POST',
      url: ECP.ajax_url + '?' + query_string,
      data: data,
      dataType: 'json',
      success: function (result) {
        if (requestTime === window.ECP.lastEmbeddedRequestTime) {
          window.ECP.paramsForEmbeddedPP = result
          loadEmbeddedIframe()
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        submit_error('<div class="woocommerce-error">' + errorThrown + '</div>')
      },
    })
  }

  // Step 2. Button "Place order" - onclick, send message to iframe, call form validation
  function startEmbeddedIframeFlow() {
    window.ECP.isPaymentRunning = true

    if (ECP.order_id) {
      window.postMessage('{"message":"epframe.embedded_mode.check_validation","from_another_domain":true}')
      return
    }

    const data = [
      { name: 'action', value: 'check_cart_amount' },
      { name: 'amount', value: window.ECP.paramsForEmbeddedPP.payment_amount },
    ]
    jQuery.ajax({
      type: 'POST',
      url: ECP.ajax_url,
      data: data,
      dataType: 'json',
      success: function (result) {
        if (result.amount_is_equal) {
          window.postMessage('{"message":"epframe.embedded_mode.check_validation","from_another_domain":true}')
        } else {
          window.location.reload()
        }
      },
      error: (jqXHR, textStatus, errorThrown) => {
        alert(textStatus)
      },
    })
  }

  // Step3. Listen Answer from Iframe about form validation
  function onEmbeddedModeCheckValidationResponse(data) {
    if (!window.ECP.isPaymentRunning) {
      return
    }
    if (!!data && Object.keys(data).length > 0) {
      var errors = []
      var errorText = ''
      jQuery.each(data, function (key, value) {
        errors.push(value)
      })
      var errorsUnique = [...new Set(errors)] //remove duplicated
      jQuery.each(errorsUnique, function (key, value) {
        errorText += value + '<br>'
      })
      submit_error('<div class="woocommerce-error">' + errorText + '</div>')
      window.ECP.isPaymentRunning = false
    } else {
      clear_error()
      if (window.ECP.clarificationRunning) {
        postSubmit({})
      } else {
        createWoocommerceOrder()
      }
    }
  }

  // Step 4. Create Wocommerce Order
  function createWoocommerceOrder() {
    var href = window.location.href.split('?')
    var data = targetForm.serializeArray()
    var query_string = href[1] === undefined ? '' : href[1]
    data.push({
      name: 'action',
      value: 'ecommpay_process',
    })
    if (ECP.order_id > 0) {
      data.push({
        name: 'order_id',
        value: ECP.order_id,
      })
    }
    data.push({
      name: 'payment_id',
      value: window.ECP.paramsForEmbeddedPP.payment_id,
    })
    jQuery.ajax({
      type: 'POST',
      url: ECP.ajax_url + '?' + query_string,
      data: data,
      dataType: 'json',
      success: success,
      error: function (jqXHR, textStatus, errorThrown) {
        submit_error('<div class="woocommerce-error">' + errorThrown + '</div>')
      },
    })
  }

  // Step 5 send payment request via post message
  function processOrderWithEmbeddedIframe(result) {
    window.ECP.redirectResult = JSON.parse(result.optionsJson)
    window.ECP.redirectResult.frame_mode = 'iframe'
    window.ECP.redirectResult.payment_id = window.ECP.paramsForEmbeddedPP.payment_id
    var billingFields = [
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
    var fieldsObject = {}
    Object.keys(window.ECP.redirectResult).forEach((key) => {
      var name = key
      if (billingFields.includes(key)) {
        name = 'BillingInfo[' + name + ']'
      }
      fieldsObject[name] = window.ECP.redirectResult[key]
      if (key === 'billing_country') {
        fieldsObject['BillingInfo[country]'] = window.ECP.redirectResult[key]
      }
    })

    postSubmit(fieldsObject)
  }

  function postSubmit(fields) {
    var message = { message: 'epframe.embedded_mode.submit' }
    message.fields = fields
    message.from_another_domain = true
    window.postMessage(JSON.stringify(message))
  }

  function onEnterKeyPressed() {
    jQuery('#place_order').click()
  }

  function redirectOnSuccess() {
    if (window.ECP.redirectResult.redirect_success_enabled) {
      hideOverlayLoader()
      window.location.replace(window.ECP.redirectResult.redirect_success_url)
    }
  }

  function redirectOnFail() {
    if (window.ECP.redirectResult.redirect_fail_enabled) {
      hideOverlayLoader()
      window.location.replace(window.ECP.redirectResult.redirect_fail_url)
    }
  }

  function onEmbeddedModeRedirect3dsParentPage(data) {
    var form = document.createElement('form')
    form.setAttribute('method', data.method)
    form.setAttribute('action', data.url)
    form.setAttribute('style', 'display:none;')
    form.setAttribute('name', '3dsForm')
    for (let k in data.body) {
      const input = document.createElement('input')
      input.name = k
      input.value = data.body[k]
      form.appendChild(input)
    }
    document.body.appendChild(form)
    form.submit()
  }

  function showOverlayLoader() {
    jQuery('#ecommpay-overlay-loader').show()
  }

  function hideOverlayLoader() {
    jQuery('#ecommpay-overlay-loader').hide()
  }

  function onShowClarificationPage() {
    window.ECP.clarificationRunning = true
    hideOverlayLoader()
  }
})
