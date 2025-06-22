<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'db_connection.php'; 


if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update') {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

        if ($productId > 0) { 
            if ($quantity > 0) {
                $_SESSION['cart'][$productId] = $quantity;
            } else {
                unset($_SESSION['cart'][$productId]);
            }
        }

      
        header('Location: cart.php');
        exit;
    } elseif ($action === 'remove') {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

        if ($productId > 0 && isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }

        header('Location: cart.php');
        exit;
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];

     
        header('Location: cart.php');
        exit;
    }
}


$cartItems = [];
$sellers = [];
$subtotal = 0; 

if (!empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    try {
        $stmt = $pdo->prepare("
            SELECT p.ProductID, p.ProductName, p.Price, p.ImageURL, p.SellerID, p.`Condition`,
                   p.Make, p.Model, p.Year, p.Mileage, p.FuelType, p.Transmission,
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
            $itemTotal = (float)$price * $quantity;

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
                'make' => $product['Make'], // Added for cars
                'model' => $product['Model'], // Added for cars
                'year' => $product['Year'],   // Added for cars
                'mileage' => $product['Mileage'], // Added for cars
                'fuel_type' => $product['FuelType'], // Added for cars
                'transmission' => $product['Transmission'], // Added for cars
                'item_total' => $itemTotal
            ];

          
            $subtotal += $itemTotal;

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
      
        error_log("Error fetching cart items: " . $e->getMessage());
       
    }
}


$shipping = 0;
$shippingOptions = [
    'standard' => ['name' => 'Standard Shipping', 'price' => 50, 'days' => '3-5'],
    'express' => ['name' => 'Express Shipping', 'price' => 100, 'days' => '1-2'],
    'free' => ['name' => 'Free Shipping', 'price' => 0, 'days' => '5-7'],
   
    'pickup' => ['name' => 'Local Pickup', 'price' => 0, 'days' => 'N/A'] 
];

$selectedShipping = isset($_SESSION['shipping']) ? $_SESSION['shipping'] : 'standard';

$shipping = $shippingOptions[$selectedShipping]['price'];



$taxRate = 0.15;
$tax = $subtotal * $taxRate;

$total = $subtotal + $shipping + $tax;

