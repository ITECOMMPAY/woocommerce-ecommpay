import { RESOLVE_PARAM_FIELDS } from '../constants'
import { PaymentOptions, ResolveParams } from '../types'

/**
 * Build resolve parameters for the embedded widget checkSubmit from order options.
 *
 * @param options - Payment options object.
 * @returns Resolve parameters object.
 */
export const buildResolveParams = (options: PaymentOptions): ResolveParams => {
	const params = RESOLVE_PARAM_FIELDS.reduce((acc, field) => {
		acc[field] = (options[field] as string) || ''
		return acc
	}, {} as Record<typeof RESOLVE_PARAM_FIELDS[number], string>) as unknown as ResolveParams

	// AVS fields should be passed together only.
	if (options.avs_post_code && options.avs_street_address) {
		params.avs_post_code = options.avs_post_code
		params.avs_street_address = options.avs_street_address
	}

	return params
}
