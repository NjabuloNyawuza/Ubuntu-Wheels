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


require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


$email = "";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email'] ?? ''); 
    

    if (empty($email)) {
        $error = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        try {
         
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
             
                $token = bin2hex(random_bytes(32));
             
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                
                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user['id'], $email, $token, $expires]);

                
              
                $mail = new PHPMailer(true); 

          
                $mail->SMTPDebug = SMTP::DEBUG_SERVER; 
                $mail->Debugoutput = 'html'; 
                
                try {
                   
                    $mail->isSMTP();                                           
                    $mail->Host       = 'smtp.gmail.com';                       
                    $mail->SMTPAuth   = true;                                   
                  
                    $mail->Username   = 'njabulonyawuza12@gmail.com';           
                 
                    $mail->Password   = 'opsovzbuojcakfby';             
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;           
                    $mail->Port       = 465;                                   

                
                    $mail->setFrom('YOUR_GMAIL_EMAIL@gmail.com', 'UbuntuTrade Password Reset'); 
                    $mail->addAddress($email, $user['name']);                
                    $mail->addReplyTo('no-reply@ubuntutrade.com', 'No-reply'); 

                   
                    $resetLink = "http://localhost/ubuntutrade/reset-password.php?token=" . $token; 
                    
                    $mail->isHTML(true);                                       
                    $mail->Subject = 'Password Reset Request for UbuntuTrade';
                    $mail->Body    = '
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { width: 80%; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; }
                                .header { background-color: #ff385c; color: white; padding: 10px 20px; text-align: center; border-radius: 8px 8px 0 0; }
                                .content { padding: 20px; }
                                .button { display: inline-block; padding: 10px 20px; margin: 20px 0; background-color: #ff385c; color: white; text-decoration: none; border-radius: 5px; }
                                .footer { text-align: center; font-size: 0.8em; color: #666; margin-top: 20px; }
                            </style>
                        </head>
                        <body>
                            <div class="container">
                                <div class="header">
                                    <h2>UbuntuTrade Password Reset</h2>
                                </div>
                                <div class="content">
                                    <p>Dear ' . htmlspecialchars($user['name']) . ',</p>
                                    <p>You have requested to reset your password for your UbuntuTrade account. Please click the button below to reset your password:</p>
                                    <a href="' . htmlspecialchars($resetLink) . '" class="button">Reset Your Password</a>
                                    <p>This link will expire in 1 hour. If you did not request a password reset, please ignore this email.</p>
                                    <p>If you are having trouble clicking the password reset button, copy and paste the URL below into your web browser:</p>
                                    <p><a href="' . htmlspecialchars($resetLink) . '">' . htmlspecialchars($resetLink) . '</a></p>
                                    <p>Thank you,</p>
                                    <p>The UbuntuTrade Team</p>
                                </div>
                                <div class="footer">
                                    <p>&copy; ' . date("Y") . ' UbuntuTrade. All rights reserved.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                    ';
                 
                    $mail->AltBody = 'Dear ' . htmlspecialchars($user['name']) . ',\n\nYou have requested to reset your password for your UbuntuTrade account. Please use the following link to reset your password: ' . $resetLink . '\n\nThis link will expire in 1 hour. If you did not request a password reset, please ignore this email.\n\nThank you,\nThe UbuntuTrade Team';

                    $mail->send();
                    $success = "Password reset instructions have been sent to your email address.";
                    $email = ""; 
                } catch (Exception $mailError) {
                  
                    error_log("PHPMailer Error: " . $mailError->getMessage());
                   
                    $success = "If your email address exists in our database, you will receive a password reset link shortly.";

                }
                
            } else {
              
                $success = "If your email address exists in our database, you will receive a password reset link shortly.";
                $email = ""; 
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
            error_log("Forgot Password PDO Error: " . $e->getMessage());
        } catch (Exception $e) {
            $error = 'An unexpected error occurred: ' . $e->getMessage();
            error_log("Forgot Password General Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | UbuntuTrade</title>
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
            <div class="auth-card forgot-card">
                <div class="auth-header">
                    <h2>Forgot Password</h2>
                    <p>Enter your email to reset your password</p>
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
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="forgot-form">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-paper-plane"></i>
                        Send Reset Link
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Remember your password? <a href="login.php">Back to Login</a></p>
                </div>
            </div>
            
            <div class="auth-features">
                <div class="auth-feature">
                    <div class="feature-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Secure Password Reset</h3>
                        <p>We'll send you a secure link to reset your password</p>
                    </div>
                </div>
                
                <div class="auth-feature">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Account Protection</h3>
                        <p>Your account security is our top priority</p>
                    </div>
                </div>
                
                <div class="auth-feature">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Need Help?</h3>
                        <p>Contact our support team for assistance with your account</p>
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
            <img class="logo" src="images/logo.png" alt="UbuntuTrade Logo" />
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