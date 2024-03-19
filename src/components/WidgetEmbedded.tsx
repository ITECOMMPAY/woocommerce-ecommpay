import { useCallback, useEffect } from "@wordpress/element"
import { useDebouncedCallback } from "use-debounce"
import getFieldsForGateway from "../helpers/getFieldsForGateway"
import scrollToSelector from "../helpers/scrollToSelector"
import useBack from "../hooks/useBack"
import useBoolean from "../hooks/useBoolean"
import { PaymentMethodInterface } from "../woocommerce-types"
import OverlayLoader from "./OverlayLoader"

function WidgetEmbedded(props: PaymentMethodInterface) {
  const {
    value: isOverlayLoading,
    setTrue: showOverlayLoader,
    setFalse: hideOverlayLoader,
  } = useBoolean(false)
  const { value: isClarificationRunning, setTrue: setClarificationRunning } =
    useBoolean(false)
  const { value: isWidgetLoading, setFalse: setWidgetLoaded } = useBoolean(true)
  const { back } = useBack()

  const onEmbeddedModeRedirect3dsParentPage = useCallback((data) => {
    const form = document.createElement("form")
    form.setAttribute("method", data.method)
    form.setAttribute("action", data.url)
    form.setAttribute("style", "display:none;")
    form.setAttribute("name", "3dsForm")
    for (let k in data.body) {
      const input = document.createElement("input")
      input.name = k
      input.value = data.body[k]
      form.appendChild(input)
    }
    document.body.appendChild(form)
    form.submit()
  }, [])

  const onMessage = useCallback((event) => {
    if (event.origin !== window.ECP.origin_url) {
      return
    }

    const data = JSON.parse(event.data)

    switch (data.message) {
      case "epframe.show_clarification_page":
        return window.ECP.listeners.onShowClarificationPage()
      case "epframe.embedded_mode.check_validation_response":
        return window.ECP.listeners.onEmbeddedModeCheckValidationResponse(data)
      case "epframe.payment.success":
        return window.ECP.listeners.onSuccess()
      case "epframe.payment.fail":
        return window.ECP.listeners.onFail()
      case "epframe.card.verify.success":
        return window.ECP.listeners.onSuccess()
      case "epframe.card.verify.fail":
        return window.ECP.listeners.onFail()
    }
  }, [])

  const submitIframe = useCallback((options: any) => {
    return new Promise((resolve) => {
      window.ECP.listeners = {
        onShowClarificationPage: () => {
          setClarificationRunning()
          hideOverlayLoader()
          resolve({
            type: props.emitResponse.responseTypes.ERROR,
            messageContext: props.emitResponse.noticeContexts.PAYMENTS,
            message: "Clarification required",
          })
        },
        onEmbeddedModeCheckValidationResponse: (response) => {
          scrollToSelector(
            '[for="radio-control-wc-payment-method-options-ecommpay-card"]'
          )

          if (Object.keys(response.data).length !== 0) {
            const uniqueErrors = Array.from(
              new Set(Object.values(response.data))
            )

            resolve({
              type: props.emitResponse.responseTypes.ERROR,
              messageContext: props.emitResponse.noticeContexts.PAYMENTS,
              message:
                "<b>Validation failed:</b> <ul><li>" +
                uniqueErrors.join("</li><li>") +
                "</li></ul>",
              retry: true,
            })
            return
          }

          window.postMessage(
            JSON.stringify({
              message: "epframe.embedded_mode.submit",
              fields: getFieldsForGateway(options),
              from_another_domain: true,
            })
          )
        },
        onSuccess: () => {
          hideOverlayLoader()
          resolve({
            type: props.emitResponse.responseTypes.SUCCESS,
            redirectUrl: options.redirect_success_url,
          })
        },
        onFail: () => {
          hideOverlayLoader()
          resolve({
            type: props.emitResponse.responseTypes.FAIL,
            messageContext: props.emitResponse.noticeContexts.PAYMENTS,
            message: "Payment failed",
          })
        },
      }

      setTimeout(() => {
        window.postMessage(
          JSON.stringify({
            message: "epframe.embedded_mode.check_validation",
            from_another_domain: true,
          })
        )
      }, 2000)
    })
  }, [])

  const runIframe = useDebouncedCallback(() => {
    const data = [
      {
        name: "action",
        value: "get_data_for_payment_form",
      },
    ]

    if (window.ECP.order_id > 0) {
      data.push({
        name: "order_id",
        value: window.ECP.order_id,
      })
    }

    window.jQuery.ajax({
      type: "POST",
      url: window.ECP.ajax_url + "?" + window.location.href.split("?")[1],
      data,
      dataType: "json",
      success: function (paramsForEmbeddedPP) {
        window.ECP = {
          ...window.ECP,
          isEmbeddedMode: true,
          paramsForEmbeddedPP: {
            ...paramsForEmbeddedPP,
            onExit: back,
            onDestroy: back,
            onLoaded: setWidgetLoaded,
            onEnterKeyPressed: props.onSubmit,
            onPaymentSent: showOverlayLoader,
            onSubmitClarificationForm: showOverlayLoader,
            onEmbeddedModeRedirect3dsParentPage,
          },
        }

        window.EPayWidget.run(window.ECP.paramsForEmbeddedPP, "POST")
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(jqXHR, textStatus, errorThrown)
      },
    })
  }, 2000)

  useEffect(() => {
    const unsubscribePaymentSetup = props.eventRegistration.onPaymentSetup(
      async () => {
        if (isClarificationRunning) {
          window.postMessage(
            JSON.stringify({
              message: "epframe.embedded_mode.submit",
              fields: {},
              from_another_domain: true,
            })
          )
        } else {
          return {
            type: props.emitResponse.responseTypes.SUCCESS,
            meta: {
              paymentMethodData: {
                payment_id: window.ECP.paramsForEmbeddedPP.payment_id,
              },
            },
          }
        }
      },
      0
    )

    const unsubscribeCheckoutSuccess =
      props.eventRegistration.onCheckoutSuccess(async (data: any) => {
        window.ECP.order_id = data.orderId
        const options = JSON.parse(
          data.processingResponse.paymentDetails.optionsJson
        )

        const isAmountEqual =
          props.billing.cartTotal.value === options.payment_amount
        const isCurrencyEqual =
          props.billing.currency.code === options.payment_currency

        if (isAmountEqual && isCurrencyEqual) {
          return await submitIframe(options)
        } else {
          return {
            type: props.emitResponse.responseTypes.ERROR,
            messageContext: props.emitResponse.noticeContexts.CHECKOUT,
            message:
              "Cart amount has changed, please refresh the page and try again",
          }
        }
      }, 0)

    return () => {
      unsubscribePaymentSetup()
      unsubscribeCheckoutSuccess()
    }
  }, [props.eventRegistration.onCheckoutSuccess])

  useEffect(() => {
    runIframe()
  }, [
    props.billing.appliedCoupons,
    props.billing.billingAddress.address_1,
    props.billing.billingAddress.address_2,
    props.billing.billingAddress.city,
    props.billing.billingAddress.company,
    props.billing.billingAddress.country,
    props.billing.billingAddress.email,
    props.billing.billingAddress.first_name,
    props.billing.billingAddress.last_name,
    props.billing.billingAddress.phone,
    props.billing.billingAddress.postcode,
    props.billing.billingAddress.state,
    props.billing.billingData.address_1,
    props.billing.billingData.address_2,
    props.billing.billingData.city,
    props.billing.billingData.company,
    props.billing.billingData.country,
    props.billing.billingData.email,
    props.billing.billingData.first_name,
    props.billing.billingData.last_name,
    props.billing.billingData.phone,
    props.billing.billingData.postcode,
    props.billing.billingData.state,
    props.billing.cartTotal.label,
    props.billing.cartTotal.value,
    props.billing.cartTotalItems,
    props.billing.currency.code,
    props.billing.currency.decimalSeparator,
    props.billing.currency.minorUnit,
    props.billing.currency.prefix,
    props.billing.currency.suffix,
    props.billing.currency.symbol,
    props.billing.currency.thousandSeparator,
    props.billing.customerId,
    props.billing.displayPricesIncludingTax,
    props.shippingData.isSelectingRate,
    props.shippingData.needsShipping,
    props.shippingData.selectedRates,
    props.shippingData.shippingAddress.address_1,
    props.shippingData.shippingAddress.address_2,
    props.shippingData.shippingAddress.city,
    props.shippingData.shippingAddress.company,
    props.shippingData.shippingAddress.country,
    props.shippingData.shippingAddress.first_name,
    props.shippingData.shippingAddress.last_name,
    props.shippingData.shippingAddress.phone,
    props.shippingData.shippingAddress.postcode,
    props.shippingData.shippingAddress.state,
    props.shippingData.shippingRates,
    props.shippingData.shippingRatesLoading,
    props.cartData.cartFees,
    props.cartData.cartItems,
    props.cartData.extensions,
    props.shouldSavePayment,
  ])

  useEffect(() => {
    window.ECP.listeners = {}
    window.addEventListener("message", onMessage)

    return () => {
      window.removeEventListener("message", onMessage)
      window.ECP.listeners = {}
    }
  }, [onMessage])

  return (
    <>
      {isWidgetLoading && "Loading..."}
      <div
        id="ecommpay-iframe-embedded"
        style={{
          height: isWidgetLoading ? "0" : "auto",
        }}
      />
      <OverlayLoader show={isOverlayLoading} />
    </>
  )
}

export default WidgetEmbedded
