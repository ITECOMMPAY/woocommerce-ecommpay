/**
 * Currencies that don't use decimal places (the smallest unit = base unit).
 * Kept as `as const` array for idiomatic `Array.includes()` type narrowing.
 *
 * @see https://en.wikipedia.org/wiki/ISO_4217
 */
export const NON_DECIMAL_CURRENCIES = [
	'BIF', // Burundian Franc
	'CLP', // Chilean Peso
	'DJF', // Djiboutian Franc
	'GNF', // Guinean Franc
	'ISK', // Icelandic Króna
	'JPY', // Japanese Yen
	'KMF', // Comorian Franc
	'KRW', // South Korean Won
	'PYG', // Paraguayan Guaraní
	'RWF', // Rwandan Franc
	'UGX', // Ugandan Shilling
	'UYI', // Uruguayan Peso en Unidades Indexadas
	'VND', // Vietnamese Đồng
	'VUV', // Vanuatu Vatu
	'XAF', // Central African CFA Franc
	'XOF', // West African CFA Franc
	'XPF', // CFP Franc
] as const

export type NonDecimalCurrency = typeof NON_DECIMAL_CURRENCIES[number]

/**
 * Check if the currency uses decimal places.
 *
 * @param currency - Currency code to check.
 * @returns True if currency does not use decimal places.
 */
export const isNonDecimalCurrency = (currency: string): currency is NonDecimalCurrency => {
	return NON_DECIMAL_CURRENCIES.includes(currency as NonDecimalCurrency)
}
