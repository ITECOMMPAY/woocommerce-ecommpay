export enum responseTypes {
  SUCCESS = "success",
  FAIL = "failure",
  ERROR = "error",
}

export enum noticeContexts {
  CART = "wc/cart",
  CHECKOUT = "wc/checkout",
  PAYMENTS = "wc/checkout/payments",
  EXPRESS_PAYMENTS = "wc/checkout/express-payments",
  CONTACT_INFORMATION = "wc/checkout/contact-information",
  SHIPPING_ADDRESS = "wc/checkout/shipping-address",
  BILLING_ADDRESS = "wc/checkout/billing-address",
  SHIPPING_METHODS = "wc/checkout/shipping-methods",
  CHECKOUT_ACTIONS = "wc/checkout/checkout-actions",
  ADDITIONAL_INFORMATION = "wc/checkout/additional-information",
}

export interface CurrencyInfo {
  currency_code: CurrencyCode
  currency_symbol: string
  currency_minor_unit: number
  currency_decimal_separator: string
  currency_thousand_separator: string
  currency_prefix: string
  currency_suffix: string
}

export interface CartTotalsItem extends CurrencyInfo {
  total_discount: string
  total_discount_tax: string
}

export interface CartCouponItem {
  code: string
  label: string
  discount_type: string
  totals: CartTotalsItem
}

export interface FirstNameLastName {
  first_name: string
  last_name: string
}

export interface BaseAddress {
  address_1: string
  address_2: string
  city: string
  state: string
  postcode: string
  country: string
}

export interface CartShippingPackageShippingRate extends CurrencyInfo {
  rate_id: string
  name: string
  description: string
  delivery_time: string
  price: string
  taxes: string
  instance_id: number
  method_id: string
  meta_data: Array<MetaKeyValue>
  selected: boolean
}

export interface CartShippingRate {
  package_id: string | number
  name: string
  destination: BaseAddress
  items: Array<ShippingRateItem>
  shipping_rates: Array<CartShippingPackageShippingRate>
}

export interface CartShippingAddress extends BaseAddress, FirstNameLastName {
  company: string
  phone: string
}

export interface CartBillingAddress extends CartShippingAddress {
  email: string
}

export interface CartImageItem {
  id: number
  src: string
  thumbnail: string
  srcset: string
  sizes: string
  name: string
  alt: string
}

export interface CartVariationItem {
  attribute: string
  value: string
}

export interface CartItemPrices extends CurrencyInfo {
  price: string
  regular_price: string
  sale_price: string
  price_range: null | { min_amount: string; max_amount: string }
  raw_prices: {
    precision: number
    price: string
    regular_price: string
    sale_price: string
  }
}

export interface CartItemTotals extends CurrencyInfo {
  line_subtotal: string
  line_subtotal_tax: string
  line_total: string
  line_total_tax: string
}

export type CatalogVisibility = "catalog" | "hidden" | "search" | "visible"

export interface CartItem {
  key: string
  id: number
  type: string
  quantity: number
  catalog_visibility: CatalogVisibility
  quantity_limits: {
    minimum: number
    maximum: number
    multiple_of: number
    editable: boolean
  }
  name: string
  summary: string
  short_description: string
  description: string
  sku: string
  low_stock_remaining: null | number
  backorders_allowed: boolean
  show_backorder_badge: boolean
  sold_individually: boolean
  permalink: string
  images: Array<CartImageItem>
  variation: Array<CartVariationItem>
  prices: CartItemPrices
  totals: CartItemTotals
  extensions: ExtensionsData
  item_data: ProductResponseItemData[]
}

export interface CartTotalsTaxLineItem {
  name: string
  price: string
  rate: string
}

export interface CartFeeItemTotals extends CurrencyInfo {
  total: string
  total_tax: string
}

export interface CartFeeItem {
  key: string
  id: string
  name: string
  totals: CartFeeItemTotals
}

export interface CartTotals extends CurrencyInfo {
  total_items: string
  total_items_tax: string
  total_fees: string
  total_fees_tax: string
  total_discount: string
  total_discount_tax: string
  total_shipping: string
  total_shipping_tax: string
  total_price: string
  total_tax: string
  tax_lines: Array<CartTotalsTaxLineItem>
}

export interface CartErrorItem {
  code: string
  message: string
}

