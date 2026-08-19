import { useCallback, useEffect, useRef } from '@wordpress/element'
import { useDebouncedCallback } from 'use-debounce'
import { PAYMENT_METHODS, PM_EMBEDDED_CHECK_VALIDATION, PM_EMBEDDED_SUBMIT } from '../constants'
import { ecpDebug } from '../helpers/ecpDebug'
import scrollToSelector from '../helpers/scrollToSelector'
import useBack from './useBack'
import useBoolean from './useBoolean'
import { PaymentMethodInterface } from '../woocommerce-types'

export interface WidgetParams {
  onExit: () => void
  onDestroy: () => void
  onLoaded: () => void
  onPaymentSuccess?: () => void
  onPaymentFail?: () => void
  onCardVerifySuccess?: () => void
  onCardVerifyFail?: () => void
  [key: string]: unknown
}

export interface WidgetEmbeddedBaseResult {
	isOverlayLoading: boolean
	showOverlayLoader: () => void
	hideOverlayLoader: () => void
	isWidgetLoading: boolean
	setWidgetLoaded: () => void
	back: () => void
	submitIframe: (options: Record<string, unknown>) => Promise<unknown>
	runIframe: () => void
}

function escapeHtml(str: unknown): string {
	return String(str)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;')
}

/**
 * Shared base hook for both embedded widget variants.
 * Contains all common states, callbacks, and effects.
 *
 * @param props - WooCommerce payment method interface
 * @param widgetRunner - Version-specific function to start the widget with prepared params
 */
export function useWidgetEmbeddedBase(
	props: PaymentMethodInterface,
	widgetRunner: (params: WidgetParams) => void
): WidgetEmbeddedBaseResult {
	const { value: isOverlayLoading, setTrue: showOverlayLoader, setFalse: hideOverlayLoader } = useBoolean(false)
	const { value: isWidgetLoading, setFalse: setWidgetLoaded } = useBoolean(true)
	const { back } = useBack()
	const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null)

	const submitIframe = useCallback((options: Record<string, unknown>) => {
		ecpDebug('submitIframe: start', options)
		return new Promise((resolve) => {
			window.ECP.listeners = {
				onSuccess: () => {
					ecpDebug('submitIframe: onSuccess')
					hideOverlayLoader()
					resolve({
						type: props.emitResponse.responseTypes.SUCCESS,
						redirectUrl: options.redirect_success_url,
					})
				},
				onFail: () => {
					ecpDebug('submitIframe: onFail')
					hideOverlayLoader()
					runIframe()
					resolve({
						type: props.emitResponse.responseTypes.FAIL,
						messageContext: props.emitResponse.noticeContexts.PAYMENTS,
						message: 'Payment failed',
					})
				},
			}
		})
	}, [hideOverlayLoader, props.emitResponse])

	const runIframe = useDebouncedCallback(() => {
		ecpDebug('runIframe: fetching payment form data')
		const formData = new FormData()
		formData.append('action', 'get_data_for_payment_form')

		if (window.ECP.order_id > 0) {
			formData.append('order_id', String(window.ECP.order_id))
		}

		fetch(window.ECP.ajax_url + window.location.search, {
			method: 'POST',
			body: formData,
		})
			.then((res) => {
				if (!res.ok) {
					throw new Error(`HTTP ${res.status}`)
				}
				return res.json()
			})
			.then((paramsForEmbeddedPP: WidgetParams) => {
				ecpDebug('runIframe: got payment params, running widget')
				window.ECP = {
					...window.ECP,
					paramsForEmbeddedPP: {
						...paramsForEmbeddedPP,
						onExit: back,
						onDestroy: back,
						onLoaded: setWidgetLoaded,
						onPaymentSuccess: () => window.ECP.listeners.onSuccess?.(),
						onPaymentFail: () => window.ECP.listeners.onFail?.(),
						onCardVerifySuccess: () => window.ECP.listeners.onSuccess?.(),
						onCardVerifyFail: () => window.ECP.listeners.onFail?.(),
					},
				}

				widgetRunner(window.ECP.paramsForEmbeddedPP as WidgetParams)
			})
			.catch((err: unknown) => {
				ecpDebug('runIframe: fetch error', err)
				console.error(err)
			})
	}, 2000)

	const billingKey = JSON.stringify(props.billing)
	const shippingKey = JSON.stringify(props.shippingData)
	const cartKey = JSON.stringify(props.cartData)

	useEffect(() => {
		runIframe()
	}, [billingKey, shippingKey, cartKey, props.shouldSavePayment])

	useEffect(() => {
		window.ECP.listeners = {}

		return () => {
			window.ECP.listeners = {}
			if (timeoutRef.current !== null) {
				clearTimeout(timeoutRef.current)
			}
		}
	}, [])

	return {
		isOverlayLoading,
		showOverlayLoader,
		hideOverlayLoader,
		isWidgetLoading,
		setWidgetLoaded,
		back,
		submitIframe,
		runIframe,
	}
}
