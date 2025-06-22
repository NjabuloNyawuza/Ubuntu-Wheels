<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db_connection.php';


echo "\n";
echo "\n";
echo "\n";
echo "\n";
echo "\n";
if (!empty($_POST)) {
    echo "\n";
} else {
    echo "\n";
}



if (empty($_SESSION['cart'])) {

    echo "\n";
 
    header('Location: cart.php');
    exit;
}


$cartItems = [];
$sellers = [];
$subtotal = 0;

if (!empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
  
    if (!empty($productIds)) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        try {
            $stmt = $pdo->prepare("
                SELECT p.ProductID, p.ProductName, p.Price, p.ImageURL, p.SellerID, p.`Condition`, 
                       u.name as SellerName, u.rating as SellerRating,
                       u.location as SellerLocation
                FROM Products p
                LEFT JOIN users u ON p.SellerID = u.id
                WHERE p.ProductID IN ($placeholders)
            ");
            
            $stmt->execute($productIds);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as $product) {
                $productId = $product['ProductID'];
                $quantity = $_SESSION['cart'][$productId];
                $price = $product['Price'];
                $itemTotal = $price * $quantity;
                $subtotal += $itemTotal;
                
                $cartItems[$productId] = [
                    'product_id' => $productId,
                    'name' => $product['ProductName'],
                    'price' => $price,
                    'quantity' => $quantity,
                    'image' => $product['ImageURL'],
                    'seller_id' => $product['SellerID'],
                    'seller_name' => $product['SellerName'],
                    'seller_rating' => $product['SellerRating'],
                    'seller_location' => $product['SellerLocation'],
                    'condition' => $product['Condition'],
                    'item_total' => $itemTotal
                ];
             
                if (!isset($sellers[$product['SellerID']])) {
                    $sellers[$product['SellerID']] = [
                        'seller_id' => $product['SellerID'],
                        'seller_name' => $product['SellerName'],
                        'seller_rating' => $product['SellerRating'],
                        'seller_location' => $product['SellerLocation'],
                        'items' => [],
                        'seller_total' => 0
                    ];
                }
                
                $sellers[$product['SellerID']]['items'][$productId] = $cartItems[$productId];
                $sellers[$product['SellerID']]['seller_total'] += $itemTotal;
            }
        } catch (PDOException $e) {
        
            $_SESSION['order_errors'][] = "Database error: " . $e->getMessage();
        }
    }
}


$shipping = 0;
$shippingOptions = [
    'standard' => ['name' => 'Standard Shipping', 'price' => 50, 'days' => '3-5'],
    'express' => ['name' => 'Express Shipping', 'price' => 100, 'days' => '1-2'],
    'free' => ['name' => 'Free Shipping', 'price' => 0, 'days' => '5-7']
];


$selectedShipping = isset($_SESSION['shipping']) && isset($shippingOptions[$_SESSION['shipping']]) ? $_SESSION['shipping'] : 'standard';
$shipping = $shippingOptions[$selectedShipping]['price'];


$taxRate = 0.15;
$tax = $subtotal * $taxRate;


$total = $subtotal + $shipping + $tax;


$orderErrors = []; 


