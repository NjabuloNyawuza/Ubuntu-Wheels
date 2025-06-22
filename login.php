<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';
session_start();

// --- Remember Me Auto-login Block ---
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $remember_token = $_COOKIE['remember_token'];

    try {
        $stmt = $pdo->prepare("SELECT id, name, email, password_hash, is_admin, avatar, remember_expiry FROM users WHERE remember_token = :token");
        $stmt->bindParam(':token', $remember_token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['remember_expiry'] > time()) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $_SESSION['user_avatar'] = $user['avatar'] ?? 'images/avatars/default.jpg';

            $new_token = bin2hex(random_bytes(32));
            $new_expiry = time() + (86400 * 30);
            $stmt_update_token = $pdo->prepare("UPDATE users SET remember_token = :new_token, remember_expiry = :new_expiry WHERE id = :user_id");
            $stmt_update_token->bindParam(':new_token', $new_token);
            $stmt_update_token->bindValue(':new_expiry', $new_expiry, PDO::PARAM_INT);
            $stmt_update_token->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
            $stmt_update_token->execute();

            setcookie('remember_token', $new_token, [
                'expires' => $new_expiry,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            header('Location: index.php');
            exit;
        } else {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
    } catch (PDOException $e) {
        // DEBUGGING CHANGE 1: Display detailed error and exit
        echo '<h1>Remember Me Auto-login Database Error:</h1>';
        echo '<p><strong>Error Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        error_log("Remember Me Auto-login Error: " . $e->getMessage()); // Keep logging
        setcookie('remember_token', '', time() - 3600, '/', '', true, true); // Clear cookie
        exit;
    }
}

// --- Redirect if already logged in ---
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// --- Login Attempt Constants ---
define('MAX_LOGIN_ATTEMPTS', 5);
define('TIME_WINDOW', 300);

// --- Function to get IP address ---
function get_ip_address() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

$current_ip = get_ip_address();

// --- Clear old login attempts ---
try {
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = :ip_address AND attempt_time < (NOW() - INTERVAL :time_window SECOND)");
    $stmt->bindParam(':ip_address', $current_ip);
    $stmt->bindValue(':time_window', TIME_WINDOW, PDO::PARAM_INT);
    $stmt->execute();
} catch (PDOException $e) {
    // DEBUGGING CHANGE 2: Display detailed error and exit
    echo '<h1>Login Attempts Cleanup Database Error:</h1>';
    echo '<p><strong>Error Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    error_log("Error cleaning old login attempts: " . $e->getMessage()); // Keep logging
    exit;
}

// --- Fetch failed login attempts ---
$failed_attempts = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = :ip_address AND attempt_time >= (NOW() - INTERVAL :time_window SECOND)");
    $stmt->bindParam(':ip_address', $current_ip);
    $stmt->bindValue(':time_window', TIME_WINDOW, PDO::PARAM_INT);
    $stmt->execute();
    $failed_attempts = $stmt->fetchColumn();
} catch (PDOException $e) {
    // DEBUGGING CHANGE 3: Display detailed error and exit
    echo '<h1>Login Attempts Fetch Database Error:</h1>';
    echo '<p><strong>Error Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    error_log("Error fetching login attempts: " . $e->getMessage()); // Keep logging
    exit;
}

$email = $_SESSION['old_login_email'] ?? '';
$rememberMe = $_SESSION['old_remember_me'] ?? false;
$errors = $_SESSION['login_errors'] ?? [];

unset($_SESSION['old_login_email']);
unset($_SESSION['old_remember_me']);
unset($_SESSION['login_errors']);

if (empty($_SESSION['csrf_token_login'])) {
    $_SESSION['csrf_token_login'] = bin2hex(random_bytes(32));
}

