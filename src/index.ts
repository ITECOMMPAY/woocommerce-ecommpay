declare global {
  interface Window {
    wp: any
    wc: any
    ECP: any
  }
}

import { registerPaymentMethodByName } from "./helpers/registerPaymentMethodByName"

for (const gateway of window.ECP.gateways) {
  registerPaymentMethodByName(gateway)
}
