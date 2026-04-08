import { useCallback, useEffect, useRef } from '@wordpress/element'
import { useDebouncedCallback } from 'use-debounce'
import { PAYMENT_METHODS, PM_EMBEDDED_CHECK_VALIDATION, PM_EMBEDDED_SUBMIT } from '../constants'
import { ecpDebug } from '../helpers/ecpDebug'
import getFieldsForGateway from '../helpers/getFieldsForGateway'
import postSafeMessage from '../helpers/postSafeMessage'
import scrollToSelector from '../helpers/scrollToSelector'
import useBack from './useBack'
import useBoolean from './useBoolean'
import { PaymentMethodInterface } from '../woocommerce-types'

export interface WidgetParams {
  onExit: () => void
  onDestroy: () => void
  onLoaded: () => void
  onEnterKeyPressed: () => void
  onPaymentSent: () => void
  onSubmitClarificationForm: () => void
  onEmbeddedModeRedirect3dsParentPage: (data: Redirect3dsData) => void
  onShowClarificationPage?: () => void
  onEmbeddedModeCheckValidationResponse?: (data: unknown) => void
  onPaymentSuccess?: () => void
  onPaymentFail?: () => void
  onCardVerifySuccess?: () => void
  onCardVerifyFail?: () => void
  [key: string]: unknown
}

export interface Redirect3dsData {
	method: string
	url: string
	body: Record<string, string>
}


