declare global {
  interface Window {
    wp: any
    wc: any
    ECP: Record<string, any> & {
      ajax_url: string
      origin_url: string
      order_id: number
      gateways: string[]
      ecp_pay_nonce: string
      log_level: string
    }
    EPayWidget: any
  }
}

import { registerPaymentMethodByName } from './helpers/registerPaymentMethodByName'

for (const gateway of window.ECP.gateways) {
  registerPaymentMethodByName(gateway)
}