export interface Cart extends Record<string, unknown> {
  coupons: Array<CartCouponItem>
  shippingRates: Array<CartShippingRate>
  shippingAddress: CartShippingAddress
  billingAddress: CartBillingAddress
  items: Array<CartItem>
  itemsCount: number
  itemsWeight: number
  crossSells: Array<ProductResponseItem>
  needsPayment: boolean
  needsShipping: boolean
  hasCalculatedShipping: boolean
  fees: Array<CartFeeItem>
  totals: CartTotals
  errors: Array<CartErrorItem>
  paymentMethods: Array<string>
  paymentRequirements: Array<string>
  extensions: ExtensionsData
}
export interface CartMeta {
  updatingCustomerData: boolean
  updatingSelectedRate: boolean
  isCartDataStale: boolean
  applyingCoupon: string
  removingCoupon: string
}
export interface ExtensionCartUpdateArgs {
  data: Record<string, unknown>
  namespace: string
}

export interface BillingAddressShippingAddress {
  billing_address: Partial<CartBillingAddress>
  shipping_address: Partial<CartShippingAddress>
}

export interface Currency {
  /**
   * ISO 4217 Currency Code
   */
  code: CurrencyCode
  /**
   * String which separates the decimals from the integer
   */
  decimalSeparator: string
  /**
   * @todo Description of this currently unknown
   */
  minorUnit: number
  /**
   * String to prefix the currency with.
   *
   * This property is generally exclusive with `suffix`.
   */
  prefix: string
  /**
   * String to suffix the currency with.
   *
   * This property is generally exclusive with `prefix`.
   */
  suffix: string
  /**
   * Currency symbol
   */
  symbol: string // @todo create a list of allowed currency symbols
  /**
   * String which separates the thousands
   */
  thousandSeparator: string
}

export interface CurrencyResponse {
  currency_code: CurrencyCode
  currency_symbol: string
  currency_minor_unit: number
  currency_decimal_separator: string
  currency_thousand_separator: string
  currency_prefix: string
  currency_suffix: string
}

export type SymbolPosition = "left" | "left_space" | "right" | "right_space"

export type CurrencyCode =
  | "AED"
  | "AFN"
  | "ALL"
  | "AMD"
  | "ANG"
  | "AOA"
  | "ARS"
  | "AUD"
  | "AWG"
  | "AZN"
  | "BAM"
  | "BBD"
  | "BDT"
  | "BGN"
  | "BHD"
  | "BIF"
  | "BMD"
  | "BND"
  | "BOB"
  | "BRL"
  | "BSD"
  | "BTC"
  | "BTN"
  | "BWP"
  | "BYR"
  | "BYN"
  | "BZD"
  | "CAD"
  | "CDF"
  | "CHF"
  | "CLP"
  | "CNY"
  | "COP"
  | "CRC"
  | "CUC"
  | "CUP"
  | "CVE"
  | "CZK"
  | "DJF"
  | "DKK"
  | "DOP"
  | "DZD"
  | "EGP"
  | "ERN"
  | "ETB"
  | "EUR"
  | "FJD"
  | "FKP"
  | "GBP"
  | "GEL"
  | "GGP"
  | "GHS"
  | "GIP"
  | "GMD"
  | "GNF"
  | "GTQ"
  | "GYD"
  | "HKD"
  | "HNL"
  | "HRK"
  | "HTG"
  | "HUF"
  | "IDR"
  | "ILS"
  | "IMP"
  | "INR"
  | "IQD"
  | "IRR"
  | "IRT"
  | "ISK"
  | "JEP"
  | "JMD"
  | "JOD"
  | "JPY"
  | "KES"
  | "KGS"
  | "KHR"
  | "KMF"
  | "KPW"
  | "KRW"
  | "KWD"
  | "KYD"
  | "KZT"
  | "LAK"
  | "LBP"
  | "LKR"
  | "LRD"
  | "LSL"
  | "LYD"
  | "MAD"
  | "MDL"
  | "MGA"
  | "MKD"
  | "MMK"
  | "MNT"
  | "MOP"
  | "MRU"
  | "MUR"
  | "MVR"
  | "MWK"
  | "MXN"
  | "MYR"
  | "MZN"
  | "NAD"
  | "NGN"
  | "NIO"
  | "NOK"
  | "NPR"
  | "NZD"
  | "OMR"
  | "PAB"
  | "PEN"
  | "PGK"
  | "PHP"
  | "PKR"
  | "PLN"
  | "PRB"
  | "PYG"
  | "QAR"
  | "RON"
  | "RSD"
  | "RUB"
  | "RWF"
  | "SAR"
  | "SBD"
  | "SCR"
  | "SDG"
  | "SEK"
  | "SGD"
  | "SHP"
  | "SLL"
  | "SOS"
  | "SRD"
  | "SSP"
  | "STN"
  | "SYP"
  | "SZL"
  | "THB"
  | "TJS"
  | "TMT"
  | "TND"
  | "TOP"
  | "TRY"
  | "TTD"
  | "TWD"
  | "TZS"
  | "UAH"
  | "UGX"
  | "USD"
  | "UYU"
  | "UZS"
  | "VEF"
  | "VES"
  | "VND"
  | "VUV"
  | "WST"
  | "XAF"
  | "XCD"
  | "XOF"
  | "XPF"
  | "YER"
  | "ZAR"
  | "ZMW"

