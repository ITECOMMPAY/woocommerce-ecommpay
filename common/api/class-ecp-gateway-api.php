<?php

defined('ABSPATH') || exit;

/**
 * <h2>Base ECOMMPAY Gate2025 API</h2>
 *
 * @class    Ecp_Gateway_API
 * @version  2.0.0
 * @package  Ecp_Gateway/Api
 * @category Class
 */
class Ecp_Gateway_API
{
    // region Constants

    /**
     * <h2>Default API protocol name.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    const PROTOCOL = 'https';

    /**
     * <h2>Default API host name.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    const HOST = 'api.ecommpay.com';

    /**
     * <h2>Default API protocol version.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    const VERSION = 'v2';

    // endregion

    // region Properties

    /**
     * <h2>cURL instance.</h2>
     *
     * @var ?resource
     * @since 2.0.0
     */
    private $curl;

    /**
     * <h2>The API url.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    private $api_url;

    /**
     * <h2>Request headers.</h2>
     *
     * @var string[]
     * @since 2.0.0
     */
    private $headers;

    /**
     * <h2>cURL options.</h2>
     *
     * @var array
     * @since 2.0.0
     */
    private $options = [];

    // endregion

    /**
     * <h2>API Constructor.</h2>
     *
     * @param string $append <p>Additional parameters to base API URL.</p>
     * @since 2.0.0
     */
    public function __construct($append = '')
    {
        $this->api_url = sprintf(
            '%s://%s/%s%s',
            $this->getProtocol(),
            $this->getHost(),
            $this->getVersion(),
            $append !== '' ? '/' . $append : ''
        );

        $this->headers = [
            'X-ECOMMPAY_PLUGIN' => Ecp_Gateway::WC_ECP_VERSION,
            'X-WORDPRESS' => wp_version(),
            'X-WOOCOMMERCE' => wc_version(),
        ];

        $this->hooks();
    }

    /**
     * <h2>Adds hooks and filters.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    protected function hooks()
    {
        add_filter('ecp_api_append_interface_type', [$this, 'filter_append_interface_type'], 10, 1);
        add_filter('ecp_api_append_signature', [$this, 'filter_append_signature'], 10, 1);
    }

    /**
     * <h2>Performs an API GET request.</h2>
     *
     * @param string $path <p>API request string.</p>
     * @since 2.0.0
     * @return array <p>Response data as array.</p>
     */
    final public function get($path)
    {
        // Start the request and return the response
        return $this->execute('GET', $path);
    }

    /**
     * <h2>Performs an API POST request.</h2>
     *
     * @param string $path <p>API request string.</p>
     * @param array $form [optional] <p>Form data for send. Default: blank array.</p>
     * @since 2.0.0
     * @return array <p>Response data as array.</p>
     */
    final public function post($path, $form = [])
    {
        // Start the request and return the response
        return $this->execute('POST', $path, $form);
    }

    /**
     * <h2>Performs an API PUT request.</h2>
     *
     * @param string $path <p>API request string.</p>
     * @param array $form [optional] <p>Form data for send. Default: blank array.</p>
     * @since 2.0.0
     * @return array <p>Response data as array.</p>
     */
    final public function put($path, $form = [])
    {
        // Start the request and return the response
        return $this->execute('PUT', $path, $form);
    }

    /**
     * <h2>Performs an API PATCH request.</h2>
     *
     * @param string $path <p>API request string.</p>
     * @param array $form [optional] <p>Form data for send. Default: blank array.</p>
     * @since 2.0.0
     * @return array <p>Response data as array.</p>
     */
    final public function patch($path, $form = [])
    {
        // Start the request and return the response
        return $this->execute('PATCH', $path, $form);
    }

    /**
     * <h2>Form data filter to add interface type parameter.</h2>
     *
     * @param array $data <p>Incoming form data.</p>
     * @since 2.0.0
     * @return array <p>Filtered form data.</p>
     */
    final public function filter_append_interface_type(array $data)
    {
        $data['interface_type'] = Ecp_Gateway::get_interface_type();

        return $data;
    }

