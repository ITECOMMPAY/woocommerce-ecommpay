import { useEffect, useRef } from '@wordpress/element'
import type { MutableRefObject } from 'react'
import { buildResolveParams } from '../helpers/buildResolveParams'
import {
	CheckSubmitReject,
	CheckSubmitResolve,
	CheckoutSuccessResolve,
} from '../helpers/buildEmbeddedWidgetParams'
import { ecpDebug } from '../helpers/ecpDebug'
import { postSubmitClarification } from '../helpers/postSafeMessage'
import { parseCheckoutSuccessData, CheckoutSuccessData } from '../helpers/parseCheckoutSuccessData'
import { saveRedirectResult } from '../helpers/redirectResultSession'
import ECPService from '../services/ECPService'
import { PaymentMethodInterface } from '../woocommerce-types'

interface UseEmbeddedCheckoutEventsOptions {
	props: PaymentMethodInterface
	widgetInstanceRef: MutableRefObject<{ trySubmit?: () => void } | null>
	checkSubmitResolveRef: MutableRefObject<CheckSubmitResolve | null>
	checkSubmitRejectRef: MutableRefObject<CheckSubmitReject | null>
	checkoutSuccessResolveRef: MutableRefObject<CheckoutSuccessResolve | null>
	isClarificationRunning: boolean
}

export function useEmbeddedCheckoutEvents({
	props,
	widgetInstanceRef,
	checkSubmitResolveRef,
	checkSubmitRejectRef,
	checkoutSuccessResolveRef,
	isClarificationRunning,
}: UseEmbeddedCheckoutEventsOptions): void {
	const { eventRegistration, emitResponse, billing } = props

	const isClarificationRunningRef = useRef(isClarificationRunning)
	useEffect(() => {
		isClarificationRunningRef.current = isClarificationRunning
	}, [isClarificationRunning])

	useEffect(() => {
		const unsubscribePaymentSetup = eventRegistration.onPaymentSetup(async () => {
			ecpDebug('embedded: onPaymentSetup fired', { clarification: isClarificationRunningRef.current })

			if (isClarificationRunningRef.current) {
				postSubmitClarification()
				isClarificationRunningRef.current = false
				return { type: emitResponse.responseTypes.SUCCESS }
			}

			return new Promise((resolve) => {
				window.ECP.paymentSetupResolve = resolve
				if (widgetInstanceRef.current && typeof widgetInstanceRef.current.trySubmit === 'function') {
					ecpDebug('embedded: calling trySubmit')
					widgetInstanceRef.current.trySubmit()
				} else {
					resolve({
						type: emitResponse.responseTypes.ERROR,
						messageContext: emitResponse.noticeContexts.PAYMENTS,
						message: 'Payment widget is not ready. Please refresh the page and try again.',
						retry: true,
					})
				}
			})
		}, 0)

		const unsubscribeCheckoutSuccess = eventRegistration.onCheckoutSuccess(async (data: CheckoutSuccessData) => {
			ecpDebug('embedded: onCheckoutSuccess fired', { orderId: data.orderId })
			ECPService.setOrderId(data.orderId)
			const { options, isAmountEqual, isCurrencyEqual } = parseCheckoutSuccessData(data, billing)

			ecpDebug('embedded: amount check', { isAmountEqual, isCurrencyEqual })
			if (!isAmountEqual || !isCurrencyEqual) {
				if (checkSubmitRejectRef.current) {
					checkSubmitRejectRef.current()
					checkSubmitRejectRef.current = null
				}
				return {
					type: emitResponse.responseTypes.ERROR,
					messageContext: emitResponse.noticeContexts.CHECKOUT,
					message: 'Cart amount has changed, please refresh the page and try again',
				}
			}

			window.ECP.redirectResult = options
			saveRedirectResult(options)

			ecpDebug('embedded: resolving checkSubmit with redirect params')
			if (checkSubmitResolveRef.current) {
				checkSubmitResolveRef.current({ additional_parameters: buildResolveParams(options) })
				checkSubmitResolveRef.current = null
			}

			return new Promise<{ type: string; redirectUrl?: string }>((resolve) => {
				checkoutSuccessResolveRef.current = resolve
			})
		}, 0)

		const unsubscribeCheckoutFail = eventRegistration.onCheckoutFail(async () => {
			if (checkSubmitRejectRef.current) {
				checkSubmitRejectRef.current()
				checkSubmitRejectRef.current = null
			}
		}, 0)

		return () => {
			unsubscribePaymentSetup()
			unsubscribeCheckoutSuccess()
			unsubscribeCheckoutFail()
		}
	}, [
		eventRegistration.onPaymentSetup,
		eventRegistration.onCheckoutSuccess,
		eventRegistration.onCheckoutFail,
		emitResponse.responseTypes.SUCCESS,
		emitResponse.responseTypes.ERROR,
		emitResponse.noticeContexts.PAYMENTS,
		emitResponse.noticeContexts.CHECKOUT,
		billing,
		isClarificationRunning,
	])
}