$displayErrors = [];
if (isset($_SESSION['order_errors']) && !empty($_SESSION['order_errors'])) {
    $displayErrors = $_SESSION['order_errors'];
    unset($_SESSION['order_errors']); 
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    echo "\n";



    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zipCode = trim($_POST['zip_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $orderNotes = trim($_POST['order_notes'] ?? '');
    $saveAddress = isset($_POST['save_address']); 
    $shippingMethod = trim($_POST['shipping_method'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? '');
    $termsAgree = isset($_POST['terms_agree']); 

  
    if (empty($firstName)) { $orderErrors[] = 'First Name is required.'; }
    if (empty($lastName)) { $orderErrors[] = 'Last Name is required.'; }
    if (empty($email)) { $orderErrors[] = 'Email Address is required.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $orderErrors[] = 'Please enter a valid email address.'; }
    if (empty($phone)) { $orderErrors[] = 'Phone Number is required.'; }
    if (empty($address)) { $orderErrors[] = 'Street Address is required.'; }
    if (empty($city)) { $orderErrors[] = 'City is required.'; }
    if (empty($state)) { $orderErrors[] = 'State/Province is required.'; }
    if (empty($zipCode)) { $orderErrors[] = 'Postal/Zip Code is required.'; }
    
    $allowedCountries = ['South Africa', 'Nigeria', 'Kenya', 'Ghana', 'Tanzania']; 
    if (empty($country) || !in_array($country, $allowedCountries)) { 
        $orderErrors[] = 'Please select a valid Country.';
    }


    if (empty($shippingMethod) || !isset($shippingOptions[$shippingMethod])) {
        $orderErrors[] = 'Please select a valid Shipping Method.';
    } else {
     
        $shipping = $shippingOptions[$shippingMethod]['price'];
        $total = $subtotal + $shipping + $tax; 
        $_SESSION['shipping'] = $shippingMethod; 
    }

    if (empty($paymentMethod)) { $orderErrors[] = 'Please select a Payment Method.'; }

 
    if ($paymentMethod === 'card') {
        $cardNumber = str_replace(' ', '', trim($_POST['card_number'] ?? '')); 
        $cardName = trim($_POST['card_name'] ?? '');
        $expiryDate = trim($_POST['expiry_date'] ?? ''); 
        $cvv = trim($_POST['cvv'] ?? '');

        if (empty($cardNumber) || !preg_match('/^\d{16}$/', $cardNumber)) { 
            $orderErrors[] = 'Please enter a valid 16-digit card number.';
        }
        if (empty($cardName)) {
            $orderErrors[] = 'Name on Card is required.';
        }
        if (empty($expiryDate) || !preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiryDate)) { 
            $orderErrors[] = 'Please enter a valid expiry date (MM/YY).';
        } else {
          
            list($month, $year) = explode('/', $expiryDate);
            $currentYear = (int)date('y'); 
            $currentMonth = (int)date('m');

           
            $fullExpiryYear = 2000 + (int)$year;
            $fullCurrentYear = 2000 + $currentYear;

            if ($fullExpiryYear < $fullCurrentYear || ($fullExpiryYear === $fullCurrentYear && (int)$month < $currentMonth)) {
                $orderErrors[] = 'Card expiry date cannot be in the past.';
            }
        }
        if (empty($cvv) || !preg_match('/^\d{3,4}$/', $cvv)) { 
            $orderErrors[] = 'Please enter a valid CVV (3 or 4 digits).';
        }
    }


    if (!$termsAgree) {
        $orderErrors[] = 'You must agree to the Terms and Conditions, Privacy Policy, and Refund Policy.';
    }


    if (empty($orderErrors)) {
    
        echo "\n";


     
        $orderId = 'ORD-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8)); 
        $orderDate = date('Y-m-d H:i:s');

    
        $_SESSION['order_confirmation'] = [
            'order_id' => $orderId,
            'order_date' => $orderDate,
            'total' => $total,
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'tax_amount' => $tax,
            'shipping_details' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zip_code' => $zipCode,
                'country' => $country,
                'notes' => $orderNotes
            ],
            'shipping_method_details' => $shippingOptions[$shippingMethod],
            'payment_method' => $paymentMethod,
            'items' => $cartItems, 
            'sellers' => $sellers 
        ];

        unset($_SESSION['cart']); 
        unset($_SESSION['shipping']); 

       
        echo "\n";
   
        header('Location: order-confirmation.php');
        exit; 
    } else {
    
        $_SESSION['order_errors'] = $orderErrors;
     
        echo "\n";
        echo "\n";
       
    }
}



$userData = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      
    }
}


$firstName = $_POST['first_name'] ?? ($userData['first_name'] ?? '');
$lastName = $_POST['last_name'] ?? ($userData['last_name'] ?? '');
$email = $_POST['email'] ?? ($userData['email'] ?? '');
$phone = $_POST['phone'] ?? ($userData['phone'] ?? '');
$address = $_POST['address'] ?? ($userData['address'] ?? '');
$city = $_POST['city'] ?? ($userData['city'] ?? '');
$state = $_POST['state'] ?? ($userData['state'] ?? '');
$zipCode = $_POST['zip_code'] ?? ($userData['zip_code'] ?? '');
$country = $_POST['country'] ?? ($userData['country'] ?? '');
$orderNotes = $_POST['order_notes'] ?? ''; 
$saveAddress = isset($_POST['save_address']) ? 'checked' : ''; 
$selectedShipping = $_POST['shipping_method'] ?? ($selectedShipping ?? 'standard'); 
$paymentMethod = $_POST['payment_method'] ?? 'card'; 