    /**
     * <h2>Form data filter to add signature parameter.</h2>
     *
     * @param array $data <p>Incoming form data.</p>
     * @since 2.0.0
     * @return array <p>Filtered form data.</p>
     * @throws Ecp_Gateway_Signature_Exception <p>
     * When the key or value of one of the parameters contains the character
     * {@see Ecp_Gateway_Signer::VALUE_SEPARATOR} symbol.
     * </p>
     */
    final public function filter_append_signature(array $data)
    {
        $signature = ecp_get_signer()->get_signature($data);

        switch (true) {
            case array_key_exists(Ecp_Gateway_Signer::GENERAL, $data):
                ecp_get_log()->debug(__('Append signature to general data', 'woo-ecommpay'));
                $data[Ecp_Gateway_Signer::GENERAL][Ecp_Gateway_Signer::NAME] = $signature;
                break;
            default:
                ecp_get_log()->debug(__('Append signature to body data', 'woo-ecommpay'));
                $data[Ecp_Gateway_Signer::NAME] = $signature;
        }

        return $data;
    }

    /**
     * <h2>Takes an API request string and appends it to the API url.</h2>
     *
     * @param string $params <p>API request string.</p>
     * @since 2.0.0
     * @return static <p>Current object.</p>
     */
    public function set_url($params)
    {
        return $this->setOption(CURLOPT_URL, $this->api_url . '/' . trim($params, '/'));
    }

    // region Private methods

    /**
     * <h2>Executes the API request.</h2>
     *
     * @param string $request_type <p>The type of request being made.</p>
     * @param string $path <p>API request string.</p>
     * @param array $form [optional] <p>Form data for send. Default: blank array.</p>
     * @since 2.0.0
     * @return array <p>Response data as array.</p>
     */
    private function execute($request_type, $path, $form = [])
    {
        // Instantiate a new instance
        $this->init();
        $this->set_url($path)                   // Set the request params
            ->setRequestMethod($request_type)   // Set the HTTP request type
            ->setPostData($form)                // Set request POST data
            ->setHeaders();                     // Set additional headers

        // Set CURL request options
        curl_setopt_array($this->curl, $this->options);

        // Execute the request
        $data = curl_exec($this->curl);

        // Log and close request
        $this->log($request_type, $form, $data);
        curl_close($this->curl);

        $result = (int) curl_getinfo($this->curl, CURLINFO_HTTP_CODE) === 200
            // Parse and return response
            ? json_decode($data, true)
            // Error response
            : [
                Ecp_Gateway_Info_Status::FIELD_ERRORS => [[
                    Ecp_Gateway_Info_Error::FIELD_MESSAGE => 'Communication error',
                ]]
            ];

        if ($result !== null && !is_bool($result)) {
            return $result;
        }

        ecp_get_log()->warning(
            _x('JSON parse data with error: ', 'Log information', 'woo-ecommpay'),
            json_last_error_msg()
        );
        ecp_get_log()->info(
            _x('JSON source string data: ', 'Log information', 'woo-ecommpay'),
            $data
        );

        return [];
    }

    /**
     * <h2>Create a cURL instance if none exists already.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    private function init()
    {
        $this->curl = curl_init();

        $this->setOption(CURLOPT_RETURNTRANSFER, true)
            ->setOption(CURLOPT_SSL_VERIFYPEER, false)
            ->setOption(CURLOPT_HTTPAUTH, CURLAUTH_BASIC)
            ->setOption(CURLINFO_HEADER_OUT, true);

        $this->headers['Accept'] = 'application/json';
    }

    /**
     * <h2>Returns the ECOMMPAY Gate2025 API protocol name.</h2>
     *
     * @since 2.0.0
     * @return string <p>Protocol name.</p>
     */
    private function getProtocol()
    {
        $proto = getenv('ECP_PROTO');

        return is_string($proto) ? $proto : self::PROTOCOL;
    }

