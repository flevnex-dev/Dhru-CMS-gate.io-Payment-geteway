<?php
// Ensure access is restricted to direct access
define("DEFINE_MY_ACCESS", true);
define("DEFINE_DHRU_FILE", true);

// Load necessary libraries
include 'comm.php';
require 'includes/fun.inc.php';
include 'includes/gateway.fun.php';
include 'includes/invoice.fun.php';

$GATEWAY = loadGatewayModule('gateio');

// Get POST data from Gate.io
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log incoming data for troubleshooting
// Logging callback request
file_put_contents('logs/gateio_callback.log', "Request Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents('logs/gateio_callback.log', "Headers: " . print_r(getallheaders(), true) . "\n", FILE_APPEND);
file_put_contents('logs/gateio_callback.log', "Received Data: " . print_r($data, true) . "\n", FILE_APPEND);
file_put_contents('logs/gateio_callback.log', "GATEWAY Received Data: " . print_r($GATEWAY, true) . "\n", FILE_APPEND);

// Decode the 'data' field, since it's a JSON string
$paymentData = json_decode($data['data'], true);

// Extract relevant fields from incoming data
$order_id = $paymentData['merchantTradeNo'] ?? ''; // Extracted from nested 'data'
$transaction_id = $paymentData['transactionId'] ?? ''; // Extracted from nested 'data'
$status = $data['bizStatus'] ?? ''; // Payment status
$timestamp = $paymentData['createTime'] ?? ''; // Updated to extract from payment data
$amount = (float)($paymentData['totalFee'] ?? 0); // Cast amount to float for safety

file_put_contents('logs/gateio_callback.log', "order_id=" . $order_id . "\n", FILE_APPEND);
file_put_contents('logs/gateio_callback.log', "transaction_id=" . $transaction_id . "\n", FILE_APPEND);
file_put_contents('logs/gateio_callback.log', "status=" . $status . "\n", FILE_APPEND);
file_put_contents('logs/gateio_callback.log', "timestamp=" . $timestamp . "\n", FILE_APPEND);
file_put_contents('logs/gateio_callback.log', "totalFee=" . $amount . "\n", FILE_APPEND);

if (!isset($order_id) || empty($order_id)) {
    logTransaction('Gate io Payment', json_encode($data) , 'Pending');
    $log_msg .= "Order ID not found". "\n";
    $data = [ 
        'message' => "Order ID not found",
        'status' => '2050'
        ];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);

    exit();
}

if (!isset($transaction_id) || empty($transaction_id)){
    logTransaction('Gate io Payment', json_encode($data) , 'Pending');

    $data = [ 
        'message' => "Transaction ID not found",
        'status' => '2050'
        ];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);

    exit();
}

// Perform actions based on payment status
if ($status == 'PAY_SUCCESS') {
    // Attempt to mark invoice as paid
    
    $pmnt = addPayment($order_id, $transaction_id, $amount, 0, 'Gate Pay');
    
    logTransaction('Gate io Payment successfully', json_encode($data), 'Payment Successful');

    // Check if the payment was added successfully
    if ($pmnt) {
        
        file_put_contents('logs/gateio_callback.log', "payment=" . $pmnt . "\n", FILE_APPEND);
        file_put_contents('logs/gateio_callback.log', "Payment marked as successful for Invoice ID: $order_id\n", FILE_APPEND);
        
    } else {
        // Log if addPayment failed
        file_put_contents('logs/gateio_callback.log', "Error: Payment not added for Invoice ID: $order_id\n", FILE_APPEND);
         error_log("Payment Details: TrxID=$transaction_id, MerchantInvoiceNumber=$order_id, Amount=$amount, Status=$status");
    }
    
} else {
    // Log payment failure
    error_log('Gate io Payment Failed', json_encode($data), "Payment Failed");
   
    file_put_contents('logs/gateio_callback.log', "Payment failed for Invoice ID: $order_id\n", FILE_APPEND);
}

// Return 200 OK status to acknowledge receipt
header("HTTP/1.1 200 OK");
