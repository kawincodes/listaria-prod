<?php

namespace App\Services\Payment;

use Auth;
use PhonePe\PhonePe as PhonePeSDK;
use Exception;
use Http;

class PhonePePayment implements PaymentInterface {
    private string $clientId;
    private string $merchantId;
    private string $callbackUrl;
    private string $transactionId;
    private string $clientSecret;
    private string $clientVersion;
    private string $payment_mode;
    private string $pgUrl;

    public function __construct($merchantId, $clientId, $addtional_data_1, $additional_data_2, $payment_mode) {
        $this->merchantId = $merchantId;
        $this->clientId = $clientId;
        $this->callbackUrl = url('/webhook/phonePe');
        $this->transactionId = uniqid();
        $this->clientSecret = $addtional_data_1;
        $this->clientVersion = $additional_data_2;
        $this->payment_mode = $payment_mode;
        $this->pgUrl = ($payment_mode == "UAT") ? "https://api-preprod.phonepe.com/apis/pg-sandbox" : "https://api.phonepe.com/apis/pg";
    }

    /**
     * Create payment intent for PhonePe
     *
     * @param $amount
     * @param $customMetaData
     * @return array
     * @throws Exception
     */
    public function createPaymentIntent($amount, $customMetaData) {
        $amount = $this->minimumAmountValidation('INR', $amount);
        $userMobile = Auth::user()->mobile;
        $metaData = 't' . '-' . $customMetaData['payment_transaction_id'] . '-' . 'p' . '-' . $customMetaData['package_id'];

        if ($customMetaData['platform_type'] == 'web') {
            $redirectUrl = route('phonepe.success.web');

            $transactionId = uniqid();
            // $phonepe = PhonePeSDK::init(
            //     $this->merchantId,
            //     $metaData,
            //     $this-> ,
            //     "1",
            //     $redirectUrl,
            //     $this->callbackUrl,
            //     "DEV"
            // );

            // $amountInPaisa = $amount * 100;
            // $redirectURL = $phonepe->standardCheckout()->createTransaction($amountInPaisa, $userMobile, $metaData)->getTransactionURL();

            if (!empty($redirectURL)) {
                return $this->formatPaymentIntent($transactionId, $amount, 'INR', 'pending', $customMetaData, $redirectURL);
            }
        } else {
            $redirectUrl = route('phonepe.success');
            $orderId = 'TX' . time(); // unique order ID
            $amount = 100; // amount in INR (not multiplied)
            $expireAfter = 1200; // in seconds (20 mins)
            $token = $this->getPhonePeToken();
            $order = $this->createOrder($token, $orderId, $amount);
            $order_data = json_decode($order, true);
            $requestPayload = [
                "orderId" => $order_data['orderId'],
                // "state"  => "PENDING",
                // "merchantOrderId" => $metaData,
                "merchantId" => $this->merchantId,
                "expireAT" => $expireAfter,
                "token" => $order_data['token'],
                "paymentMode" => [
                    "type" => "PAY_PAGE"
                ]
            ];

            // Convert to JSON string as required by Flutter SDK
            $requestString = json_encode($requestPayload);

            if ($this->payment_mode == "UAT") {
                $payment_mode = "SANDBOX";
            } else {
                $payment_mode = "PRODUCTION";
            }

            return [
                "environment" => $payment_mode, // or "PRODUCTION"
                "merchantId" => $this->merchantId,
                "flowId" => $orderId,
                "enableLogging" => true, // false in production
                "request" => $requestPayload,
                "appSchema" => "eclassify", // for iOS deep link return
                "token" => $token,
            ];

            // $payload = [
            //     "merchantId" => $this->merchantId,
            //     "merchantTransactionId" => $metaData,
            //     "merchantUserId" => $this->merchantId,
            //     "amount" => $amount * 100,
            //     "callbackUrl" => $this->callbackUrl,
            //     "redirectMode" => "REDIRECT",
            //     "mobileNumber" => $userMobile,
            //     "paymentInstrument" => [
            //         "type" => "PAY_PAGE"
            //     ]
            // ];

            // $encodedPayload = base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
            // $stringToHash = $encodedPayload . '/pg/v1/pay' . $this->saltKey;
            // $hash = hash('sha256', $stringToHash);
            // $checksum = $hash . '###' . 1;

            // return [
            //     "payload" => $payload,
            //     "checksum" => $checksum,
            //     "Phonepe_environment_mode" => 'SANDBOX',
            //     "merchent_id" => $this->merchantId,
            //     "appId" => 'Appid',
            //     "callback_url" => $this->callbackUrl
            // ];
        }

        // throw new Exception("Error initiating payment: " . $redirectURL);
    }

