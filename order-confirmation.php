<?php
session_start();


if (!isset($_SESSION['order_confirmation']) || empty($_SESSION['order_confirmation'])) {
  
    header('Location: index.php'); 
    exit;
}

$order = $_SESSION['order_confirmation'];


unset($_SESSION['order_confirmation']);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | UbuntuTrade</title>
    <link rel="stylesheet" href="order-confirmation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
       
        :root {
            --primary-color: #ff385c;
            --primary-light: #ff7a95;
            --primary-dark: #e61e4d;
            --secondary-color: #3a86ff;
            --secondary-light: #61a0ff;
            --secondary-dark: #2563eb;
            --accent-color: #00c8b3;
            --accent-light: #40e0d0;
            --text-color: #1a1a1a;
            --text-light: #6b7280;
            --text-lighter: #9ca3af;
            --background-white: #ffffff;
            --background-light: #f9fafb;
            --background-lighter: #f3f4f6;
            --background-dark: #111827;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --border-color: #e5e7eb;
            --box-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --box-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --box-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --box-shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --transition-fast: all 0.2s ease;
            --transition: all 0.3s ease;
            --transition-slow: all 0.5s ease;
            --border-radius-sm: 0.25rem;
            --border-radius: 0.5rem;
            --border-radius-md: 0.75rem;
            --border-radius-lg: 1rem;
            --border-radius-xl: 1.5rem;
            --border-radius-2xl: 2rem;
            --border-radius-full: 9999px;
        }

        body { font-family: 'Outfit', sans-serif; margin: 0; padding: 0; background-color: var(--background-light); color: var(--text-color); }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 1.5rem; }
        #header {
            display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 7%;
            background: var(--background-white); box-shadow: var(--box-shadow); z-index: 999;
            position: sticky; top: 0; left: 0;
        }
        #header .logo { height: 3rem; }
        #navbar { display: flex; align-items: center; }
        #navbar li { list-style: none; padding: 0 1.25rem; }
        #navbar li a { text-decoration: none; font-size: 1rem; font-weight: 500; color: var(--text-color); }
        .cart-link { position: relative; }
        .badge {
            position: absolute; top: -0.625rem; right: -0.625rem; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white; font-size: 0.75rem; font-weight: 600; width: 1.5rem; height: 1.5rem; border-radius: var(--border-radius-full);
            display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .breadcrumb-container {
            padding: 1rem 0; border-bottom: 1px solid var(--border-color); background-color: var(--background-white); box-shadow: var(--box-shadow-sm);
        }
        .breadcrumb {
            display: flex; align-items: center; font-size: 0.875rem; color: var(--text-light); max-width: 1280px; margin: 0 auto; padding: 0 7%;
        }
        .breadcrumb a { color: var(--text-light); transition: var(--transition); }
        .breadcrumb a:hover { color: var(--primary-color); }
        .breadcrumb i { margin: 0 0.75rem; font-size: 0.75rem; color: var(--text-lighter); }
        .breadcrumb span { color: var(--text-color); font-weight: 500; }
      
        footer {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 4rem;
            padding: 5rem 7% 3rem; background-color: var(--background-light); position: relative;
        }
        footer::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-light), var(--secondary-color), var(--accent-color));
        }
        footer .col { display: flex; flex-direction: column; align-items: flex-start; }
        footer .logo { height: 3rem; margin-bottom: 2rem; }
        footer h4 { font-size: 1.125rem; padding-bottom: 1.25rem; position: relative; margin-bottom: 1.5rem; }
        footer h4::after { content: ''; width: 50px; height: 2px; background: var(--primary-color); position: absolute; bottom: 0; left: 0; }
        footer p { font-size: 0.9rem; margin: 0 0 0.75rem 0; color: var(--text-light); }
        footer a { font-size: 0.9rem; text-decoration: none; color: var(--text-light); margin-bottom: 0.75rem; }
        footer .follow { margin-top: 1.5rem; }
        footer .follow .icon { display: flex; gap: 1rem; }
        footer .follow i { color: var(--text-light); font-size: 1.5rem; cursor: pointer; }
        footer .install .row { display: flex; gap: 0.75rem; }
        footer .install .row img { border: 1px solid var(--border-color); border-radius: var(--border-radius); height: 3rem; }
        footer .install img { margin: 0.75rem 0 1.5rem 0; }
        footer .copyright {
            width: 100%; text-align: center; padding-top: 2rem; border-top: 1px solid var(--border-color);
            margin-top: 3rem; grid-column: 1 / -1; color: var(--text-light); font-size: 0.9rem;
        }
    </style>
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
                        <span class="badge"><?php echo count($_SESSION['cart'] ?? []); ?></span>
                    </a>
                </li>
            </ul>
        </div>
        <div id="mobile">
            <a href="cart.php" class="cart-link">
                <i class="far fa-shopping-bag"></i>
                <span class="badge"><?php echo count($_SESSION['cart'] ?? []); ?></span>
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
                <a href="checkout.php">Checkout</a>
                <i class="fas fa-chevron-right"></i>
                <span>Order Confirmation</span>
            </div>
        </div>
    </div>

    <div class="page-title-container">
        <div class="container">
            <h1 class="page-title">Order Confirmed!</h1>
            <div class="checkout-steps">
                <div class="step completed">
                    <div class="step-number"><i class="fas fa-check"></i></div>
                    <div class="step-label">Shopping Cart</div>
                </div>
                <div class="step-connector completed"></div>
                <div class="step completed">
                    <div class="step-number"><i class="fas fa-check"></i></div>
                    <div class="step-label">Checkout</div>
                </div>
                <div class="step-connector completed"></div>
                <div class="step completed">
                    <div class="step-number">3</div>
                    <div class="step-label">Confirmation</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container confirmation-content">
        <div class="confirmation-message">
            <i class="fas fa-check-circle"></i>
            <h2>Thank you for your order!</h2>
            <p>Your order has been placed successfully. You will receive an email confirmation shortly with details of your purchase.</p>
            <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['order_id']); ?></p>
            <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
        </div>

        <div class="order-details-summary">
            <h3>Order Details</h3>

            <div class="summary-section">
                <h4>Shipping Address</h4>
                <p>
                    <?php echo htmlspecialchars($order['shipping_details']['first_name']) . ' ' . htmlspecialchars($order['shipping_details']['last_name']); ?><br>
                    <?php echo htmlspecialchars($order['shipping_details']['address']); ?><br>
                    <?php echo htmlspecialchars($order['shipping_details']['city']) . ', ' . htmlspecialchars($order['shipping_details']['state']) . ' ' . htmlspecialchars($order['shipping_details']['zip_code']); ?><br>
                    <?php echo htmlspecialchars($order['shipping_details']['country']); ?><br>
                    Phone: <?php echo htmlspecialchars($order['shipping_details']['phone']); ?><br>
                    Email: <?php echo htmlspecialchars($order['shipping_details']['email']); ?>
                </p>
                <?php if (!empty($order['shipping_details']['notes'])): ?>
                    <h4>Order Notes</h4>
                    <p><?php echo nl2br(htmlspecialchars($order['shipping_details']['notes'])); ?></p>
                <?php endif; ?>
            </div>

            <div class="summary-section">
                <h4>Shipping Method</h4>
                <p><strong><?php echo htmlspecialchars($order['shipping_method_details']['name']); ?>:</strong> R<?php echo number_format($order['shipping_method_details']['price'], 2); ?></p>
                <p>Delivery in <?php echo htmlspecialchars($order['shipping_method_details']['days']); ?> business days</p>
            </div>

            <div class="summary-section">
                <h4>Payment Method</h4>
                <p><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method']))); ?></p>
                <?php if ($order['payment_method'] === 'bank'): ?>
                    <p class="bank-details-info">
                        Please transfer the total amount to our bank account. Instructions will be sent to your email.
                    </p>
                <?php endif; ?>
            </div>

            <div class="summary-section order-items-summary">
                <h4>Items Ordered</h4>
                <?php foreach ($order['sellers'] as $sellerId => $seller): ?>
                    <div class="seller-group">
                        <div class="seller-header">
                            <i class="fas fa-store"></i>
                            <h5><?php echo htmlspecialchars($seller['seller_name']); ?></h5>
                        </div>
                        <?php foreach ($seller['items'] as $productId => $item): ?>
                            <div class="order-item-confirm">
                                <div class="item-img-confirm">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                <div class="item-details-confirm">
                                    <span class="item-name-confirm"><?php echo htmlspecialchars($item['name']); ?></span>
                                    <span class="item-quantity-confirm">Qty: <?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="item-price-confirm">R<?php echo number_format($item['item_total'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                        <div class="seller-subtotal-confirm">
                            <span>Seller Total:</span>
                            <span class="price">R<?php echo number_format($seller['seller_total'], 2); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-section order-totals-confirm">
                <div class="total-row-confirm">
                    <span>Subtotal</span>
                    <span class="price">R<?php echo number_format($order['subtotal'], 2); ?></span>
                </div>
                <div class="total-row-confirm">
                    <span>Shipping</span>
                    <span class="price">R<?php echo number_format($order['shipping_cost'], 2); ?></span>
                </div>
                <div class="total-row-confirm">
                    <span>Tax (15% VAT)</span>
                    <span class="price">R<?php echo number_format($order['tax_amount'], 2); ?></span>
                </div>
                <div class="total-row-confirm grand-total-confirm">
                    <span>Grand Total</span>
                    <span class="price">R<?php echo number_format($order['total'], 2); ?></span>
                </div>
            </div>
        </div>

        <div class="confirmation-actions">
            <a href="shop.php" class="btn primary-btn"><i class="fas fa-shopping-bag"></i> Continue Shopping</a>
            <a href="track-order.php?order_id=<?php echo htmlspecialchars($order['order_id']); ?>" class="btn outline-btn"><i class="fas fa-map-marker-alt"></i> Track Order</a>
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