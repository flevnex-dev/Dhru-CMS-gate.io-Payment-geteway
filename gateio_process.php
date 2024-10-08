<?php
// Start output buffering at the beginning of the script
ob_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $params = [
        'invoiceid' => $_POST['invoiceid'],
        'amount' => $_POST['amount'],
        'currency' => $_POST['currency'],
        'api_key' => $_POST['api_key'],
        'secret_key' => $_POST['secret_key'],
        'client_id' => $_POST['client_id'],
        'returnurl' => $_POST['returnurl'],
        'callbackURL' => $_POST['callbackURL']
    ];

    createGateIoPayment($params);
}

function createGateIoPayment($params)
{
    $client_id = $params['client_id'];
    $api_key = $params['api_key'];
    $secret_key = $params['secret_key'];
    $invoiceid = $params['invoiceid'];
    $invoicetotal = number_format($params['amount'], 2, '.', '');
    $currency = 'USDT';  // Correct currency
    $callback_url = $params['systemurl'] . 'gatecallback.php';  // Callback URL for Gate.io webhook
    $return_url = $params['returnurl'];  // Redirect URL on success
    $cancel_url = $params['returnurl'] . '&paymentfailed=true';  // Redirect URL on failure

    $timestamp = time() * 1000;
    $nonce = bin2hex(random_bytes(16));

    // Data for the POST request
    $data = [
        'merchantTradeNo' => $invoiceid,
        'env' => ['terminalType' => 'APP'],
        'currency' => $currency,  // Set to USDT
        'orderAmount' => $invoicetotal,
        'merchantUserId' => 17455498,  // Adjust to actual merchant ID
        'goods' => [
            'goodsType' => '02',
            'goodsName' => 'Order Payment - ' . $invoiceid,
            'goodsDetail' => 'Invoice No: ' . $invoiceid
        ],
        'returnUrl' => $return_url,
        'cancelUrl' => $cancel_url,
        'chain' => 'TRX',  
        'fullCurrType' => 'USDT_TRX'
    ];

    // Convert data to JSON format
    $payload_json = json_encode($data);

    // Generate the signature
    $signature = generateSignature($timestamp, $nonce, $payload_json, $api_key);

    // cURL POST request to Gate.io
    $ch = curl_init('https://openplatform.gateapi.io/v1/pay/checkout/order');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-GatePay-Certificate-ClientId: ' . $client_id,
        'X-GatePay-Timestamp: ' . $timestamp,
        'X-GatePay-Nonce: ' . $nonce,
        'X-GatePay-Signature: ' . $signature
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_json);

    // Execute request and get response
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        // Decode the JSON response
        $responseData = json_decode($response, true);

        // Check if the request was successful and redirect to payment URL
        if (isset($responseData['status']) && $responseData['status'] === 'SUCCESS') {
            $location = $responseData['data']['location']; // Payment URL from the response
            header("Location: $location");  // Redirect to the payment URL
            exit();  // Ensure the script stops after redirecting
        } else {
            echo 'Payment creation failed. Response: ' . $response;
        }
    }

    curl_close($ch);
}

function generateSignature($timestamp, $nonce, $body, $secretKey) {
    $payload = "$timestamp\n$nonce\n$body\n";
    $signature = hash_hmac('sha512', $payload, $secretKey, true);
    return bin2hex($signature);
}

// End output buffering and flush output
ob_end_flush();
