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
$saved_products = [];
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_saved') {
    $product_id_to_remove = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    if ($product_id_to_remove) {
        try {
            $stmt = $pdo->prepare("
                DELETE FROM wishlist
                WHERE user_id = :user_id AND product_id = :product_id
            ");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $product_id_to_remove, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $success_message = "Product removed from your saved list.";
            } else {
                $error_message = "Failed to remove product from your saved list.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
            error_log("Remove saved product error: " . $e->getMessage());
        }
    } else {
        $error_message = "Invalid product ID for removal.";
    }
}



try {
    $stmt = $pdo->prepare("
        SELECT
            p.ProductID,
            p.ProductName,
            p.Description,
            p.Price,
            p.ImageURL,
            p.Location,
            w.added_at  /* Use added_at from wishlist table */
        FROM
            wishlist w
        JOIN
            products p ON w.product_id = p.ProductID
        WHERE
            w.user_id = :user_id
        ORDER BY
            w.added_at DESC /* Order by added_at */
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $saved_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database error fetching saved products: " . $e->getMessage();
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
        error_log("Error fetching username in saved_cars.php for header: " . $e->getMessage());
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
    <title>Your Saved Cars - UbuntuTrade</title>
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
                    <li><a href="notifications.php" class="<?php echo ($current_page == 'notifications.php' ? 'active' : ''); ?>"><i class="fas fa-bell"></i> Notifications</a></li>
                    <li><a href="saved_cars.php" class="active"><i class="fas fa-heart"></i> Saved Cars</a></li> <li><a href="profile.php" class="<?php echo ($current_page == 'profile.php' ? 'active' : ''); ?>"><i class="fas fa-user"></i> <?php echo htmlspecialchars($username ?: 'Profile'); ?></a></li>
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
        <h2>Your Saved Cars</h2>

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

        <div class="product-grid">
            <?php if (!empty($saved_products)): ?>
                <?php foreach ($saved_products as $product): ?>
                    <div class="product-item">
                        <a href="product.php?id=<?php echo htmlspecialchars($product['ProductID']); ?>">
                            <img src="<?php echo htmlspecialchars($product['ImageURL'] ?: 'images/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                            <div class="des">
                                <span><?php echo htmlspecialchars($product['Location']); ?></span>
                                <h5><?php echo htmlspecialchars($product['ProductName']); ?></h5>
                                <h4>R<?php echo number_format($product['Price'], 2); ?></h4>
                            </div>
                        </a>
                        <form method="POST" action="saved_cars.php" class="remove-saved-form">
                            <input type="hidden" name="action" value="remove_saved">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['ProductID']); ?>">
                            <button type="submit" class="remove-btn" title="Remove from Saved"><i class="fas fa-times-circle"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-products">You haven't saved any cars yet.</p>
            <?php endif; ?>
        </div>
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
            <a href="saved_cars.php">My Wishlist</a> <a href="my-listings.php">My Listings</a>
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