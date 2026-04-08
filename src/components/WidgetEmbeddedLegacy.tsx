import { useEffect, useRef } from '@wordpress/element'
import { ecpDebug } from '../helpers/ecpDebug'
import { parseCheckoutSuccessData, CheckoutSuccessData } from '../helpers/parseCheckoutSuccessData'
import { postSubmitClarification } from '../helpers/postSafeMessage'
import { useWidgetEmbeddedBase } from '../hooks/useWidgetEmbeddedBase'
import ECPService from '../services/ECPService'
import { PaymentMethodInterface } from '../woocommerce-types'
import OverlayLoader from './OverlayLoader'

function WidgetEmbeddedLegacy(props: PaymentMethodInterface) {
	const {
		eventRegistration,
		emitResponse,
		billing,
	} = props

	const { isOverlayLoading, isClarificationRunning, isWidgetLoading, validateIframe, submitIframe } = useWidgetEmbeddedBase(
		props,
		(params) => {
			window.EPayWidget.run(params, 'POST')
		}
	)

	const isClarificationRunningRef = useRef(isClarificationRunning)
	useEffect(() => {
		isClarificationRunningRef.current = isClarificationRunning
	}, [isClarificationRunning])

	useEffect(() => {
		const unsubscribePaymentSetup = eventRegistration.onPaymentSetup(async () => {
			ecpDebug('legacy: onPaymentSetup fired', { clarification: isClarificationRunningRef.current })
			if (isClarificationRunningRef.current) {
				postSubmitClarification()
				isClarificationRunningRef.current = false
				return { type: emitResponse.responseTypes.SUCCESS }
			}

			// Enter key path pre-validates via onEnterKeyPressed; skip redundant check.
			if (!window.ECP.iframeValidationPassed) {
				const isValid = await validateIframe()
				if (!isValid) {
					return {
						type: emitResponse.responseTypes.ERROR,
						messageContext: emitResponse.noticeContexts.PAYMENTS,
						retry: true,
					}
				}
			}
			window.ECP.iframeValidationPassed = false

			return {
				type: emitResponse.responseTypes.SUCCESS,
				meta: {
					paymentMethodData: {
						payment_id: window.ECP?.paramsForEmbeddedPP?.payment_id,
					},
				},
			}
		}, 0)

		const unsubscribeCheckoutSuccess = eventRegistration.onCheckoutSuccess(async (data: CheckoutSuccessData) => {
			ecpDebug('legacy: onCheckoutSuccess fired', { orderId: data.orderId })
			ECPService.setOrderId(data.orderId)
			const { options, isAmountEqual, isCurrencyEqual } = parseCheckoutSuccessData(data, billing)

			ecpDebug('legacy: amount check', { isAmountEqual, isCurrencyEqual })
			if (isAmountEqual && isCurrencyEqual) {
				return await submitIframe(options)
			} else {
				return {
					type: emitResponse.responseTypes.ERROR,
					messageContext: emitResponse.noticeContexts.CHECKOUT,
					message: 'Cart amount has changed, please refresh the page and try again',
				}
			}
		}, 0)

		return () => {
			unsubscribePaymentSetup()
			unsubscribeCheckoutSuccess()
		}
	}, [
		eventRegistration.onPaymentSetup,
		eventRegistration.onCheckoutSuccess,
		emitResponse.responseTypes.SUCCESS,
		emitResponse.responseTypes.ERROR,
		emitResponse.noticeContexts.CHECKOUT,
		billing,
		isClarificationRunning,
		validateIframe,
		submitIframe,
	])

	return (
		<>
			{isWidgetLoading && 'Loading...'}
			<div
				id="ecommpay-iframe-embedded"
				style={{
					height: isWidgetLoading ? '0' : 'auto',
				}}
			/>
			<OverlayLoader show={isOverlayLoading} />
		</>
	)
}

export default WidgetEmbeddedLegacy
