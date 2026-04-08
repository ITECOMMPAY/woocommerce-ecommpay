import type { MutableRefObject } from 'react'
import { ecpDebug } from './ecpDebug'
import { PaymentOptions, ResolveParams } from '../types'
import { PaymentMethodInterface } from '../woocommerce-types'

export type CheckSubmitResolve = (params: { additional_parameters: ResolveParams }) => void
export type CheckSubmitReject = () => void
export type CheckoutSuccessResolve = (result: { type: string; redirectUrl?: string }) => void

interface BuildEmbeddedWidgetParamsOptions {
	props: PaymentMethodInterface
	checkSubmitResolveRef: MutableRefObject<CheckSubmitResolve | null>
	checkSubmitRejectRef: MutableRefObject<CheckSubmitReject | null>
	checkoutSuccessResolveRef: MutableRefObject<CheckoutSuccessResolve | null>
	showOverlayLoader: () => void
	hideOverlayLoader: () => void
	runIframe: () => void
	loadRedirectResult: () => PaymentOptions | null
	clearRedirectResult: () => void
}

export function buildEmbeddedWidgetParams({
	props,
	checkSubmitResolveRef,
	checkSubmitRejectRef,
	checkoutSuccessResolveRef,
	showOverlayLoader,
	hideOverlayLoader,
	runIframe,
	loadRedirectResult,
	clearRedirectResult,
}: BuildEmbeddedWidgetParamsOptions): Record<string, unknown> {
	return {
		onEnterKeyPressed: () => props.onSubmit(),
		onValidationError: (errors: unknown) => {
			ecpDebug('embedded: onValidationError', errors)
			if (!errors || typeof errors !== 'object') return

			const errorsObj = errors as Record<string, unknown>
			const rawErrors: unknown[] = Array.isArray(errorsObj.errors)
				? errorsObj.errors
				: Object.values(errorsObj)

			const uniqueErrors = Array.from(
				new Set(rawErrors.map((e) => String(e).replace(/[.,;]+$/, '')).filter(Boolean))
			)
			if (uniqueErrors.length === 0) return

			if (window.ECP.paymentSetupResolve) {
				window.ECP.paymentSetupResolve({
					type: props.emitResponse.responseTypes.ERROR,
					messageContext: props.emitResponse.noticeContexts.PAYMENTS,
					message: uniqueErrors.join(', '),
					retry: true,
				})
				window.ECP.paymentSetupResolve = null
			} else {
				const store = window.wp?.data?.dispatch('core/notices')
				if (store && typeof store.createErrorNotice === 'function') {
					store.createErrorNotice(uniqueErrors.join(', '), {
						context: props.emitResponse.noticeContexts.PAYMENTS,
						id: 'ecp-pp-validation-error',
					})
				}
			}
		},
		onCheckSubmit: (_data: unknown, resolve: CheckSubmitResolve, reject: CheckSubmitReject) => {
			ecpDebug('embedded: onCheckSubmit fired')
			checkSubmitResolveRef.current = resolve
			checkSubmitRejectRef.current = reject
			if (window.ECP.paymentSetupResolve) {
				window.ECP.paymentSetupResolve({
					type: props.emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							payment_id: window.ECP?.paramsForEmbeddedPP?.payment_id,
						},
					},
				})
				window.ECP.paymentSetupResolve = null
			} else {
				// Enter key in the widget bypassing onPaymentSetup; trigger it now.
				props.onSubmit()
			}
		},
		onPaymentSuccess: () => {
			ecpDebug('embedded: onPaymentSuccess')
			hideOverlayLoader()
			const result = window.ECP.redirectResult ?? loadRedirectResult()
			clearRedirectResult()
			if (checkoutSuccessResolveRef.current) {
				checkoutSuccessResolveRef.current({
					type: props.emitResponse.responseTypes.SUCCESS,
					redirectUrl: result?.redirect_success_url as string | undefined,
				})
				checkoutSuccessResolveRef.current = null
			}
		},
		onPaymentFail: () => {
			ecpDebug('embedded: onPaymentFail')
			hideOverlayLoader()
			const result = window.ECP.redirectResult ?? loadRedirectResult()
			clearRedirectResult()
			if (checkoutSuccessResolveRef.current) {
				checkoutSuccessResolveRef.current({
					type: props.emitResponse.responseTypes.ERROR,
				})
				checkoutSuccessResolveRef.current = null
			}
			if (result?.redirect_fail_enabled && result.redirect_fail_url) {
				window.location.replace(result.redirect_fail_url as string)
			} else {
				runIframe()
			}
		},
		onShowLoader: showOverlayLoader,
		onHideLoader: hideOverlayLoader,
	}
}