export interface ProductResponseItemPrices extends CurrencyResponse {
  price: string
  regular_price: string
  sale_price: string
  price_range: null | { min_amount: string; max_amount: string }
}

export interface ProductResponseItemBaseData {
  value: string
  display?: string
  hidden?: boolean
  className?: string
}

export type ProductResponseItemData = ProductResponseItemBaseData &
  ({ key: string; name?: never } | { key?: never; name: string })

export interface ProductResponseImageItem {
  id: number
  src: string
  thumbnail: string
  srcset: string
  sizes: string
  name: string
  alt: string
}

export interface ProductResponseTermItem {
  default?: boolean
  id: number
  name: string
  slug: string
  link?: string
}

export interface ProductResponseAttributeItem {
  id: number
  name: string
  taxonomy: string
  has_variations: boolean
  terms: Array<ProductResponseTermItem>
}

export interface ProductResponseVariationsItem {
  id: number
  attributes: Array<ProductResponseVariationAttributeItem>
}

export interface ProductResponseVariationAttributeItem {
  name: string
  value: string
}

export interface ProductResponseItem {
  id: number
  name: string
  parent: number
  type: string
  variation: string
  permalink: string
  sku: string
  short_description: string
  description: string
  on_sale: boolean
  prices: ProductResponseItemPrices
  price_html: string
  average_rating: string
  review_count: number
  images: Array<ProductResponseImageItem>
  categories: Array<ProductResponseTermItem>
  tags: Array<ProductResponseTermItem>
  attributes: Array<ProductResponseAttributeItem>
  variations: Array<ProductResponseVariationsItem>
  has_options: boolean
  is_purchasable: boolean
  is_in_stock: boolean
  is_on_backorder: boolean
  low_stock_remaining: null | number
  sold_individually: boolean
  add_to_cart: {
    text: string
    description: string
    url: string
    minimum: number
    maximum: number
    multiple_of: number
  }
  slug: string
}

export interface CartResponseTotalsItem extends CurrencyResponse {
  total_discount: string
  total_discount_tax: string
}

export interface CartResponseCouponItem {
  code: string
  discount_type: string
  totals: CartResponseTotalsItem
}

export interface CartResponseCouponItemWithLabel
  extends CartResponseCouponItem {
  label: string
}

export type CartResponseCoupons = CartResponseCouponItemWithLabel[]

export interface ResponseFirstNameLastName {
  first_name: string
  last_name: string
}

export interface ResponseBaseAddress {
  address_1: string
  address_2: string
  city: string
  state: string
  postcode: string
  country: string
}

export interface ShippingRateItem {
  key: string
  name: string
  quantity: number
}

export interface MetaKeyValue {
  key: string
  value: string
}

export type ExtensionsData = Record<string, unknown> | Record<string, never>

export interface CartResponseShippingPackageShippingRate
  extends CurrencyResponse {
  rate_id: string
  name: string
  description: string
  delivery_time: string
  price: string
  taxes: string
  instance_id: number
  method_id: string
  meta_data: Array<MetaKeyValue>
  selected: boolean
}

export interface CartResponseShippingRate {
  /* PackageId can be a string, WooCommerce Subscriptions uses strings for example, but WooCommerce core uses numbers */
  package_id: number | string
  name: string
  destination: ResponseBaseAddress
  items: Array<ShippingRateItem>
  shipping_rates: Array<CartResponseShippingPackageShippingRate>
}

