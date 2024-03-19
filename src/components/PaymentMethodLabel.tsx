import { decodeEntities } from "@wordpress/html-entities"

function PaymentMethodLabel(props: { data: any }) {
  return (
    <div
      style={{
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        gap: "10px",
      }}
    >
      {decodeEntities(props.data.title)}
      {props.data.icon && <img src={props.data.icon} alt="" />}
    </div>
  )
}

export default PaymentMethodLabel
