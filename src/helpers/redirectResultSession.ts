import { PaymentOptions } from '../types'

const SESSION_KEY = 'ecp_embedded_redirect_result'

export function saveRedirectResult(options: PaymentOptions): void {
	try {
		sessionStorage.setItem(SESSION_KEY, JSON.stringify(options))
	} catch {}
}

export function loadRedirectResult(): PaymentOptions | null {
	try {
		const raw = sessionStorage.getItem(SESSION_KEY)
		return raw ? (JSON.parse(raw) as PaymentOptions) : null
	} catch {
		return null
	}
}

export function clearRedirectResult(): void {
	try {
		sessionStorage.removeItem(SESSION_KEY)
	} catch {}
}