export interface CartResponseShippingAddress
  extends ResponseBaseAddress,
    ResponseFirstNameLastName {
  company: string
  phone: string
}

export interface CartResponseBillingAddress
  extends CartResponseShippingAddress {
  email: string
}

export interface CartResponseImageItem {
  id: number
  src: string
  thumbnail: string
  srcset: string
  sizes: string
  name: string
  alt: string
}

export interface CartResponseVariationItem {
  attribute: string
  value: string
}

export interface CartResponseItemPrices extends CurrencyResponse {
  price: string
  regular_price: string
  sale_price: string
  price_range: null | { min_amount: string; max_amount: string }
  raw_prices: {
    precision: number
    price: string
    regular_price: string
    sale_price: string
  }
}

export interface CartResponseItemTotals extends CurrencyResponse {
  line_subtotal: string
  line_subtotal_tax: string
  line_total: string
  line_total_tax: string
}

export type CartResponseItem = CartItem
export interface CartResponseTotalsTaxLineItem {
  name: string
  price: string
  rate: string
}

export interface CartResponseFeeItemTotals extends CurrencyResponse {
  total: string
  total_tax: string
}

export type CartResponseFeeItem = {
  id: string
  name: string
  totals: CartResponseFeeItemTotals
}

export interface CartResponseTotals extends CurrencyResponse {
  total_items: string
  total_items_tax: string
  total_fees: string
  total_fees_tax: string
  total_discount: string
  total_discount_tax: string
  total_shipping: string
  total_shipping_tax: string
  total_price: string
  total_tax: string
  tax_lines: Array<CartResponseTotalsTaxLineItem>
}

export interface CartResponseErrorItem {
  code: string
  message: string
}

export interface CartResponseExtensionItem {
  [key: string]: unknown
}

export interface CartResponse {
  coupons: Array<CartResponseCouponItem>
  shipping_rates: Array<CartResponseShippingRate>
  shipping_address: CartResponseShippingAddress
  billing_address: CartResponseBillingAddress
  items: Array<CartResponseItem>
  items_count: number
  items_weight: number
  cross_sells: Array<ProductResponseItem>
  needs_payment: boolean
  needs_shipping: boolean
  has_calculated_shipping: boolean
  fees: Array<CartResponseFeeItem>
  totals: CartResponseTotals
  errors: Array<CartResponseErrorItem>
  payment_methods: string[]
  payment_requirements: string[]
  extensions: ExtensionsData
}

export enum SHIPPING_ERROR_TYPES {
  NONE = "none",
  INVALID_ADDRESS = "invalid_address",
  UNKNOWN = "unknown_error",
}

export interface PreparedCartTotalItem {
  // The label for the total item.
  label: string
  // The value for the total item.
  value: number
}

export interface BillingDataProps {
  // All the coupons that were applied to the cart/order.
  appliedCoupons: CartResponseCouponItem[]
  // The address used for billing.
  billingData: CartBillingAddress
  billingAddress: CartBillingAddress
  // The total item for the cart.
  cartTotal: PreparedCartTotalItem
  // The various subtotal amounts.
  cartTotalItems: PreparedCartTotalItem[]
  // Currency object.
  currency: Currency
  // The customer Id the order belongs to.
  customerId: number
  // True means that the site is configured to display prices including tax.
  displayPricesIncludingTax: boolean
}

export interface CartDataProps {
  cartItems: CartResponseItem[]
  cartFees: CartResponseFeeItem[]
  extensions: ExtensionsData
}

export interface CheckoutStatusProps {
  // If true then totals are being calculated in the checkout.
  isCalculating: boolean
  // If true then the checkout has completed it's processing.
  isComplete: boolean
  // If true then the checkout is idle (no  activity happening).
  isIdle: boolean
  // If true then checkout is processing (finalizing) the order with the server.
  isProcessing: boolean
}

export interface ComponentProps {
  // A wrapper component used for showing a loading state when the isLoading prop is true.
  LoadingMask: any
  // A component used for displaying payment method icons.
  PaymentMethodIcons: any
  // A component used for displaying payment method labels, including an icon.
  PaymentMethodLabel: any
  // A container for holding validation errors
  ValidationInputError: any
}

