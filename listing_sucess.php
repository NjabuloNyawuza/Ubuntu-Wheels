<?php
session_start();

// require_once 'db_connection.php'; 

$listing_id = isset($_GET['id']) ? (int)$_GET['id'] : null;


// if ($listing_id) {
//     try {
//         $stmt = $pdo->prepare("SELECT title FROM listings WHERE listing_id = ?");
//         $stmt->execute([$listing_id]);
//         $listing = $stmt->fetch(PDO::FETCH_ASSOC);
//         $listing_title = $listing ? htmlspecialchars($listing['title']) : "your item";
//     } catch (PDOException $e) {
//         $listing_title = "your item (error fetching details)";
//     }
// } else {
//     $listing_title = "your item";
// }
$listing_title = "your item"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Created Successfully!</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="sell.css"> <style>
        .success-message {
            padding: 60px 0;
            text-align: center;
            background-color: #f9f9f9;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 90%;
            text-align: center;
        }
        .success-icon {
            color: #28a745; 
            font-size: 80px;
            margin-bottom: 20px;
        }
        .success-card h2 {
            font-size: 2.2em;
            color: #333;
            margin-bottom: 15px;
        }
        .success-card p {
            font-size: 1.1em;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .success-actions a {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        .btn-view-listings {
            background-color: #088178;
            color: #fff;
        }
        .btn-view-listings:hover {
            background-color: #066d64;
        }
        .btn-sell-another {
            background-color: #f0f0f0;
            color: #088178;
            border: 1px solid #088178;
        }
        .btn-sell-another:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
    <section id="header">
        <a href="index.php"><img src="images/logo.png" class="logo" alt="UbuntuTrade Logo"></a>
        <div>
            <ul id="navbar">
                <li><a href="index.php">Home</a></li>
                <li><a href="browse.php">Browse</a></li>
                <li><a href="categories.php">Categories</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php">My Account</a></li>
                    <li><a href="sell.php" class="sell-button">Sell Item</a></li>
                    <li><a href="messages.php"><i class="far fa-envelope"></i></a></li>
                    <li><a href="notifications.php"><i class="far fa-bell"></i></a></li>
                    <li><a href="cart.php"><i class="far fa-shopping-bag"></i></a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="sell.php" class="sell-button">Sell Item</a></li>
                <?php endif; ?>
                <li><a href="#" id="close"><i class="far fa-times"></i></a></li>
            </ul>
        </div>
        <div id="mobile">
            <a href="cart.php"><i class="far fa-shopping-bag"></i></a>
            <i id="bar" class="fas fa-outdent"></i>
        </div>
    </section>

    <section class="success-message">
        <div class="container">
            <div class="success-card">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Listing Created Successfully!</h2>
                <p>Your item "<?php echo $listing_title; ?>" has been listed on UbuntuTrade and is now visible to potential buyers.</p>
                <div class="success-actions">
                    <?php if ($listing_id): ?>
                        <a href="listing_details.php?id=<?php echo $listing_id; ?>" class="btn-view-listings">View Listing</a>
                    <?php endif; ?>
                    <a href="my_listings.php" class="btn-sell-another">View My Listings</a>
                    <a href="sell.php" class="btn-sell-another">Sell Another Item</a>
                </div>
            </div>
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
</body>
</html>