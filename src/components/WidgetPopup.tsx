import { useCallback, useEffect } from '@wordpress/element'
import useBack from '../hooks/useBack'
import { PaymentMethodInterface } from '../woocommerce-types'

interface IProps extends PaymentMethodInterface {
  children: React.ReactNode
}

function WidgetPopup(props: IProps) {
  const { back } = useBack()

  const runPopup = useCallback(
    (options: any) => {
      return new Promise((resolve) => {
        window.EPayWidget.run(
          {
            ...options,
            onPaymentSuccess: () => {
              resolve({
                type: props.emitResponse.responseTypes.SUCCESS,
                redirectUrl: '',
              })
            },
            onPaymentFail: () => {
              resolve({
                type: props.emitResponse.responseTypes.ERROR,
                messageContext: props.emitResponse.noticeContexts.PAYMENTS,
                message: 'Payment was declined. You can try another payment method.',
                retry: true,
              })
            },
            onExit: () => {
              back()
              resolve({
                type: props.emitResponse.responseTypes.ERROR,
                messageContext: props.emitResponse.noticeContexts.CHECKOUT_ACTIONS,
                message: 'Payment cancelled',
                retry: true,
              })
            },
            onDestroy: () => {
              back()
              resolve({
                type: props.emitResponse.responseTypes.ERROR,
                messageContext: props.emitResponse.noticeContexts.CHECKOUT_ACTIONS,
                message: 'Payment cancelled',
                retry: true,
              })
            },
          },
          'POST'
        )
      })
    },
    [back]
  )

  useEffect(() => {
    const unsubscribeCheckoutSuccess = props.eventRegistration.onCheckoutSuccess(async (data: any) => {
      window.ECP.order_id = data.orderId
      const options = JSON.parse(data.processingResponse.paymentDetails.optionsJson)
      return await runPopup(options)
    }, 0)

    return () => {
      unsubscribeCheckoutSuccess()
    }
  }, [props.eventRegistration.onCheckoutSuccess, runPopup])

  return props.children
}

export default WidgetPopup
