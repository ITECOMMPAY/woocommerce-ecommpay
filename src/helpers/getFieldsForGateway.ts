const billingFields = [
  "billing_address",
  "billing_city",
  "billing_country",
  "billing_postal",
  "customer_first_name",
  "customer_last_name",
  "customer_phone",
  "customer_zip",
  "customer_address",
  "customer_city",
  "customer_country",
  "customer_email",
]

function getFieldsForGateway(data: object) {
  const fields: object = {}

  Object.keys(data).forEach((key: string) => {
    let name: string

    if (billingFields.includes(key)) {
      name = "BillingInfo[" + key + "]"
    } else {
      name = key
    }

    if (key === "billing_country") {
      fields["BillingInfo[country]"] = data[key]
    } else {
      fields[name] = data[key]
    }
  })

  return fields
}

export default getFieldsForGateway
