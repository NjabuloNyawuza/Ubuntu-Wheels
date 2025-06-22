<?php
session_start();
require_once 'db_connection.php'; 


function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


$selectedPaymentMethod = $_POST['payment-method'] ?? 'credit-card'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  
    switch ($selectedPaymentMethod) {
        case 'credit-card':
         
            $cardName = sanitize($_POST['card-name']) ?? '';
            $cardNumber = sanitize($_POST['card-number']) ?? '';
            $expiryDate = sanitize($_POST['expiry-date']) ?? '';
            $cvv = sanitize($_POST['cvv']) ?? '';

        
            $errors = [];
            if (empty($cardName)) $errors[] = "Cardholder Name is required.";
            if (empty($cardNumber) || !preg_match('/^[0-9\s]+$/', $cardNumber)) $errors[] = "Please enter a valid Card Number.";
            if (empty($expiryDate) || !preg_match('/^\d{2}\/\d{2}$/', $expiryDate)) $errors[] = "Please enter a valid Expiry Date (MM/YY).";
            if (empty($cvv) || !preg_match('/^\d{3,4}$/', $cvv)) $errors[] = "Please enter a valid CVV.";

            if (!empty($errors)) {
                $_SESSION['payment_errors'] = $errors;
                header('Location: payment.php');
                exit();
            }

          
            $paymentSuccessful = true;
            $transactionId = uniqid('cc_'); 

            if ($paymentSuccessful) {
                processOrder($pdo, $transactionId, 'credit-card');
            } else {
                $_SESSION['payment_error'] = "Credit card payment failed. Please try again.";
                header('Location: payment.php');
                exit();
            }
            break;

        case 'payfast':
           
            $merchantId = 'YOUR_PAYFAST_MERCHANT_ID'; 
            $merchantKey = 'YOUR_PAYFAST_MERCHANT_KEY'; 
            $amount = number_format(($_SESSION['grand_total'] ?? 0), 2); 
            $itemName = 'MarketPlace Order'; 
            $itemDescription = 'Order from MarketPlace';
            $returnUrl = 'https://yourwebsite.com/payfast_return.php'; 
            $cancelUrl = 'https://yourwebsite.com/payment.php?payment_cancelled=true'; 
            $notifyUrl = 'https://yourwebsite.com/payfast_notify.php'; 
            $timestamp = date('Y-m-d H:i:s');
            $transactionId = uniqid('pf_');

         
            $signatureString = 'amount=' . $amount . '&item_name=' . urlencode($itemName) . '&merchant_id=' . $merchantId . '&merchant_key=' . $merchantKey . '&return_url=' . urlencode($returnUrl) . '&timestamp=' . $timestamp;
            $signature = md5($signatureString);

   
            $payFastRedirectURL = 'https://www.payfast.co.za/eng/process?' .
                                  'merchant_id=' . urlencode($merchantId) .
                                  '&merchant_key=' . urlencode($merchantKey) .
                                  '&amount=' . urlencode($amount) .
                                  '&item_name=' . urlencode($itemName) .
                                  '&item_description=' . urlencode($itemDescription) .
                                  '&return_url=' . urlencode($returnUrl) .
                                  '&cancel_url=' . urlencode($cancelUrl) .
                                  '&notify_url=' . urlencode($notifyUrl) .
                                  '&timestamp=' . urlencode($timestamp) .
                                  '&signature=' . urlencode($signature) .
                                  '&m_payment_id=' . urlencode($transactionId); 

      
            header('Location: ' . $payFastRedirectURL);
            exit();
            break;

        case 'yoco':
            
            $yocoSecretKey = 'YOUR_YOCO_SECRET_KEY'; 
            $amountInCents = round(($_SESSION['grand_total'] ?? 0) * 100); 
            $currency = 'ZAR';
            $returnUrl = 'https://yourwebsite.com/yoco_return.php'; 
            $transactionId = uniqid('yc_');

        
            $yocoPaymentPageURL = 'https://pay.yoco.com/checkout/' . $yocoSecretKey . '?amount=' . $amountInCents . '&currency=' . urlencode($currency) . '&transaction_id=' . urlencode($transactionId) . '&redirect_url=' . urlencode($returnUrl);

   
            header('Location: ' . $yocoPaymentPageURL);
            exit();
            break;

        default:
     
            $_SESSION['payment_error'] = "Invalid payment method selected.";
            header('Location: payment.php');
            exit();
    }
} else {

    header('Location: payment.php');
    exit();
}


function processOrder($pdo, $transactionId, $paymentMethod) {
    try {
        $pdo->beginTransaction();

    
        $shippingInfo = $_SESSION['checkout']['shipping'] ?? [];
        $totalAmount = $_SESSION['grand_total'] ?? 0; 
        $subtotal = $_SESSION['subtotal'] ?? 0;
        $shippingCost = $_SESSION['checkout']['shipping_cost'] ?? 0;
        $tax = $_SESSION['tax'] ?? 0; 

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, first_name, last_name, email, address, address2, city, state, zip, country, phone, shipping_method, subtotal, shipping_cost, tax, total_amount, order_date, order_status, transaction_id, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'processing', ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $shippingInfo['first_name'] ?? '',
            $shippingInfo['last_name'] ?? '',
            $shippingInfo['email'] ?? '',
            $shippingInfo['address'] ?? '',
            $shippingInfo['address2'] ?? '',
            $shippingInfo['city'] ?? '',
            $shippingInfo['state'] ?? '',
            $shippingInfo['zip'] ?? '',
            $shippingInfo['country'] ?? '',
            $shippingInfo['phone'] ?? '',
            $_SESSION['shipping_method'] ?? 'Standard',
            $subtotal,
            $shippingCost,
            $tax,
            $totalAmount,
            $transactionId,
            $paymentMethod
        ]);

        $orderId = $pdo->lastInsertId();

      
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $productId => $item) {
                $stmt->execute([$orderId, $productId, $item['quantity'], $item['price']]);
            }
        }

    
        $_SESSION['cart'] = [];
        unset($_SESSION['checkout']);
        unset($_SESSION['shipping_method']);
        unset($_SESSION['applied_promo']);
        unset($_SESSION['grand_total']);
        unset($_SESSION['subtotal']);
        unset($_SESSION['tax']);

   
        $pdo->commit();

   
        $_SESSION['order_id'] = $orderId;
        header('Location: order-confirmation.php');
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error processing order: " . $e->getMessage());
        $_SESSION['payment_error'] = "An error occurred while finalizing your order. Please try again.";
        header('Location: payment.php');
        exit();
    }
}
?>