$cardNumber = '';
$cardName = '';
$expiryDate = '';
$cvv = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $paymentMethod === 'card') {
    $cardNumber = $_POST['card_number'] ?? '';
    $cardName = $_POST['card_name'] ?? '';
    $expiryDate = $_POST['expiry_date'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
}

$termsAgreeChecked = isset($_POST['terms_agree']) ? 'checked' : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | UbuntuTrade</title>
    <link rel="stylesheet" href="checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <section id="header">
        <a href="index.php"><img src="/placeholder.svg" class="logo" alt="UbuntuTrade Logo" /></a>

        <div>
            <ul id="navbar">
                <li><a href="index.php">Home</a></li>
                <li><a href="shop.php">Shop</a></li>
                <li><a href="blog.php">Blog</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li>
                    <a href="cart.php" class="cart-link">
                        <i class="far fa-shopping-bag"></i>
                        <span class="badge"><?php echo count($_SESSION['cart']); ?></span>
                    </a>
                </li>
            </ul>
        </div>
        <div id="mobile">
            <a href="cart.php" class="cart-link">
                <i class="far fa-shopping-bag"></i>
                <span class="badge"><?php echo count($_SESSION['cart']); ?></span>
            </a>
            <i id="bar" class="fas fa-outdent"></i>
        </div>
    </section>

    <div class="breadcrumb-container">
        <div class="container">
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <i class="fas fa-chevron-right"></i>
                <a href="cart.php">Shopping Cart</a>
                <i class="fas fa-chevron-right"></i>
                <span>Checkout</span>
            </div>
        </div>
    </div>

    <div class="page-title-container">
        <div class="container">
            <h1 class="page-title">Checkout</h1>
            <div class="checkout-steps">
                <div class="step completed">
                    <div class="step-number">1</div>
                    <div class="step-label">Shopping Cart</div>
                </div>
                <div class="step-connector completed"></div>
                <div class="step active">
                    <div class="step-number">2</div>
                    <div class="step-label">Checkout</div>
                </div>
                <div class="step-connector"></div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label">Confirmation</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
    <?php if (!empty($displayErrors)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <ul>
            <?php foreach ($displayErrors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

        <div class="checkout-container">
            <div class="checkout-form-container">
                <form id="checkout-form" method="post" action="checkout.php">
                    <div class="form-section">
                        <h2 class="section-title">
                            <span class="section-icon"><i class="fas fa-map-marker-alt"></i></span>
                            Shipping Information
                        </h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-control" required value="<?php echo htmlspecialchars($firstName); ?>">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-control" required value="<?php echo htmlspecialchars($lastName); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address <span class="required">*</span></label>
                                <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number <span class="required">*</span></label>
                                <input type="tel" id="phone" name="phone" class="form-control" required value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Street Address <span class="required">*</span></label>
                            <input type="text" id="address" name="address" class="form-control" required value="<?php echo htmlspecialchars($address); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City <span class="required">*</span></label>
                                <input type="text" id="city" name="city" class="form-control" required value="<?php echo htmlspecialchars($city); ?>">
                            </div>
                            <div class="form-group">
                                <label for="state">State/Province <span class="required">*</span></label>
                                <input type="text" id="state" name="state" class="form-control" required value="<?php echo htmlspecialchars($state); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="zip_code">Postal/Zip Code <span class="required">*</span></label>
                                <input type="text" id="zip_code" name="zip_code" class="form-control" required value="<?php echo htmlspecialchars($zipCode); ?>">
                            </div>
                            <div class="form-group">
                                <label for="country">Country <span class="required">*</span></label>
                                <select id="country" name="country" class="form-control" required>
                                    <option value="">Select Country</option>
                                    <option value="South Africa" <?php echo ($country === 'South Africa') ? 'selected' : ''; ?>>South Africa</option>
                                    <option value="Nigeria" <?php echo ($country === 'Nigeria') ? 'selected' : ''; ?>>Nigeria</option>
                                    <option value="Kenya" <?php echo ($country === 'Kenya') ? 'selected' : ''; ?>>Kenya</option>
                                    <option value="Ghana" <?php echo ($country === 'Ghana') ? 'selected' : ''; ?>>Ghana</option>
                                    <option value="Tanzania" <?php echo ($country === 'Tanzania') ? 'selected' : ''; ?>>Tanzania</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="order_notes">Order Notes (Optional)</label>
                            <textarea id="order_notes" name="order_notes" class="form-control" rows="3" placeholder="Notes about your order, e.g. special delivery instructions"><?php echo htmlspecialchars($orderNotes); ?></textarea>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="save_address" name="save_address" <?php echo $saveAddress ? 'checked' : ''; ?>>
                            <label for="save_address">Save this address for future orders</label>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2 class="section-title">
                            <span class="section-icon"><i class="fas fa-truck"></i></span>
                            Shipping Method
                        </h2>
                        
                        <div class="shipping-methods">
                            <?php foreach ($shippingOptions as $key => $option): ?>
                                <div class="shipping-method">
                                    <input type="radio" name="shipping_method" id="shipping_<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo $selectedShipping === $key ? 'checked' : ''; ?>>
                                    <label for="shipping_<?php echo $key; ?>">
                                        <div class="method-details">
                                            <span class="method-name"><?php echo $option['name']; ?></span>
                                            <span class="method-description">Delivery in <?php echo $option['days']; ?> business days</span>
                                        </div>
                                        <span class="method-price">R<?php echo number_format($option['price'], 2); ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2 class="section-title">
                            <span class="section-icon"><i class="fas fa-credit-card"></i></span>
                            Payment Method
                        </h2>
                        
                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="payment_card" value="card" <?php echo ($paymentMethod === 'card') ? 'checked' : ''; ?>>
                                <label for="payment_card">
                                    <div class="method-details">
                                        <span class="method-name">Credit/Debit Card</span>
                                        <div class="card-icons">
                                            <i class="fab fa-cc-visa"></i>
                                            <i class="fab fa-cc-mastercard"></i>
                                            <i class="fab fa-cc-amex"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="card-details" id="card-details-form" style="<?php echo ($paymentMethod === 'card') ? 'display: block;' : 'display: none;'; ?>">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="card_number">Card Number <span class="required">*</span></label>
                                        <div class="card-number-input">
                                            <input type="text" id="card_number" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" value="<?php echo htmlspecialchars($cardNumber); ?>" required>
                                            <i class="fas fa-lock"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="card_name">Name on Card <span class="required">*</span></label>
                                        <input type="text" id="card_name" name="card_name" class="form-control" value="<?php echo htmlspecialchars($cardName); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="expiry_date">Expiry Date <span class="required">*</span></label>
                                        <input type="text" id="expiry_date" name="expiry_date" class="form-control" placeholder="MM/YY" value="<?php echo htmlspecialchars($expiryDate); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="cvv">CVV <span class="required">*</span></label>
                                        <div class="cvv-input">
                                            <input type="text" id="cvv" name="cvv" class="form-control" placeholder="123" value="<?php echo htmlspecialchars($cvv); ?>" required>
                                            <i class="fas fa-question-circle" title="3-digit security code on the back of your card"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="payment_paypal" value="paypal" <?php echo ($paymentMethod === 'paypal') ? 'checked' : ''; ?>>
                                <label for="payment_paypal">
                                    <div class="method-details">
                                        <span class="method-name">PayPal</span>
                                        <div class="card-icons">
                                            <i class="fab fa-paypal"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="payment_escrow" value="escrow" <?php echo ($paymentMethod === 'escrow') ? 'checked' : ''; ?>>
                                <label for="payment_escrow">
                                    <div class="method-details">
                                        <span class="method-name">UbuntuTrade Secure Escrow</span>
                                        <span class="method-description">Funds are held securely until you receive and approve the item</span>
                                        <div class="escrow-badge">
                                            <i class="fas fa-shield-alt"></i> Buyer Protection
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="payment_bank" value="bank" <?php echo ($paymentMethod === 'bank') ? 'checked' : ''; ?>>
                                <label for="payment_bank">
                                    <div class="method-details">
                                        <span class="method-name">Bank Transfer</span>
                                        <span class="method-description">Make your payment directly into our bank account</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2 class="section-title">
                            <span class="section-icon"><i class="fas fa-shield-alt"></i></span>
                            Buyer Protection
                        </h2>
                        
                        <div class="buyer-protection-info">
                            <div class="protection-item">
                                <div class="protection-icon">
                                    <i class="fas fa-undo"></i>
                                </div>
                                <div class="protection-details">
                                    <h3>Money Back Guarantee</h3>
                                    <p>If your item doesn't arrive or isn't as described, we'll refund your payment.</p>
                                </div>
                            </div>
                            
                            <div class="protection-item">
                                <div class="protection-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="protection-details">
                                    <h3>Secure Transactions</h3>
                                    <p>Your payment information is encrypted and never shared with sellers.</p>
                                </div>
                            </div>
                            
                            <div class="protection-item">
                                <div class="protection-icon">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <div class="protection-details">
                                    <h3>24/7 Support</h3>
                                    <p>Our customer support team is available to help with any issues.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-group checkbox-group terms-checkbox">
                            <input type="checkbox" id="terms_agree" name="terms_agree" required <?php echo $termsAgreeChecked; ?>>
                            <label for="terms_agree">I have read and agree to the <a href="#" class="terms-link">Terms and Conditions</a>, <a href="#" class="terms-link">Privacy Policy</a>, and <a href="#" class="terms-link">Refund Policy</a> <span class="required">*</span></label>
                        </div>
                        
                        <button type="submit" name="place_order" class="btn primary-btn place-order-btn">
                            <i class="fas fa-lock"></i> Place Order - R<?php echo number_format($total, 2); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="order-summary-container">
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    
                    <div class="order-items">
                        <?php foreach ($sellers as $sellerId => $seller): ?>
                            <div class="seller-items">
                                <div class="seller-info">
                                    <div class="seller-avatar">
                                        <img src="https://randomuser.me/api/portraits/men/<?php echo $sellerId % 100; ?>.jpg" alt="Seller Avatar">
                                    </div>
                                    <div class="seller-details">
                                        <h3 class="seller-name"><?php echo htmlspecialchars($seller['seller_name']); ?></h3>
                                        <div class="seller-rating">
                                            <?php
                                            $rating = round($seller['seller_rating']);
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            ?>
                                            <span>(<?php echo htmlspecialchars($seller['seller_rating']); ?>)</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php foreach ($seller['items'] as $productId => $item): ?>
                                    <div class="order-item">
                                        <div class="item-image">
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php if (!empty($item['condition'])): ?>
                                                <span class="condition-badge"><?php echo htmlspecialchars($item['condition']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="item-details">
                                            <h4 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h4>
                                            <div class="item-meta">
                                                <span class="item-quantity">Qty: <?php echo htmlspecialchars($item['quantity']); ?></span>
                                                <span class="item-price">R<?php echo number_format($item['price'], 2); ?></span>
                                            </div>
                                        </div>
                                        <div class="item-total">
                                            R<?php echo number_format($item['item_total'], 2); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="seller-total">
                                    <span>Seller Total:</span>
                                    <span class="price">R<?php echo number_format($seller['seller_total'], 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="order-totals">
                        <div class="total-row">
                            <span>Subtotal</span>
                            <span class="price">R<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        
                        <div class="total-row">
                            <span>Shipping</span>
                            <span class="price">R<?php echo number_format($shipping, 2); ?></span>
                        </div>
                        
                        <div class="total-row">
                            <span>Tax (15% VAT)</span>
                            <span class="price">R<?php echo number_format($tax, 2); ?></span>
                        </div>
                        
                        <div class="total-row grand-total">
                            <span>Total</span>
                            <span class="price">R<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="secure-checkout-badge">
                        <i class="fas fa-lock"></i>
                        <span>Secure Checkout</span>
                    </div>
                    
                    <div class="payment-icons">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-amex"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fab fa-cc-apple-pay"></i>
                    </div>
                </div>
                
                <div class="need-help">
                    <h3>Need Help?</h3>
                    <p>Our customer support team is available 24/7 to assist you with your purchase.</p>
                    <a href="contact.php" class="btn outline-btn">
                        <i class="fas fa-headset"></i> Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>

    <footer class="section-p1">
        <div class="col">
            <img class="logo" src="/placeholder.svg" alt="UbuntuTrade Logo" />
            <h4>Contact</h4>
            <p><strong>Address: </strong> 4664 Address Street</p>
            <p><strong>Phone: </strong> 011 123 4567</p>
            <p><strong>Hours: </strong> 10:00 - 18:00, Mon - Sat</p>
            <div class="follow">
                <h4>Follow us</h4>
                <div class="icon">
                    <i class="fab fa-facebook-f"></i>
                    <i class="fab fa-twitter"></i>
                    <i class="fab fa-instagram"></i>
                    <i class="fab fa-pinterest-p"></i>
                    <i class="fab fa-youtube"></i>
                </div>
            </div>
        </div>

        <div class="col">
            <h4>About</h4>
            <a href="#">About us</a>
            <a href="#">Delivery Information</a>
            <a href="#">Privacy Policy</a>
            <a href="#">Terms & Conditions</a>
            <a href="#">Contact Us</a>
        </div>

        <div class="col">
            <h4>My Account</h4>
            <a href="#">Sign In</a>
            <a href="#">View Cart</a>
            <a href="#">My Wishlist</a>
            <a href="#">Track My Order</a>
            <a href="#">Help</a>
        </div>

        <div class="col install">
            <h4>Install App</h4>
            <p>From App Store or Google Play</p>
            <div class="row">
                <img src="/placeholder.svg" alt="App Store" />
                <img src="/placeholder.svg" alt="Google Play" />
            </div>
            <p>Secure Payment Gateways</p>
            <img src="/placeholder.svg" alt="Payment Methods" />
        </div>

        <div class="copyright">
            <p>© 2025 - UbuntuTrade</p>
        </div>
    </footer>

   
</body>
</html>