export interface EmitResponseProps {
  // Response types that can be returned from emitter observers.
  responseTypes: typeof responseTypes
  // Available contexts that can be returned as the value for the messageContext property on the object  returned from an emitter observer.
  noticeContexts: typeof noticeContexts
}

export interface EventRegistrationProps {
  // Deprecated in favour of onCheckoutFail.
  onCheckoutAfterProcessingWithError: any
  // Deprecated in favour of onCheckoutSuccess.
  onCheckoutAfterProcessingWithSuccess: any
  // Used to subscribe callbacks firing before checkout begins processing.
  onCheckoutBeforeProcessing: any
  // Used to register a callback that will fire if the api call to /checkout is successful
  onCheckoutSuccess: any
  // Used to register a callback that will fire if the api call to /checkout fails
  onCheckoutFail: any
  // Used to register a callback that will fire when the checkout performs validation on the form
  onCheckoutValidation: any
  // Deprecated in favour of onCheckoutValidation.
  onCheckoutValidationBeforeProcessing: any
  // Deprecated in favour of onPaymentSetup
  onPaymentProcessing: any
  // Event registration callback for registering observers for the payment setup event.
  onPaymentSetup: any
  // Used to subscribe callbacks that will fire when retrieving shipping rates failed.
  onShippingRateFail: any
  // Used to subscribe callbacks that will fire after selecting a shipping rate unsuccessfully.
  onShippingRateSelectFail: any
  // Used to subscribe callbacks that will fire after selecting a shipping rate successfully.
  onShippingRateSelectSuccess: any
  // Used to subscribe callbacks that will fire when shipping rates for a given address have been received successfully.
  onShippingRateSuccess: any
}

export interface ShippingDataProps {
  // True when rates are being selected.
  isSelectingRate: boolean
  // True if cart requires shipping.
  needsShipping: boolean
  // An object containing package IDs as the key and selected rate as the value (rate ids).
  selectedRates: Record<string, unknown>
  // A function for setting selected rates (receives id).
  setSelectedRates: (
    newShippingRateId: string,
    packageId: string | number
  ) => unknown
  // A function for setting the shipping address.
  setShippingAddress: (data: CartResponseShippingAddress) => void
  // The current set shipping address.
  shippingAddress: CartResponseShippingAddress
  // All the available shipping rates.
  shippingRates: CartShippingRate[]
  // Whether the rates are loading or not.
  shippingRatesLoading: boolean
}

export interface ShippingStatusProps {
  // Current error status for shipping.
  shippingErrorStatus: {
    // Whether the status is pristine.
    isPristine: boolean
    // Whether the status is valid.
    isValid: boolean
    // Whether the address is invalid.
    hasInvalidAddress: boolean
    // Whether an error has happened.
    hasError: boolean
  }
  // An object containing all the possible types for shipping error status.
  shippingErrorTypes: SHIPPING_ERROR_TYPES
}

export type PaymentMethodInterface = {
  // Indicates what the active payment method is.
  activePaymentMethod: string
  // Various billing data items.
  billing: BillingDataProps
  // Data exposed from the cart including items, fees, and any registered extension data. Note that this data should
  // be treated as immutable (should not be modified/mutated) or it will result in errors in your application.
  cartData: CartDataProps
  // The current checkout status exposed as various boolean state.
  checkoutStatus: CheckoutStatusProps
  // Components exposed to payment methods for use.
  components: ComponentProps
  // Utilities for usage in event observer response objects.
  emitResponse: EmitResponseProps
  // Various event registration helpers for subscribing callbacks for events.
  eventRegistration: EventRegistrationProps
  // Used to trigger checkout processing.
  onSubmit: () => void
  // Various payment status helpers.
  paymentStatus: {
    isPristine: boolean
    isIdle: boolean
    isStarted: boolean
    isProcessing: boolean
    isFinished: boolean
    hasError: boolean
    hasFailed: boolean
    isSuccessful: boolean
    isDoingExpressPayment: boolean
  }
  // Deprecated. For setting an error (error message string) for express payment methods. Does not change payment status.
  setExpressPaymentError: (errorMessage?: string) => void
  // Various data related to shipping.
  shippingData: ShippingDataProps
  // Various shipping status helpers.
  shippingStatus: ShippingStatusProps
  // A boolean which indicates whether the shopper has checked the save payment method checkbox.
  shouldSavePayment: boolean
}
