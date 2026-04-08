import { PM_EMBEDDED_SUBMIT } from '../constants'

function postSafeMessage(payload: Record<string, unknown>): void {
	window.postMessage(JSON.stringify({ ...payload, from_another_domain: true }), window.location.origin)
}

export function postSubmitClarification(): void {
	postSafeMessage({ message: PM_EMBEDDED_SUBMIT, fields: {} })
}

export default postSafeMessage