    /**
     * <h2>Returns the ECOMMPAY Gate2025 API host name.</h2>
     *
     * @since 2.0.0
     * @return string <p>Host name.</p>
     */
    private function getHost()
    {
        $host = getenv('ECP_GATE_HOST');

        return is_string($host) ? $host : self::HOST;
    }

    /**
     * <h2>Returns the ECOMMPAY Gate2025 API version.</h2>
     *
     * @since 2.0.0
     * @return string <p>API version.</b>
     */
    private function getVersion()
    {
        $version = getenv('ECP_GATE_VERSION');

        return is_string($version) ? $version : self::VERSION;
    }

    /**
     * <h2>Sets request type into cURL option.</h2>
     *
     * @param string $method <p>Request type.</p>
     * @since 2.0.0
     * @return static <p>Current object.</p>
     */
    private function setRequestMethod($method)
    {
        switch (strtolower($method)) {
            case 'get':
                $this->setOption(CURLOPT_HTTPGET, true);
                break;
            case 'post':
                $this->setOption(CURLOPT_POST, true);
                break;
            case 'put':
                $this->setOption(CURLOPT_PUT, true);
                break;
            case 'head':
                $this->setOption(CURLOPT_NOBODY, true);
                break;

            default:
                $this->setOption(CURLOPT_CUSTOMREQUEST, $method);
        }

        return $this;
    }

    /**
     * <h2>Sets headers into cURL options.</h2>
     *
     * @since 2.0.0
     * @return static <p>Current object.</p>
     * @noinspection PhpReturnValueOfMethodIsNeverUsedInspection
     */
    private function setHeaders()
    {
        if (count($this->headers) <= 0) {
            return $this;
        }

        $options = [];

        foreach ($this->headers as $key => $value) {
            $options[] = sprintf('%s%s %s', $key, ':', $value);
        }

        $this->setOption(CURLOPT_HTTPHEADER, $options);

        return $this;
    }

    /**
     * <h2>Sets POST-data into cURL option.</h2>
     *
     * @param array $data <p>Post data as key->value pairs.</p>
     * @since 2.0.0
     * @return static <p>Current object.</p>
     */
    private function setPostData($data)
    {
        // If additional data is delivered, we will send it along with the API request
        if (is_array($data) && !empty($data)) {
            // Prepare to post the data string
            $this->setOption(CURLOPT_POSTFIELDS, json_encode($data));
        }

        return $this;
    }

    /**
     * <h2>Adds new cURL option.</h2>
     *
     * @param string $key <p>Option key.</p>
     * @param mixed $value <p>Option value.</p>
     * @since 2.0.0
     * @return static <p>Current object.</p>
     */
    private function setOption($key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * <h2>Logs result of execution.<h2>
     *
     * @param string $request_type <p>Request type.</p>
     * @param array $request_data <p>Form data as array.</p>
     * @param string $response_data <p>Response raw data.</p>
     * @since 2.0.0
     * @return void
     */
    private function log($request_type, $request_data, $response_data)
    {
        ecp_get_log()->debug(__('~ START => [cURL Execution process]', 'woo-ecommpay'));
        ecp_get_log()->debug(__('Request URL:', 'woo-ecommpay'), curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL));
        ecp_get_log()->debug(__('Request type:', 'woo-ecommpay'), $request_type);
        ecp_get_log()->debug(__('Form data:', 'woo-ecommpay'), json_encode($request_data));
        ecp_get_log()->debug(__('Response code:', 'woo-ecommpay'), curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        ecp_get_log()->debug(__('Response raw:', 'woo-ecommpay'), $response_data);
        ecp_get_log()->debug(__('[cURL Execution process] => END ~', 'woo-ecommpay'));
    }

    // endregion
}
