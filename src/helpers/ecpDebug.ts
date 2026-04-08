/**
 * Logs a debug message to the browser console only when the plugin's
 * log level is set to "debug" in the ECOMMPAY admin settings.
 *
 * PHP injects the log_level string via wp_localize_script as window.ECP.log_level.
 */
export function ecpDebug(message: string, ...data: unknown[]): void {
	if (window.ECP?.log_level === 'debug') {
		console.log('[ECP]', message, ...data)
	}
}
