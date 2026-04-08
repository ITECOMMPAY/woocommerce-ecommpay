import { useRef, useState } from '@wordpress/element'
import {
	buildEmbeddedWidgetParams,
	CheckSubmitResolve,
	CheckSubmitReject,
	CheckoutSuccessResolve,
} from '../helpers/buildEmbeddedWidgetParams'
import { loadRedirectResult, clearRedirectResult } from '../helpers/redirectResultSession'
import { useWidgetEmbeddedBase } from '../hooks/useWidgetEmbeddedBase'
import { useEmbeddedCheckoutEvents } from '../hooks/useEmbeddedCheckoutEvents'
import { PaymentMethodInterface } from '../woocommerce-types'
import OverlayLoader from './OverlayLoader'

function WidgetEmbedded(props: PaymentMethodInterface) {

	const widgetParamsRef = useRef<Record<string, unknown>>({})
	const widgetInstanceRef = useRef<{ trySubmit?: () => void } | null>(null)
	const checkSubmitResolveRef = useRef<CheckSubmitResolve | null>(null)
	const checkSubmitRejectRef = useRef<CheckSubmitReject | null>(null)
	const checkoutSuccessResolveRef = useRef<CheckoutSuccessResolve | null>(null)
	const [isWidgetError, setIsWidgetError] = useState(false)

	const { isOverlayLoading, isClarificationRunning, isWidgetLoading, showOverlayLoader, hideOverlayLoader, runIframe } =
		useWidgetEmbeddedBase(props, (params) => {
			Object.assign(params, widgetParamsRef.current)
			window.ECP.isEmbeddedMode = true
			try {
				widgetInstanceRef.current = window.EPayWidget.runEmbedded(params, 'POST')
			} catch (e) {
				console.error('Failed to initialize embedded widget:', e)
				setIsWidgetError(true)
			}
		})

	widgetParamsRef.current = buildEmbeddedWidgetParams({
		props,
		checkSubmitResolveRef,
		checkSubmitRejectRef,
		checkoutSuccessResolveRef,
		showOverlayLoader,
		hideOverlayLoader,
		runIframe,
		loadRedirectResult,
		clearRedirectResult,
	})

	useEmbeddedCheckoutEvents({
		props,
		widgetInstanceRef,
		checkSubmitResolveRef,
		checkSubmitRejectRef,
		checkoutSuccessResolveRef,
		isClarificationRunning,
	})

	return (
		<>
			{isWidgetError && <div>Failed to load widget</div>}
			{isWidgetLoading && !isWidgetError && 'Loading...'}
			<div
				id="ecommpay-iframe-embedded"
				style={{ height: isWidgetLoading ? '0' : 'auto' }}
			/>
			<OverlayLoader show={isOverlayLoading} />
		</>
	)
}

export default WidgetEmbedded
