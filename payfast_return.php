<?php
session_start();
require_once 'db_connection.php'; 


function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


$pfData = $_GET;


error_log("PayFast Return Data: " . print_r($pfData, true));


$pfParamMerchantId = isset($pfData['merchant_id']) ? sanitize($pfData['merchant_id']) : '';
$pfParamMerchantKey = 'YOUR_PAYFAST_MERCHANT_KEY'; 
$pfParamPaymentId = isset($pfData['pf_payment_id']) ? sanitize($pfData['pf_payment_id']) : '';
$pfParamPaymentStatus = isset($pfData['payment_status']) ? sanitize($pfData['payment_status']) : '';
$pfParamAmountGross = isset($pfData['amount_gross']) ? sanitize($pfData['amount_gross']) : '';
$pfParamItemId = isset($pfData['item_id']) ? sanitize($pfData['item_id']) : ''; 
$pfParamMPaymentId = isset($pfData['m_payment_id']) ? sanitize($pfData['m_payment_id']) : ''; 


$signature = isset($pfData['signature']) ? sanitize($pfData['signature']) : '';

$stringToHash = '';
$stringToHash .= (isset($pfData['m_payment_id'])) ? $pfData['m_payment_id'] : '';
$stringToHash .= (isset($pfData['pf_payment_id'])) ? $pfData['pf_payment_id'] : '';
$stringToHash .= (isset($pfData['payment_status'])) ? $pfData['payment_status'] : '';
$stringToHash .= (isset($pfData['item_name'])) ? $pfData['item_name'] : '';
$stringToHash .= (isset($pfData['item_description'])) ? $pfData['item_description'] : '';
$stringToHash .= (isset($pfData['amount_gross'])) ? $pfData['amount_gross'] : '';
$stringToHash .= (isset($pfData['amount_fee'])) ? $pfData['amount_fee'] : '';
$stringToHash .= (isset($pfData['amount_net'])) ? $pfData['amount_net'] : '';
$stringToHash .= (isset($pfData['custom_int1'])) ? $pfData['custom_int1'] : '';
$stringToHash .= (isset($pfData['custom_int2'])) ? $pfData['custom_int2'] : '';
$stringToHash .= (isset($pfData['custom_str1'])) ? $pfData['custom_str1'] : '';
$stringToHash .= (isset($pfData['custom_str2'])) ? $pfData['custom_str2'] : '';
$stringToHash .= $pfParamMerchantKey;

$generatedSignature = md5($stringToHash);

if ($signature === $generatedSignature) {
   
    if ($pfParamPaymentStatus === 'COMPLETE') {
        
        $orderId = $pfParamMPaymentId;

        try {
     
            $stmt = $pdo->prepare("UPDATE orders SET order_status = 'completed', transaction_id = ? WHERE order_id = ?");
            $stmt->execute([$pfParamPaymentId, $orderId]);

         
            header("Location: order-confirmation.php?order_id=" . urlencode($orderId));
            exit();

        } catch (PDOException $e) {
            error_log("Database error updating order: " . $e->getMessage());
            $_SESSION['payment_error'] = "An error occurred while updating your order status.";
            header("Location: payment-failed.php"); 
            exit();
        }

    } else if ($pfParamPaymentStatus === 'FAILED' || $pfParamPaymentStatus === 'CANCELLED') {
       
        $orderId = $pfParamMPaymentId;

    
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->execute([strtolower($pfParamPaymentStatus), $orderId]);

        $_SESSION['payment_error'] = "Your PayFast payment was " . strtolower($pfParamPaymentStatus) . ". Please try again if needed.";
        header("Location: payment-failed.php");
        exit();

    } else if ($pfParamPaymentStatus === 'PENDING') {
  
        $_SESSION['payment_info'] = "Your PayFast payment is currently pending. You will be notified of the final status.";
        header("Location: payment-pending.php"); 
        exit();
    } else {
  
        error_log("Unknown PayFast payment status: " . $pfParamPaymentStatus);
        $_SESSION['payment_error'] = "An unknown error occurred with your PayFast payment.";
        header("Location: payment-failed.php");
        exit();
    }

} else {
 
    error_log("PayFast Signature Mismatch. Received: " . $signature . ", Generated: " . $generatedSignature);
    $_SESSION['payment_error'] = "Security error: Invalid payment signature.";
    header("Location: payment-failed.php");
    exit();
}

?>