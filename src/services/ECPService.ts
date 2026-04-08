/**
 * Thin wrapper around window.ECP order-related state mutations.
 * Centralise writes for easier tracing during debugging.
 */
const ECPService = {
	setOrderId(id: number | string): void {
		window.ECP.order_id = Number(id)
	},

	getOrderId(): number | undefined {
		return window.ECP.order_id
	},
}

export default ECPService
