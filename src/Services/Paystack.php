<?php

/*
 * (c) Akinwunmi Damilola <akinwunmi.damilola@yahoo.com>
 */

namespace SbscPackage\Ecommerce\Services;

use GuzzleHttp\Client;
use SbscPackage\Ecommerce\Services\TransRef;
use Exception;

class Paystack
{
    /**
     * Transaction Verification Successful
     */
    const VS = 'Verification successful';

    /**
     *  Invalid Transaction reference
     */
    const ITF = "Invalid transaction reference";

    /**
     * Issue Secret Key from your Paystack Dashboard
     * @var string
     */
    protected $secretKey;

    /**
     * Instance of Client
     * @var Client
     */
    protected $client;

    /**
     *  Response from requests made to Paystack
     * @var mixed
     */
    protected $response;

    /**
     * Paystack API base Url
     * @var string
     */
    protected $baseUrl;

    /**
     * Authorization Url - Paystack payment page
     * @var string
     */
    protected $authorizationUrl;

    public function __construct()
    {
        $this->setKey();
        $this->setBaseUrl();
        $this->setRequestOptions();
    }

    /**
     * Get Base Url from Paystack config file
     */
    public function setBaseUrl()
    {
        $this->baseUrl = env("PAYSTACK_PAYMENT_URL", "https://api.paystack.co");
    }

    /**
     * Get secret key from Paystack config file
     */
    public function setKey()
    {
        $this->secretKey = env("PAYSTACK_SECRET_KEY");
    }

    /**
     * Set options for making the Client request
     */
    private function setRequestOptions()
    {
        $authBearer = 'Bearer ' . $this->secretKey;

        $this->client = new Client(
            [
                'base_uri' => $this->baseUrl,
                'headers' => [
                    'Authorization' => $authBearer,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json'
                ]
            ]
        );
    }


    /**
     * Initiate a payment request to Paystack
     * Included the option to pass the payload to this method for situations
     * when the payload is built on the fly (not passed to the controller from a view)
     * @return Paystack
     */

    public function makePaymentRequest($data)
    {
        $this->setHttpResponse('/transaction/initialize', 'POST', $data);

        return $this;
    }

    public function mobileMoney($data)
    {
        $this->setHttpResponse('/charge', 'POST', $data);

        return $this->getResponse();
    }
    /**
     * @param string $relativeUrl
     * @param string $method
     * @param array $body
     * @return Paystack
     * @throws IsNullException
     */
    private function setHttpResponse($relativeUrl, $method, $body = [])
    {
        $this->response = $this->client->{strtolower($method)}(
            $this->baseUrl . $relativeUrl,
            ["body" => json_encode($body)]
        );

        return $this;
    }

    /**
     * Get the authorization callback response
     * In situations where Laravel serves as an backend for a detached UI, the api cannot redirect
     * and might need to take different actions based on the success or not of the transaction
     * @return array
     */
    public function getAuthorizationResponse($data)
    {
        $this->makePaymentRequest($data);

        $this->url = $this->getResponse()['data']['authorization_url'];

        return $this->getResponse();
    }

    /**
     * Hit Paystack Gateway to Verify that the transaction is valid
     */
    private function verifyTransactionAtGateway()
    {
        $transactionRef = request()->trxref;

        $relativeUrl = "/transaction/verify/{$transactionRef}";

        $this->response = $this->client->get($this->baseUrl . $relativeUrl, []);
    }

    /**
     * True or false condition whether the transaction is verified
     * @return boolean
     */
    public function isTransactionVerificationValid()
    {
        $this->verifyTransactionAtGateway();

        $result = $this->getResponse()['message'];

        switch ($result) {
            case self::VS:
                $validate = true;
                break;
            case self::ITF:
                $validate = false;
                break;
            default:
                $validate = false;
                break;
        }

        return $validate;
    }

    /**
     * Get Payment details if the transaction was verified successfully
     * @return json
     */
    public function getPaymentData()
    {
        $this->verifyTransactionAtGateway();

        return $this->getResponse();
    }


    /**
     * Get Access code from transaction callback respose
     * @return string
     */
    public function getAccessCode()
    {
        return $this->getResponse()['data']['access_code'];
    }

    /**
     * Generate a Unique Transaction Reference
     * @return string
     */
    public function genTranxRef()
    {
        return TransRef::getHashedToken();
    }

    /**
     * Get the whole response from a get operation
     * @return array
     */
    private function getResponse()
    {
        return json_decode($this->response->getBody(), true);
    }

    /**
     * Get the data response from a get operation
     * @return array
     */
    private function getData()
    {
        return $this->getResponse()['data'];
    }

    public static function verifyTransaction($reference)
    {
        // generate access token
        $baseUrl = env('PAYSTACK_PAYMENT_URL');
        $url = $baseUrl.'/transaction/verify/'.$reference;

        $headerParams = [
            'Authorization: Bearer '. env('PAYSTACK_SECRET_KEY'),
        ];

        // Make post request
        return $result = CurlService::getRequest($url, $headerParams);
    }

    public static function chargeAuthorization($request)
    {
        // generate access token
        $baseUrl = env('PAYSTACK_PAYMENT_URL');
        $url = $baseUrl.'/transaction/charge_authorization';

        $headerParams = [
            'Authorization: Bearer '. env('PAYSTACK_SECRET_KEY'),
        ];

        // Make post request
        return $result = CurlService::postRequest($url, $request, $headerParams);
    }


    public static function createPlan($request)
    {
        // generate access token
        $baseUrl = env('PAYSTACK_PAYMENT_URL');
        $url = $baseUrl.'/plan';

        $headerParams = [
            'Authorization: Bearer '. env('PAYSTACK_SECRET_KEY'),
        ];

        // Make post request
        return $result = CurlService::postRequest($url, $request, $headerParams);
    }

    public static function createProduct($request)
    {
        // generate access token
        $baseUrl = env('PAYSTACK_PAYMENT_URL');
        $url = $baseUrl.'/product';

        $headerParams = [
            'Authorization: Bearer '. env('PAYSTACK_SECRET_KEY'),
        ];

        // Make post request
        return $result = CurlService::postRequest($url, $request, $headerParams);
    }

    public static function updatePlan($request, $planCode)
    {
        // generate access token
        $baseUrl = env('PAYSTACK_PAYMENT_URL');
        $url = $baseUrl."/plan"."/".$planCode;

        $headerParams = [
            'Authorization: Bearer '. env('PAYSTACK_SECRET_KEY'),
        ];

        // Make post request
        return $result = CurlService::putRequest($url, $request, $headerParams);
    }

    public static function createCustomer($request)
    {
        // generate access token
        $baseUrl = env('PAYSTACK_PAYMENT_URL');
        $url = $baseUrl.'/customer';

        $headerParams = [
            'Authorization: Bearer '. env('PAYSTACK_SECRET_KEY'),
        ];

        // Make post request
        return $result = CurlService::postRequest($url, $request, $headerParams);
    }

    public static function refundTransaction($request)
    {
        // generate access token
        $baseUrl = env('PAYSTACK_PAYMENT_URL');
        $url = $baseUrl.'/refund';

        $headerParams = [
            'Authorization: Bearer '. env('PAYSTACK_SECRET_KEY'),
        ];

        // Make post request
        return $result = CurlService::postRequest($url, $request, $headerParams);
    }
}
