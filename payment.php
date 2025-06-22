<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db_connection.php'; 
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Checkout - Payment | MarketPlace</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="checkout.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  </head>
  <body>
    <!-- Header -->
    <header class="border-b">
      <div class="container">
        <div class="header-wrapper">
          <a href="/" class="logo"> MarketPlace </a>
        </div>
      </div>
    </header>

    <!-- Checkout Progress -->
    <div class="checkout-progress">
      <div class="container">
        <div class="progress-steps">
          <div class="progress-step completed">
            <div class="step-number">
              <i class="fas fa-check"></i>
            </div>
            <div class="step-label">Shipping</div>
          </div>
          <div class="progress-line completed"></div>
          <div class="progress-step active">
            <div class="step-number">2</div>
            <div class="step-label">Payment</div>
          </div>
          <div class="progress-line"></div>
          <div class="progress-step">
            <div class="step-number">3</div>
            <div class="step-label">Confirmation</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <main class="container">
      <div class="checkout-layout">
        <!-- Payment Form -->
        <div class="checkout-main">
          <div class="checkout-section">
            <h2>Payment Method</h2>

            <div class="payment-methods">
              <label class="payment-method">
                <input
                  type="radio"
                  name="payment-method"
                  value="credit-card"
                />
                <i class="far fa-credit-card"></i>
                <span>Credit Card</span>
              </label>

              <label class="payment-method">
                <input type="radio" name="payment-method" value="payfast" />
                <img src="path/to/payfast-logo.png" alt="PayFast" style="height: 30px; vertical-align: middle; margin-right: 5px;">
                <span>PayFast</span>
              </label>

              <label class="payment-method">
                <input type="radio" name="payment-method" value="yoco" />
                <img src="path/to/yoco-logo.png" alt="Yoco" style="height: 30px; vertical-align: middle; margin-right: 5px;">
                <span>Yoco</span>
              </label>

              <label class="payment-method" style="display: none;">
                <input type="radio" name="payment-method" value="paypal" style="display: none;"/>
                <i class="fab fa-paypal"></i>
                <span>PayPal</span>
              </label>

              <label class="payment-method" style="display: none;">
                <input type="radio" name="payment-method" value="apple-pay" style="display: none;"/>
                <i class="fab fa-apple-pay"></i>
                <span>Apple Pay</span>
              </label>

              <label class="payment-method" style="display: none;">
                <input type="radio" name="payment-method" value="google-pay" style="display: none;"/>
                <i class="fab fa-google-pay"></i>
                <span>Google Pay</span>
              </label>
            </div>

            <div id="credit-card-form" class="card-form">
              <div class="card-icons">
                <i class="fab fa-cc-visa"></i>
                <i class="fab fa-cc-mastercard"></i>
                <i class="fab fa-cc-amex"></i>
                <i class="fab fa-cc-discover"></i>
              </div>

              <form id="payment-form">
                <div class="form-group">
                  <label for="card-name">Name on Card</label>
                  <input type="text" id="card-name" name="card-name" required />
                </div>

                <div class="form-group">
                  <label for="card-number">Card Number</label>
                  <input
                    type="text"
                    id="card-number"
                    name="card-number"
                    placeholder="1234 5678 9012 3456"
                    required
                  />
                </div>

                <div class="card-row">
                  <div class="card-group">
                    <label for="expiry-date">Expiry Date</label>
                    <input
                      type="text"
                      id="expiry-date"
                      name="expiry-date"
                      placeholder="MM/YY"
                      required
                    />
                  </div>

                  <div class="card-group">
                    <label for="cvv">CVV</label>
                    <input
                      type="text"
                      id="cvv"
                      name="cvv"
                      placeholder="123"
                      required
                    />
                  </div>
                </div>

                <div class="form-checkbox">
                  <input type="checkbox" id="save-card" name="save-card" />
                  <label for="save-card"
                    >Save this card for future purchases</label
                  >
                </div>
              </form>
            </div>
          </div>

          <div class="checkout-section">
            <h2>Billing Address</h2>

            <div class="form-checkbox">
              <input
                type="checkbox"
                id="same-address"
                name="same-address"
                checked
              />
              <label for="same-address">Same as shipping address</label>
            </div>

            <div
              id="billing-address-form"
              class="checkout-form"
              style="display: none; margin-top: 1.5rem"
            >
              <div class="form-row">
                <div class="form-group">
                  <label for="billing-first-name">First Name</label>
                  <input
                    type="text"
                    id="billing-first-name"
                    name="billing-first-name"
                  />
                </div>
                <div class="form-group">
                  <label for="billing-last-name">Last Name</label>
                  <input
                    type="text"
                    id="billing-last-name"
                    name="billing-last-name"
                  />
                </div>
              </div>

              <div class="form-group">
                <label for="billing-address">Street Address</label>
                <input
                  type="text"
                  id="billing-address"
                  name="billing-address"
                />
              </div>

              <div class="form-group">
                <label for="billing-address2"
                  >Apartment, suite, etc. (optional)</label
                >
                <input
                  type="text"
                  id="billing-address2"
                  name="billing-address2"
                />
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="billing-city">City</label>
                  <input type="text" id="billing-city" name="billing-city" />
                </div>
                <div class="form-group">
                  <label for="billing-state">State/Province</label>
                  <select id="billing-state" name="billing-state">
                    <option value="">Select State</option>
                    <option value="AL">Alabama</option>
                    <option value="AK">Alaska</option>
                    <option value="AZ">Arizona</option>
                    <!-- More states would be listed here -->
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="billing-zip">ZIP/Postal Code</label>
                  <input type="text" id="billing-zip" name="billing-zip" />
                </div>
                <div class="form-group">
                  <label for="billing-country">Country</label>
                  <select id="billing-country" name="billing-country">
                    <option value="US" selected>United States</option>
                    <option value="CA">Canada</option>
                    <option value="UK">United Kingdom</option>
                    <!-- More countries would be listed here -->
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="checkout-actions">
            <a href="checkout-shipping.php" class="btn outline-btn">
              <i class="fas fa-arrow-left"></i> Back to Shipping
            </a>
            <button type="submit" class="btn primary-btn" id="place-order">
              Place Order <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="checkout-sidebar">
    <div class="order-summary">
        <h2>Order Summary</h2>

        <div class="order-items">
            <?php
            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])):
                $subtotal = 0;
                foreach ($_SESSION['cart'] as $product_id => $item):
                    // Fetch product details from the database
                    $stmt = $pdo->prepare("SELECT ProductName, Price, ImageURL FROM Products WHERE ProductID = :id");
                    $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($product):
                        $itemSubtotal = $item['quantity'] * $product['Price'];
                        $subtotal += $itemSubtotal;
                        ?>
                        <div class="order-item">
                            <div class="item-image">
                                <img src="<?php echo htmlspecialchars($product['ImageURL'] ?? 'https://placehold.co/600x600?text=No+Image', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['Name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <span class="item-quantity"><?php echo htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="item-details">
                            <h3><?php echo htmlspecialchars($product['ProductName'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <span class="item-price">$<?php echo number_format($product['Price'], 2); ?></span>
                            </div>
                        </div>
                        <?php
                    endif;
                endforeach;
            else:
                echo '<p>Your cart is empty.</p>';
            endif;
            ?>
        </div>

        <div class="order-totals">
            <div class="total-row">
                <span>Subtotal</span>
                <span>$<?php echo number_format($subtotal ?? 0, 2); ?></span>
            </div>
            <div class="total-row">
                <span>Shipping</span>
                <span>$<?php echo number_format($_SESSION['checkout']['shipping_cost'] ?? 0, 2); ?></span>
            </div>
            <div class="total-row">
                <span>Estimated Tax</span>
                <span>$<?php echo number_format(($subtotal ?? 0) * 0.08, 2); ?></span>
            </div>
            <?php if (isset($_SESSION['applied_promo']) && isset($_SESSION['applied_promo']['discount_amount'])): ?>
                <div class="total-row discount">
                    <span>Discount (<?php echo htmlspecialchars($_SESSION['applied_promo']['code'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                    <span>-$<?php echo number_format($_SESSION['applied_promo']['discount_amount'], 2); ?></span>
                </div>
            <?php endif; ?>
            <div class="total-row grand-total">
                <span>Total</span>
                <span>$<?php
                    $total = ($subtotal ?? 0) + ($_SESSION['checkout']['shipping_cost'] ?? 0) + (($subtotal ?? 0) * 0.08);
                    if (isset($_SESSION['applied_promo'])) {
                        $total -= $_SESSION['applied_promo']['discount_amount'];
                    }
                    echo number_format($total, 2);
                ?></span>
            </div>
        </div>
    </div>

    <div class="shipping-info-summary">
        <h3>Shipping Information</h3>
        <p><strong><?php echo htmlspecialchars($_SESSION['checkout']['shipping']['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($_SESSION['checkout']['shipping']['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></p>
        <p><?php echo htmlspecialchars($_SESSION['checkout']['shipping']['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?><?php if (!empty($_SESSION['checkout']['shipping']['address2'])): ?>, <?php echo htmlspecialchars($_SESSION['checkout']['shipping']['address2'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></p>
        <p><?php echo htmlspecialchars($_SESSION['checkout']['shipping']['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars($_SESSION['checkout']['shipping']['state'] ?? '', ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($_SESSION['checkout']['shipping']['zip'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
        <p><?php echo htmlspecialchars($_SESSION['checkout']['shipping']['country'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
        <p>Phone: <?php echo htmlspecialchars($_SESSION['checkout']['shipping']['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
        <p>Email: <?php echo htmlspecialchars($_SESSION['checkout']['shipping']['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>

        <div class="shipping-method-summary">
            <h4>Shipping Method</h4>
            <p><?php echo htmlspecialchars($_SESSION['shipping_method'] ?? 'Standard Shipping', ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <a href="checkout-shipping.php" class="text-btn">
            <i class="fas fa-pencil-alt"></i> Edit
        </a>
    </div>

    <div class="secure-checkout">
        <i class="fas fa-lock"></i>
        <span>Secure Checkout</span>
    </div>
</div>

          
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="checkout-footer">
      <div class="container">
        <div class="footer-bottom">
          <p>© 2025 MarketPlace. All rights reserved.</p>
          <div class="footer-links">
            <a href="/privacy">Privacy Policy</a>
            <a href="/terms">Terms of Service</a>
            <a href="/help-center">Help Center</a>
          </div>
        </div>
      </div>
    </footer>

    <script src="payment.js"></script>
  </body>
</html>