// --- POST Request Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['old_login_email'] = trim($_POST['email'] ?? '');
    $_SESSION['old_remember_me'] = isset($_POST['remember_me']);

    // CSRF Token Check
    if (!isset($_POST['csrf_token_login']) || !isset($_SESSION['csrf_token_login']) || $_POST['csrf_token_login'] !== $_SESSION['csrf_token_login']) {
        $errors['general'] = 'Security token mismatch. Please try again.';
        unset($_SESSION['csrf_token_login']);
        $_SESSION['login_errors'] = $errors;
        // DEBUGGING CHANGE 4: Comment out redirect to display error on same page
        // header('Location: login.php');
        // exit;
    }

    unset($_SESSION['csrf_token_login']);

    // Too many failed attempts check
    if ($failed_attempts >= MAX_LOGIN_ATTEMPTS) {
        $errors['general'] = 'Too many failed login attempts. Please try again after ' . (TIME_WINDOW / 60) . ' minutes.';
        $_SESSION['login_errors'] = $errors;
        // DEBUGGING CHANGE 5: Comment out redirect to display error on same page
        // header('Location: login.php');
        // exit;
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);

    if (empty($email)) {
        $errors['email'] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, password_hash, is_admin, avatar FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Clear login attempts on successful login
                try {
                    $stmt_clear_attempts = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = :ip_address");
                    $stmt_clear_attempts->bindParam(':ip_address', $current_ip);
                    $stmt_clear_attempts->execute();
                } catch (PDOException $e) {
                    // DEBUGGING CHANGE 6: Display detailed error and exit
                    echo '<h1>Login Success - Clear Attempts Database Error:</h1>';
                    echo '<p><strong>Error Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                    error_log("Error clearing login attempts on success: " . $e->getMessage()); // Keep logging
                    exit;
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                $_SESSION['user_avatar'] = $user['avatar'] ?? 'images/avatars/default.jpg';

                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + (86400 * 30);

                    $stmt_token = $pdo->prepare("UPDATE users SET remember_token = :token, remember_expiry = :expiry WHERE id = :user_id");
                    $stmt_token->bindParam(':token', $token);
                    $stmt_token->bindValue(':expiry', $expiry, PDO::PARAM_INT);
                    $stmt_token->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                    $stmt_token->execute();

                    setcookie('remember_token', $token, [
                        'expires' => $expiry,
                        'path' => '/',
                        'domain' => '',
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                } else {
                    $stmt_clear_token = $pdo->prepare("UPDATE users SET remember_token = NULL, remember_expiry = NULL WHERE id = :user_id");
                    $stmt_clear_token->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                    $stmt_clear_token->execute();
                    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
                }

                if ($_SESSION['is_admin']) {
                    header('Location: admin/dashboard.php');
                } else {
                    $redirect_url = $_GET['redirect'] ?? 'index.php';
                    header('Location: ' . $redirect_url);
                }
                exit;

            } else {
                $errors['general'] = 'Invalid email or password';
                // Log failed attempt
                try {
                    $stmt_log_attempt = $pdo->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (:ip_address, NOW())"); // Added attempt_time
                    $stmt_log_attempt->bindParam(':ip_address', $current_ip);
                    $stmt_log_attempt->execute();
                } catch (PDOException $e) {
                    // DEBUGGING CHANGE 7: Display detailed error and exit
                    echo '<h1>Failed Login Attempt Log Database Error:</h1>';
                    echo '<p><strong>Error Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                    error_log("Error logging failed attempt: " . $e->getMessage()); // Keep logging
                    exit;
                }
            }
        } catch (PDOException $e) {
            // DEBUGGING CHANGE 8: Display detailed error and exit for main login query
            echo '<h1>Main Login Query Database Error:</h1>';
            echo '<p><strong>Error Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            error_log("Login PDO Error: " . $e->getMessage()); // Keep logging
            exit;
        } catch (Exception $e) {
            // DEBUGGING CHANGE 9: Display detailed error and exit for general exceptions
            echo '<h1>General Login Error:</h1>';
            echo '<p><strong>Error Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            error_log("Login General Error: " . $e->getMessage()); // Keep logging
            exit;
        }
    }

    // DEBUGGING CHANGE 10: Comment out redirect to display validation errors on same page
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors; // Keep this to populate errors array for display
        // header('Location: login.php');
        // exit;
    }
}


if (!empty($errors)) {
    echo '<div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 20px;">';
    echo '<h2>Login Errors:</h2>';
    foreach ($errors as $field => $message) {
        echo '<p><strong>' . htmlspecialchars($field) . ':</strong> ' . htmlspecialchars($message) . '</p>';
    }
    echo '</div>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ubuntu Wheels</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <section id="header">
        <a href="index.php"><img src="img/ubuntuWheels_logo.png" class="logo" alt="UbuntuWheels Logo"></a>

        <div>
            <ul id="navbar">
                <li><a href="index.php">Home</a></li>
                <li><a href="browse.php">Browse</a></li>
                <li><a href="categories.php">Categories</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="active">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
                <li><a href="sell.php" class="sell-button">Sell Item</a></li>
            </ul>
        </div>

        <div id="mobile">
            <i id="bar" class="fas fa-outdent"></i>
        </div>
    </section>

    <section id="auth-container" class="section-p1">
        <div class="container">
            <div class="auth-card">
                <div class="auth-header">
                    <h2>Welcome Back</h2>
                    <p>Login to your Ubuntu Wheels account</p>
                </div>

                <?php
   
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="success-message">';
                    echo '<i class="fas fa-check-circle success-icon"></i>';
                    echo '<span>' . htmlspecialchars($_SESSION['success_message']) . '</span>';
                    echo '</div>';
                    unset($_SESSION['success_message']); 
                }
   
                if (isset($errors['general'])):
                ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['general']); ?>
                    </div>
                <?php endif; ?>

                <div class="social-auth">
                    <button type="button" class="btn-social google">
                        <i class="fab fa-google"></i>
                        <span>Login with Google</span>
                    </button>
                    <button type="button" class="btn-social facebook">
                        <i class="fab fa-facebook-f"></i>
                        <span>Login with Facebook</span>
                    </button>
                </div>

                <div class="divider">
                    <span>or</span>
                </div>

                <form action="login.php" method="post" id="login-form">
                    <input type="hidden" name="csrf_token_login" value="<?php echo htmlspecialchars($_SESSION['csrf_token_login'] ?? ''); ?>">

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error-text"><?php echo htmlspecialchars($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required>
                            <button type="button" class="toggle-password">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="error-text"><?php echo htmlspecialchars($errors['password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-options">
                        <div class="checkbox-group">
                            <input type="checkbox" id="remember_me" name="remember_me" <?php echo $rememberMe ? 'checked' : ''; ?>>
                            <label for="remember_me">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-login">Login</button>
                    </div>
                </form>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="register.php">Sign Up</a></p>
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
            <img class="logo" src="img/ubuntuWheels_logo.png" alt="UbuntuWheels Logo">
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
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="my-account.php">My Account</a> <a href="my-listings.php">My Listings</a>
                <a href="wishlist.php">My Wishlist</a>
                <a href="cart.php">View Cart</a>
                <a href="help.php">Help</a>
            <?php else: ?>
                <a href="login.php">Sign In</a>
                <a href="register.php">Register</a>
                <a href="help.php">Help</a>
            <?php endif; ?>
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
                <img src="img/app_store_image.png" alt="App Store">
                <img src="img/google_play_image.png" alt="Google Play">
            </div>
            <p>Secure Payment Gateways</p>
            <img src="img/payment_gateway_image.png" alt="Payment Methods">
        </div>

        <div class="copyright">
            <p>&copy; 2025 - Ubuntu Wheels. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script src="auth.js"></script>
</body>
</html>