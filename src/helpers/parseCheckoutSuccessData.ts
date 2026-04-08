import { isNonDecimalCurrency } from '../constants'
import { PaymentOptions } from '../types'

export interface CheckoutSuccessData {
	orderId: number
	processingResponse: {
		paymentDetails: {
			optionsJson: string
		}
	}
}

export interface ParsedCheckoutSuccess {
	options: PaymentOptions
	isAmountEqual: boolean
	isCurrencyEqual: boolean
}

/**
 * Parses WC Blocks onCheckoutSuccess data and validates
 * that the payment page amount/currency matches the current cart.
 */
export function parseCheckoutSuccessData(
	data: CheckoutSuccessData,
	billing: { currency: { code: string; minorUnit: number }; cartTotal: { value: number } }
): ParsedCheckoutSuccess {
	const options = JSON.parse(data.processingResponse.paymentDetails.optionsJson) as PaymentOptions

	const paymentPageAmount = options.payment_amount
	const divisorOrder = billing.currency.minorUnit - (isNonDecimalCurrency(billing.currency.code) ? 0 : 2)
	const cartTotal = Math.round(billing.cartTotal.value / Math.pow(10, divisorOrder))

	return {
		options,
		isAmountEqual: paymentPageAmount === cartTotal,
		isCurrencyEqual: billing.currency.code === options.payment_currency,
	}
}