    /**
     * Create and format payment intent for PhonePe
     *
     * @param $amount
     * @param $customMetaData
     * @return array
     * @throws Exception
     */
    public function createAndFormatPaymentIntent($amount, $customMetaData): array {
        $paymentIntent = $this->createPaymentIntent($amount, $customMetaData);
        $metaData = 't' . '-' . $customMetaData['payment_transaction_id'] . '-' . 'p' . '-' . $customMetaData['package_id'];
        return $this->formatPaymentIntent(
            id: $metaData,
            amount: $amount,
            currency: 'INR',
            status: "PENDING",
            metadata: $customMetaData,
            paymentIntent: $paymentIntent
        );
    }

    /**
     * Retrieve payment intent (check payment status)
     *
     * @param $transactionId
     * @return array
     * @throws Exception
     */
    public function retrievePaymentIntent($transactionId): array {
        // $statusUrl = 'https://api.phonepe.com/v3/transaction/' . $transactionId . '/status';
        // $signature = $this->generateSignature(''); // Adjust if needed based on PhonePe requirements

        // $response = $this->sendRequest($statusUrl, '', $signature);

        // if ($response['success']) {
        //     return $this->formatPaymentIntent($transactionId, $response['amount'], 'INR', $response['status'], [], $response);
        // }

        // throw new Exception("Error fetching payment status: " . $response['message']);
    }

    /**
     * Format payment intent response
     *
     * @param $id
     * @param $amount
     * @param $currency
     * @param $status
     * @param $metadata
     * @param $paymentIntent
     * @return array
     */
    public function formatPaymentIntent($id, $amount, $currency, $status, $metadata, $paymentIntent): array {
        return [
            'id' => $id,
            'amount' => $amount,
            'currency' => $currency,
            'metadata' => $metadata,
            'status' => match ($status) {
                "SUCCESS" => "succeeded",
                "PENDING" => "pending",
                "FAILED" => "failed",
                default => "unknown"
            },
            'payment_gateway_response' => $paymentIntent
        ];
    }

    /**
     * Minimum amount validation
     *
     * @param $currency
     * @param $amount
     * @return float|int
     */
    public function minimumAmountValidation($currency, $amount) {
        $minimumAmount = match ($currency) {
            'INR' => 1.00, // 1 Rupee
            default => 0.50
        };

        return ($amount >= $minimumAmount) ? $amount : $minimumAmount;
    }

    /**
     * Generate HMAC signature for PhonePe
     *
     * @param $encodedRequestBody
     * @return string
     */
    //  private function generateSignature($requestBody): string
    // {
    //     // Concatenate raw JSON payload, endpoint, and salt key
    //     $stringToHash = $requestBody . '/pg/v1/pay' . $this->saltKey;

    //     // Hash the string using SHA256
    //     $hash = hash('sha256', $stringToHash);

    //     // Append salt index (Assumed to be 1 in this example)
    //     return $hash . '###' . 1;
    // }
    public function getPhonePeToken() {
        $clientId =  $this->clientId;
        $clientSecret = $this->clientSecret;
        $clientVersion = $this->clientVersion;

        $postData = http_build_query([
            'client_id' => $clientId,
            'client_version' => $clientVersion,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        if ($this->payment_mode == "UAT") {
            $url = $this->pgUrl . '/v1/oauth/token';
        } else {
            $url = $this->pgUrl . '/v1/oauth/token';
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($httpCode === 200 && isset($responseData['access_token'])) {
            return $responseData['access_token'];
        }

        throw new \Exception('Failed to fetch PhonePe token. Response: ' . $response);
    }

    public function createOrder($token, $merchantOrderId, $amount) {
        $url = $this->pgUrl . '/checkout/v2/sdk/order';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "merchantOrderId" => $merchantOrderId,
            "amount" => $amount,
            "paymentFlow" => [
                "type" => "PG_CHECKOUT"
            ]
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: O-Bearer ' . $token,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }


    /**
     * Send cURL request to PhonePe API
     *
     * @param $url
     * @param $requestBody
     * @param $signature
     * @return array
     */
}
