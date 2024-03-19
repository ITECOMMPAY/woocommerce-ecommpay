import { decodeEntities } from "@wordpress/html-entities"
import PaymentMethodContent from "../components/PaymentMethodContent"
import PaymentMethodLabel from "../components/PaymentMethodLabel"
import { PAYMENT_METHODS } from "../constants/paymentMethods"
const { getSetting } = window.wc.wcSettings
const { registerPaymentMethod } = window.wc.wcBlocksRegistry

export const registerPaymentMethodByName = (name: string) => {
  const data = getSetting(`${name}_data`, null)

  if (!data) {
    return
  }

  const canMakePayment = () => {
    switch (name) {
      case PAYMENT_METHODS.APPLE_PAY:
        return (
          Object.prototype.hasOwnProperty.call(window, "ApplePaySession") &&
          ApplePaySession.canMakePayments()
        )
      default:
        return true
    }
  }

  const paymentMethod = {
    name: name,
    label: <PaymentMethodLabel data={data} />,
    content: <PaymentMethodContent data={data} />,
    edit: <PaymentMethodContent data={data} />,
    canMakePayment: canMakePayment,
    placeOrderButtonLabel: data.checkout_button_text,
    ariaLabel: decodeEntities(data.title),
    supports: {
      showSavedCards: false,
      showSaveOption: false,
      features: data.supports,
    },
  }

  registerPaymentMethod(paymentMethod)
}
