import { useMemo } from "@wordpress/element"
import { decodeEntities } from "@wordpress/html-entities"
import type { PaymentMethodInterface } from "../woocommerce-types"
import WidgetEmbedded from "./WidgetEmbedded"
import WidgetPopup from "./WidgetPopup"

function PaymentMethodContent(props: PaymentMethodInterface & { data: any }) {
  const description = useMemo(
    () => decodeEntities(props.data.description),
    [props.data.description]
  )

  const content = useMemo(() => {
    if (
      props.data.pp_mode === "embedded" &&
      props.activePaymentMethod === "ecommpay-card"
    ) {
      return <WidgetEmbedded {...props} />
    }

    if (props.data.pp_mode === "popup") {
      return <WidgetPopup {...props}>{description}</WidgetPopup>
    }

    return description
  }, [props])

  return content
}

export default PaymentMethodContent
