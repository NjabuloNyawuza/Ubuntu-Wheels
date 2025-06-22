<?php
session_start();
require_once 'db_connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';


$prefill_recipient_id = filter_input(INPUT_GET, 'recipient_id', FILTER_VALIDATE_INT);
$prefill_product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
$prefill_subject = '';
$prefill_recipient_name = '';
$prefill_product_name = '';

if ($prefill_recipient_id) {
    try {
        $stmt_recip = $pdo->prepare("SELECT name FROM Users WHERE id = ?"); 
        $stmt_recip->execute([$prefill_recipient_id]);
        $prefill_recipient_name = $stmt_recip->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching recipient name: " . $e->getMessage());
    }
}

if ($prefill_product_id) {
    try {
        $stmt_prod = $pdo->prepare("SELECT ProductName FROM products WHERE ProductID = ?"); 
        $stmt_prod->execute([$prefill_product_id]);
        $prefill_product_name = $stmt_prod->fetchColumn();
        if ($prefill_product_name) {
            $prefill_subject = "Inquiry about: " . $prefill_product_name;
        }
    } catch (PDOException $e) {
        error_log("Error fetching product name: " . $e->getMessage());
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_new_message') {
    $recipient_identifier = trim(filter_input(INPUT_POST, 'recipient_identifier', FILTER_SANITIZE_STRING)); 
    $subject = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING));
    $message_text = trim(filter_input(INPUT_POST, 'message_text', FILTER_SANITIZE_STRING));
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    $recipient_id = null;


    if (is_numeric($recipient_identifier)) { 
        $recipient_id = (int)$recipient_identifier;
        if ($recipient_id === $user_id) {
            $error_message = "You cannot send a message to yourself.";
        } else {
        
            try {
                $stmt_check = $pdo->prepare("SELECT id FROM Users WHERE id = ?");
                $stmt_check->execute([$recipient_id]);
                if (!$stmt_check->fetch()) {
                    $error_message = "Recipient user not found.";
                    $recipient_id = null; 
                }
            } catch (PDOException $e) {
                $error_message = "Database error verifying recipient.";
            }
        }
    } else { 
        try {
            $stmt_recip = $pdo->prepare("SELECT id FROM Users WHERE name = ? OR email = ?"); 
            $stmt_recip->execute([$recipient_identifier, $recipient_identifier]);
            $found_user = $stmt_recip->fetch(PDO::FETCH_ASSOC);

            if ($found_user) {
                $recipient_id = $found_user['id'];
                if ($recipient_id === $user_id) {
                    $error_message = "You cannot send a message to yourself.";
                    $recipient_id = null; 
                }
            } else {
                $error_message = "Recipient user ('" . htmlspecialchars($recipient_identifier) . "') not found by username or email.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error finding recipient.";
            error_log("Compose message recipient error: " . $e->getMessage());
        }
    }


    if ($error_message === '' && $recipient_id && !empty($message_text)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, receiver_id, subject, message_text, product_id, sent_at)
                VALUES (:sender_id, :receiver_id, :subject, :message_text, :product_id, NOW())
            ");
            $stmt->bindParam(':sender_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':receiver_id', $recipient_id, PDO::PARAM_INT);
            $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
            $stmt->bindParam(':message_text', $message_text, PDO::PARAM_STR);
            if ($product_id === false || $product_id === null) {
                $stmt->bindValue(':product_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            }

            if ($stmt->execute()) {
                $success_message = "Message sent successfully!";
             
                $recipient_identifier = '';
                $subject = '';
                $message_text = '';
                $product_id = null; 
                $prefill_recipient_id = null;
                $prefill_product_id = null;
                $prefill_subject = '';
                $prefill_recipient_name = '';
                $prefill_product_name = '';

            } else {
                $error_message = "Failed to send message.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
            error_log("Compose message error: " . $e->getMessage());
        }
    } elseif ($error_message === '') {
        $error_message = "Please ensure all required fields are filled correctly.";
    }
}


$username = '';
if (isset($_SESSION['user_id'])) {
    try {
        $stmt_user = $pdo->prepare("SELECT name FROM Users WHERE id = ?"); 
        $stmt_user->execute([$_SESSION['user_id']]);
        $fetched_username = $stmt_user->fetchColumn();
        if ($fetched_username) {
            $username = $fetched_username;
        }
    } catch (PDOException $e) {
        error_log("Error fetching username in compose_message.php for header: " . $e->getMessage());
        $username = 'Profile';
    }
}

$cart_item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_item_count = count($_SESSION['cart']);
}


$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compose Message - UbuntuTrade</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header id="header">
        <a href="index.php"><img src="images/logo.png" class="logo" alt="UbuntuTrade Logo"></a>
        
        <div>
            <ul id="navbar">
                <li><a href="index.php" class="<?php echo ($current_page == 'index.php' ? 'active' : ''); ?>">Home</a></li>
                <li><a href="shop.php" class="<?php echo ($current_page == 'shop.php' ? 'active' : ''); ?>">Shop</a></li>
                <li><a href="blog.php" class="<?php echo ($current_page == 'blog.php' ? 'active' : ''); ?>">Blog</a></li>
                <li><a href="about.php" class="<?php echo ($current_page == 'about.php' ? 'active' : ''); ?>">About</a></li>
                <li><a href="contact.php" class="<?php echo ($current_page == 'contact.php' ? 'active' : ''); ?>">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="messages.php" class="<?php echo ($current_page == 'messages.php' ? 'active' : ''); ?>"><i class="fas fa-envelope"></i> Messages</a></li>
                    <li><a href="profile.php" class="<?php echo ($current_page == 'profile.php' ? 'active' : ''); ?>"><i class="fas fa-user"></i> <?php echo htmlspecialchars($username ?: 'Profile'); ?></a></li>
                    <li><a href="sell.php" class="sell-button <?php echo ($current_page == 'sell.php' ? 'active' : ''); ?>"><i class="fas fa-plus"></i> Sell</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="<?php echo ($current_page == 'login.php' ? 'active' : ''); ?>">Login</a></li>
                    <li><a href="register.php" class="<?php echo ($current_page == 'register.php' ? 'active' : ''); ?>">Register</a></li>
                    <li><a href="sell.php" class="sell-button <?php echo ($current_page == 'sell.php' ? 'active' : ''); ?>"><i class="fas fa-plus"></i> Sell</a></li>
                <?php endif; ?>
                <li><a href="cart.php" class="cart-link <?php echo ($current_page == 'cart.php' ? 'active' : ''); ?>"><i class="fas fa-shopping-bag"></i> <span class="badge"><?php echo $cart_item_count; ?></span></a></li>
                <li><a href="#" id="close"><i class="far fa-times"></i></a></li>
            </ul>
        </div>
        
        <div id="mobile">
            <i id="bar" class="fas fa-outdent"></i>
            <a href="cart.php" class="cart-link"><i class="fas fa-shopping-bag"></i> <span class="badge"><?php echo $cart_item_count; ?></span></a>
        </div>
    </header>

    <div class="mobile-search">
        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search products...">
        </div>
    </div>

    <div class="container section-p1">
        <h2>Compose New Message</h2>

        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form action="compose_message.php" method="POST" class="compose-form">
            <input type="hidden" name="action" value="send_new_message">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($prefill_product_id); ?>">

            <div class="form-group">
                <label for="recipient_identifier">To: <span class="required">*</span></label>
                <input type="text" id="recipient_identifier" name="recipient_identifier"
                       placeholder="Enter recipient's username or email (e.g., 'JohnDoe' or 'john@example.com')"
                       value="<?php echo htmlspecialchars((string)($prefill_recipient_name ?: $prefill_recipient_id)); ?>" required>
            </div>

            <div class="form-group">
                <label for="subject">Subject: <span class="required">*</span></label>
                <input type="text" id="subject" name="subject"
                       placeholder="Message Subject" value="<?php echo htmlspecialchars($prefill_subject); ?>" required>
            </div>

            <?php if ($prefill_product_name): ?>
            <div class="form-group">
                <label>About Product:</label>
                <p><strong><a href="product.php?id=<?php echo htmlspecialchars($prefill_product_id); ?>"><?php echo htmlspecialchars($prefill_product_name); ?></a></strong></p>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="message_text">Message: <span class="required">*</span></label>
                <textarea id="message_text" name="message_text" rows="10" placeholder="Type your message here..." required></textarea>
            </div>

            <button type="submit" class="btn-send-message">Send Message</button>
            <a href="messages.php" class="btn-cancel">Cancel</a>
        </form>
    </div>

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
            <img class="logo" src="images/logo.png" alt="UbuntuTrade Logo">
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
                <img src="images/pay/app.jpg" alt="App Store">
                <img src="images/pay/play.jpg" alt="Google Play">
            </div>
            <p>Secure Payment Gateways</p>
            <img src="images/pay/pay.png" alt="Payment Methods">
        </div>

        <div class="copyright">
            <p>&copy; 2025 - UbuntuTrade. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
   
        const header = document.getElementById('header');
        const bar = document.getElementById('bar');
        const nav = document.getElementById('navbar');
        const close = document.getElementById('close');

      
        window.addEventListener('scroll', () => {
            if (window.scrollY > 0) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

    
        if (bar) {
            bar.addEventListener('click', () => {
                nav.classList.add('active');
            });
        }

        if (close) {
            close.addEventListener('click', () => {
                nav.classList.remove('active');
            });
        }
    </script>
</body>
</html>