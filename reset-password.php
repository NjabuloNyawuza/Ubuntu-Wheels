<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php'; 
session_start();


if (isset($_SESSION['user_id'])) {
    header("Location: index.php"); 
    exit();
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$showForm = false; 


if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resetRequest) {
         
            if (strtotime($resetRequest['expires_at']) > time()) {
                $showForm = true; 
            } else {
                $error = "The password reset link has expired. Please request a new one.";
            }
        } else {
            $error = "Invalid password reset token.";
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
        error_log("Reset Password PDO Error: " . $e->getMessage());
    }
} else {
    $error = "No password reset token provided.";
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && $showForm) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $form_token = $_POST['token'] ?? ''; 

    $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    $stmt->execute([$form_token]);
    $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resetRequest || strtotime($resetRequest['expires_at']) <= time()) {
        $error = "Invalid or expired password reset request. Please try again.";
        $showForm = false;
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) { 
        $error = "New password must be at least 8 characters long.";
    } else {
        try {
           
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

         
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $resetRequest['user_id']]);

       
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$form_token]);
            
            $pdo->commit(); 

            $success = "Your password has been successfully reset. You can now login with your new password.";
            $showForm = false; 
        } catch (PDOException $e) {
            $pdo->rollBack(); 
            $error = 'Database error: ' . $e->getMessage();
            error_log("Reset Password Update PDO Error: " . $e->getMessage());
        } catch (Exception $e) {
            $error = 'An unexpected error occurred: ' . $e->getMessage();
            error_log("Reset Password General Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | UbuntuTrade</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <section id="header">
        <a href="index.php"><img src="images/logo.png" class="logo" alt="UbuntuTrade Logo" /></a>

        <div>
            <ul id="navbar">
                <li><a href="index.php">Home</a></li>
                <li><a href="browse.php">Browse</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="sell.php" class="sell-button">Sell Item</a></li>
                <li><a href="#" id="close"><i class="far fa-times"></i></a></li>
            </ul>
        </div>
        <div id="mobile">
            <a href="cart.php" class="cart-link">
                <i class="far fa-shopping-bag"></i>
                <span class="badge">0</span>
            </a>
            <i id="bar" class="fas fa-outdent"></i>
        </div>
    </section>

    <section id="auth-container" class="section-p1">
        <div class="container">
            <div class="auth-card reset-card">
                <div class="auth-header">
                    <h2>Reset Password</h2>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                        <p><a href="login.php">Click here to Login</a></p>
                    </div>
                <?php endif; ?>

                <?php if ($showForm): ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . htmlspecialchars($token); ?>" method="post" id="reset-form">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-login">
                            <i class="fas fa-sync-alt"></i>
                            Reset Password
                        </button>
                    </form>
                <?php endif; ?>
                
                <?php if (!$showForm && empty($success)):  ?>
                    <div class="auth-footer">
                        <p><a href="login.php">Back to Login</a></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="auth-features">
                <div class="auth-feature">
                    <div class="feature-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Secure Password Reset</h3>
                        <p>Your new password will be securely updated</p>
                    </div>
                </div>
                
                <div class="auth-feature">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Account Protection</h3>
                        <p>We ensure your account remains safe</p>
                    </div>
                </div>
                
                <div class="auth-feature">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Need Help?</h3>
                        <p>Contact our support team for assistance</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="newsletter" class="section-p1 section-m1">
        <div class="newstext">
            <h4>Sign Up For Our Newsletter</h4>
            <p>Get email updates about new listings in your area and <span>special offers.</span></p>
        </div>
        <div class="form">
            <input type="email" placeholder="Your email address">
            <button class="normal">Sign Up</button>
        </div>
    </section>

    <footer class="section-p1">
        <div class="col">
            <img class="logo" src="images/logo.png" class="logo" alt="UbuntuTrade Logo" />
            <h4>Contact</h4>
            <p><strong>Address:</strong> 123 Market Street, Cape Town, South Africa</p>
            <p><strong>Phone:</strong> +27 21 123 4567</p>
            <p><strong>Hours:</strong> 10:00 - 18:00, Mon - Fri</p>
            <div class="follow">
                <h4>Follow Us</h4>
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
            <a href="about.php">About Us</a>
            <a href="how-it-works.php">How It Works</a>
            <a href="privacy.php">Privacy Policy</a>
            <a href="terms.php">Terms & Conditions</a>
            <a href="contact.php">Contact Us</a>
        </div>

        <div class="col">
            <h4>My Account</h4>
            <a href="login.php">Sign In</a>
            <a href="cart.php">View Cart</a>
            <a href="wishlist.php">My Wishlist</a>
            <a href="my-listings.php">My Listings</a>
            <a href="help.php">Help</a>
        </div>

        <div class="col">
            <h4>Sell</h4>
            <a href="create-listing.php">Create Listing</a>
            <a href="seller-guide.php">Seller Guide</a>
            <a href="shipping.php">Shipping Options</a>
            <a href="seller-protection.php">Seller Protection</a>
            <a href="seller-faq.php">Seller FAQ</a>
        </div>

        <div class="col install">
            <h4>Install App</h4>
            <p>From App Store or Google Play</p>
            <div class="row">
                <img src="images/pay/app.jpg" alt="App Store" />
                <img src="images/pay/play.jpg" alt="Google Play" />
            </div>
            <p>Secure Payment Gateways</p>
            <img src="images/pay/pay.png" alt="Payment Methods" />
        </div>

        <div class="copyright">
            <p>&copy; 2025 - UbuntuTrade. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script src="auth.js"></script>
</body>
</html>