$recommendedProducts = [];
try {
    $stmt = $pdo->prepare("
        SELECT ProductID, ProductName, Price, ImageURL, SellerID, Featured
        FROM Products
        WHERE Featured = 1
        ORDER BY RAND()
        LIMIT 4
    ");
    $stmt->execute();
    $recommendedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recommended products: " . $e->getMessage());
}


$cartItemCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | UbuntuTrade</title>
    <link rel="stylesheet" href="cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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
                    <a href="cart.php" class="cart-link active">
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
                <span>Shopping Cart</span>
            </div>
        </div>
    </div>

    <div class="page-title-container">
        <div class="container">
            <h1 class="page-title">Your Shopping Cart</h1>
            <p class="items-count"><?php echo count($_SESSION['cart']); ?> item(s) in your cart</p>
        </div>
    </div>

    <div class="container">
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="shop.php" class="btn primary-btn">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <?php foreach ($sellers as $sellerId => $seller): ?>
                        <div class="seller-section">
                            <div class="seller-header">
                                <div class="seller-info">
                                    <div class="seller-avatar">
                                        <img src="https://randomuser.me/api/portraits/men/<?php echo $sellerId % 100; ?>.jpg" alt="Seller Avatar">
                                    </div>
                                    <div class="seller-details">
                                    <h3 class="seller-name"><?php echo htmlspecialchars($seller['seller_name'] ?? 'Seller Information Not Available'); ?></h3>
                                        <div class="seller-meta">
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
                                            <div class="seller-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?php echo htmlspecialchars($seller['seller_location']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="seller-total">
                                    <span>Seller Total:</span>
                                    <span class="price">R<?php echo number_format($seller['seller_total'], 2); ?></span>
                                </div>
                            </div>

                            <div class="cart-items-list">
                                <?php foreach ($seller['items'] as $productId => $item): ?>
                                    <div class="cart-item" data-product-id="<?php echo $productId; ?>">
                                        <div class="item-image">
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php if (!empty($item['condition'])): ?>
                                                <span class="condition-badge"><?php echo htmlspecialchars($item['condition']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="item-details">
                                            <h3 class="item-name">
                                                <a href="product-details.php?id=<?php echo $productId; ?>">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                            </h3>
                                            <div class="item-meta">
                                                <?php if (!empty($item['make'])): ?>
                                                    <span class="item-make">Make: <?php echo htmlspecialchars($item['make']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['model'])): ?>
                                                    <span class="item-model">Model: <?php echo htmlspecialchars($item['model']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['year'])): ?>
                                                    <span class="item-year">Year: <?php echo htmlspecialchars($item['year']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['mileage'])): ?>
                                                    <span class="item-mileage">Mileage: <?php echo number_format($item['mileage']); ?> km</span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['fuel_type'])): ?>
                                                    <span class="item-fueltype">Fuel: <?php echo htmlspecialchars($item['fuel_type']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['transmission'])): ?>
                                                    <span class="item-transmission">Trans: <?php echo htmlspecialchars($item['transmission']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="item-price">
                                                <span class="price">R<?php echo number_format($item['price'], 2); ?></span>
                                            </div>
                                        </div>
                                        <div class="item-quantity">
                                            <form action="cart.php" method="post" class="quantity-form">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                                <div class="quantity-controls">
                                                    <button type="button" class="quantity-btn decrease">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" max="10" class="quantity-input">
                                                    <button type="button" class="quantity-btn increase">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                                <button type="submit" class="update-btn">Update</button>
                                            </form>
                                        </div>
                                        <div class="item-total">
                                            <span class="price">R<?php echo number_format($item['item_total'], 2); ?></span>
                                        </div>
                                        <div class="item-actions">
                                            <form action="cart.php" method="post" class="remove-form">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                                <button type="submit" class="remove-btn">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="save-for-later-btn">
                                                <i class="far fa-heart"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="seller-shipping">
                                <h4>Shipping Options from this Seller</h4>
                                <div class="shipping-options">
                                    <div class="shipping-option">
                                        <input type="radio" name="shipping_<?php echo $sellerId; ?>" id="standard_<?php echo $sellerId; ?>" value="standard" checked>
                                        <label for="standard_<?php echo $sellerId; ?>">
                                            <div class="option-details">
                                                <span class="option-name">Standard Shipping</span>
                                                <span class="option-delivery">Delivery in 3-5 business days</span>
                                            </div>
                                            <span class="option-price">R50.00</span>
                                        </label>
                                    </div>
                                    <div class="shipping-option">
                                        <input type="radio" name="shipping_<?php echo $sellerId; ?>" id="express_<?php echo $sellerId; ?>" value="express">
                                        <label for="express_<?php echo $sellerId; ?>">
                                            <div class="option-details">
                                                <span class="option-name">Express Shipping</span>
                                                <span class="option-delivery">Delivery in 1-2 business days</span>
                                            </div>
                                            <span class="option-price">R100.00</span>
                                        </label>
                                    </div>
                                    <?php if (($seller['seller_total'] ?? 0) > 500): ?>
                                    <div class="shipping-option">
                                        <input type="radio" name="shipping_<?php echo $sellerId; ?>" id="free_<?php echo $sellerId; ?>" value="free">
                                        <label for="free_<?php echo $sellerId; ?>">
                                            <div class="option-details">
                                                <span class="option-name">Free Shipping</span>
                                                <span class="option-delivery">Delivery in 5-7 business days</span>
                                                <span class="free-shipping-note">Free for orders over R500</span>
                                            </div>
                                            <span class="option-price">R0.00</span>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($seller['seller_location'])): ?>
                                    <div class="shipping-option">
                                    <input type="radio" name="shipping_<?php echo $sellerId; ?>" id="pickup_<?php echo $sellerId; ?>" value="pickup">
                                        <label for="pickup_<?php echo $sellerId; ?>">
                                            <div class="option-details">
                                                <span class="option-name">Local Pickup</span>
                                                <span class="option-delivery">Pickup from seller's location in <?php echo htmlspecialchars($seller['seller_location']); ?></span>
                                            </div>
                                            <span class="option-price">R0.00</span>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="cart-actions">
                        <a href="shop.php" class="btn secondary-btn">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                        <form action="cart.php" method="post" class="clear-cart-form">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn outline-btn">
                                <i class="fas fa-trash"></i> Clear Cart
                            </button>
                        </form>
                    </div>
                </div>

                <div class="cart-summary">
                    <h2>Order Summary</h2>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span class="price">R<?php echo number_format($subtotal, 2); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Shipping</span>
                        <span class="price" id="summary-shipping-price">R<?php echo number_format($shipping, 2); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Tax (15% VAT)</span>
                        <span class="price" id="summary-tax-price">R<?php echo number_format($tax, 2); ?></span>
                    </div>

                    <div class="summary-row total">
                        <span>Total</span>
                        <span class="price" id="summary-total-price">R<?php echo number_format($total, 2); ?></span>
                    </div>


                    <div class="coupon-section">
                        <h3>Apply Coupon</h3>
                        <form class="coupon-form">
                            <input type="text" placeholder="Enter coupon code" class="coupon-input">
                            <button type="submit" class="btn secondary-btn">Apply</button>
                        </form>
                    </div>

                    <div class="checkout-button">
                        <a href="checkout.php" class="btn primary-btn">
                            Proceed to Checkout <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="secure-checkout">
                        <div class="secure-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="secure-text">
                            <h4>Secure Checkout</h4>
                            <p>Your payment information is processed securely.</p>
                        </div>
                    </div>

                    <div class="payment-methods">
                        <span>We Accept</span>
                        <div class="payment-icons">
                            <i class="fab fa-cc-visa"></i>
                            <i class="fab fa-cc-mastercard"></i>
                            <i class="fab fa-cc-paypal"></i>
                            <i class="fab fa-cc-apple-pay"></i>
                        </div>
                    </div>

                    <div class="buyer-protection">
                        <div class="protection-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="protection-text">
                            <h4>UbuntuTrade Buyer Protection</h4>
                            <p>Get a full refund if the item is not as described or doesn't arrive.</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($recommendedProducts)): ?>
            <div class="recommended-products">
                <h2>You Might Also Like</h2>
                <div class="products-grid">
                    <?php foreach ($recommendedProducts as $product): ?>
                    <div class="product-card">
                        <a href="product-details.php?id=<?php echo htmlspecialchars($product['ProductID']); ?>">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($product['ImageURL']); ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['ProductName']); ?></h3>
                                <div class="product-price">R<?php echo number_format($product['Price'], 2); ?></div>
                            </div>
                        </a>
                        <button class="add-to-cart-btn" data-product-id="<?php echo htmlspecialchars($product['ProductID']); ?>">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <section class="trust-indicators">
        <div class="container">
            <div class="trust-grid">
                <div class="trust-item">
                    <div class="trust-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="trust-content">
                        <h3>Secure Transactions</h3>
                        <p>Your payment information is protected by secure encryption.</p>
                    </div>
                </div>
                <div class="trust-item">
                    <div class="trust-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="trust-content">
                        <h3>Easy Returns</h3>
                        <p>30-day return policy for most items.</p>
                    </div>
                </div>
                <div class="trust-item">
                    <div class="trust-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="trust-content">
                        <h3>Buyer Protection</h3>
                        <p>We've got you covered from click to delivery.</p>
                    </div>
                </div>
                <div class="trust-item">
                    <div class="trust-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="trust-content">
                        <h3>24/7 Support</h3>
                        <p>Have a question? We're here to help.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

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

    <script src="cart.js"></script>
</body>
</html>