import { useMemo } from '@wordpress/element'
import { decodeEntities } from '@wordpress/html-entities'
import { FrameMode, PaymentPageVersion, PAYMENT_METHODS  } from '../constants'
import type { PaymentMethodInterface } from '../woocommerce-types'
import WidgetEmbeddedLegacy from './WidgetEmbeddedLegacy'
import WidgetEmbedded from './WidgetEmbedded'
import WidgetPopup from './WidgetPopup'

function PaymentMethodContent(props: PaymentMethodInterface & { data: any }) {
  const description = useMemo(() => decodeEntities(props.data.description), [props.data.description])

  return useMemo(() => {
    if (props.data.pp_mode === FrameMode.EMBEDDED && props.activePaymentMethod === PAYMENT_METHODS.CARD) {
      if (props.data.pp_version === PaymentPageVersion.MODERN) {
        return <WidgetEmbedded {...props} />
      }
      return <WidgetEmbeddedLegacy {...props} />
    }

    if (props.data.pp_mode === FrameMode.POPUP) {
      return <WidgetPopup {...props}>{description}</WidgetPopup>
    }

    return description
  }, [props])
}

export default PaymentMethodContent