export interface WidgetEmbeddedBaseResult {
	isOverlayLoading: boolean
	showOverlayLoader: () => void
	hideOverlayLoader: () => void
	isClarificationRunning: boolean
	setClarificationRunning: () => void
	isWidgetLoading: boolean
	setWidgetLoaded: () => void
	back: () => void
	onEmbeddedModeRedirect3dsParentPage: (data: Redirect3dsData) => void
	validateIframe: () => Promise<boolean>
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
	const { value: isClarificationRunning, setTrue: setClarificationRunning } = useBoolean(false)
	const { value: isWidgetLoading, setFalse: setWidgetLoaded } = useBoolean(true)
	const { back } = useBack()
	const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null)

	const onEmbeddedModeRedirect3dsParentPage = useCallback(
		(data: Redirect3dsData) => {
			const allowedMethods = ['GET', 'POST']
			if (!allowedMethods.includes(data.method?.toUpperCase())) {
				console.error('Invalid 3DS redirect method:', data.method)
				return
			}

			let url: URL
			try {
				url = new URL(data.url)
			} catch {
				console.error('Invalid 3DS redirect URL:', data.url)
				return
			}

			if (!['http:', 'https:'].includes(url.protocol)) {
				console.error('Unsafe 3DS redirect URL protocol:', url.protocol)
				return
			}

			if (!data.body || typeof data.body !== 'object') {
				console.error('Missing or invalid 3DS redirect body')
				return
			}

			const form = document.createElement('form')
			form.setAttribute('method', data.method.toUpperCase())
			form.setAttribute('action', url.toString())
			form.setAttribute('style', 'display:none;')
			form.setAttribute('name', '3dsForm')
			for (const [key, value] of Object.entries(data.body)) {
				const input = document.createElement('input')
				input.name = key
				input.value = String(value)
				form.appendChild(input)
			}
			document.body.appendChild(form)
			form.submit()
		},
		[]
	)

	const validateIframe = useCallback((): Promise<boolean> => {
		return new Promise((resolve) => {
			window.ECP.listeners = {
				onEmbeddedModeCheckValidationResponse: (response: unknown) => {
					ecpDebug('validateIframe: validation response', response)
					scrollToSelector(`[for="radio-control-wc-payment-method-options-${PAYMENT_METHODS.CARD}"]`)

					const errors: Record<string, unknown> = response && typeof response === 'object' ? (response as Record<string, unknown>) : {}
					if (Object.keys(errors).length !== 0) {
						const uniqueErrors = Array.from(new Set(Object.values(errors)))
						const escapedErrors = uniqueErrors.map((e) => escapeHtml(String(e).replace(/[.,;]+$/, '')))

						ecpDebug('validateIframe: validation errors', uniqueErrors)

						const store = window.wp?.data?.dispatch('core/notices')
						if (store && typeof store.createErrorNotice === 'function') {
							store.createErrorNotice(escapedErrors.join(', '), {
								context: props.emitResponse.noticeContexts.PAYMENTS,
								id: 'ecp-v4-validation-error',
							})
						}

						resolve(false)
						return
					}

					ecpDebug('validateIframe: validation passed')
					resolve(true)
				},
			}

			ecpDebug('validateIframe: posting PM_EMBEDDED_CHECK_VALIDATION')
			postSafeMessage({ message: PM_EMBEDDED_CHECK_VALIDATION })
		})
	}, [props.emitResponse])

	const submitIframe = useCallback((options: Record<string, unknown>) => {
		ecpDebug('submitIframe: start', options)
		return new Promise((resolve) => {
			window.ECP.listeners = {
				onShowClarificationPage: () => {
					ecpDebug('submitIframe: clarification page shown')
					setClarificationRunning()
					hideOverlayLoader()
					resolve({
						type: props.emitResponse.responseTypes.ERROR,
						messageContext: props.emitResponse.noticeContexts.PAYMENTS,
						message: 'Clarification required',
					})
				},
				onEmbeddedModeCheckValidationResponse: (response: unknown) => {
					ecpDebug('submitIframe: validation response', response)
					scrollToSelector(`[for="radio-control-wc-payment-method-options-${PAYMENT_METHODS.CARD}"]`)

					// PP v4 sends errors as a flat object {field: message, ...}
					const errors: Record<string, unknown> = response && typeof response === 'object' ? (response as Record<string, unknown>) : {}
					if (Object.keys(errors).length !== 0) {
						// Strip trailing punctuation added by PP (e.g. "CVV required.")
						const uniqueErrors = Array.from(new Set(Object.values(errors)))
						const escapedErrors = uniqueErrors.map((e) => escapeHtml(String(e).replace(/[.,;]+$/, '')))

						ecpDebug('submitIframe: validation errors', uniqueErrors)
						resolve({
							type: props.emitResponse.responseTypes.ERROR,
							messageContext: props.emitResponse.noticeContexts.PAYMENTS,
							message: escapedErrors.join(', '),
							retry: true,
						})
						return
					}

					ecpDebug('submitIframe: validation passed, posting PM_EMBEDDED_SUBMIT')
					postSafeMessage({ message: PM_EMBEDDED_SUBMIT, fields: getFieldsForGateway(options) })
				},
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

			timeoutRef.current = setTimeout(() => {
				ecpDebug('submitIframe: posting PM_EMBEDDED_CHECK_VALIDATION')
				postSafeMessage({ message: PM_EMBEDDED_CHECK_VALIDATION })
			}, 2000)
		})
	}, [setClarificationRunning, hideOverlayLoader, props.emitResponse])

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
					isEmbeddedMode: true,
					paramsForEmbeddedPP: {
						...paramsForEmbeddedPP,
						onExit: back,
						onDestroy: back,
						onLoaded: setWidgetLoaded,
						onEnterKeyPressed: async () => {
							ecpDebug('onEnterKeyPressed: validating before submit')
							const isValid = await validateIframe()
							if (isValid) {
								window.ECP.iframeValidationPassed = true
								props.onSubmit()
							}
						},
						onPaymentSent: showOverlayLoader,
						onSubmitClarificationForm: showOverlayLoader,
						onEmbeddedModeRedirect3dsParentPage,
						onShowClarificationPage: () => window.ECP.listeners.onShowClarificationPage?.(),
						onEmbeddedModeCheckValidationResponse: (data: unknown) => window.ECP.listeners.onEmbeddedModeCheckValidationResponse?.(data),
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
		isClarificationRunning,
		setClarificationRunning,
		isWidgetLoading,
		setWidgetLoaded,
		back,
		onEmbeddedModeRedirect3dsParentPage,
		validateIframe,
		submitIframe,
		runIframe,
	}
}
