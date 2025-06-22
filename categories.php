<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'db_connection.php';
session_start();

$isLoggedIn = isset($_SESSION['user_id']);


$allCategories = [];
try {
 
    $stmt = $pdo->prepare("
        SELECT
            cbt.id,
            cbt.name,
            cbt.icon_class,
            cbt.slug,
            COUNT(p.ProductID) AS product_count
        FROM car_body_types cbt
        LEFT JOIN Products p ON cbt.id = p.CategoryID AND p.status = 'active'
        GROUP BY cbt.id, cbt.name, cbt.icon_class, cbt.slug
        ORDER BY cbt.name ASC
    ");
    $stmt->execute();
    $dbCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dbCategories as $category) {
        
        $image_url = 'https://via.placeholder.com/600x400?text=' . urlencode($category['name']);
        $color_hex = '#3a86ff';

        switch ($category['slug']) {
            case 'sedan':
                $image_url = 'img/categories_banner_sedan_image.png'; 
                $color_hex = '#587399'; 
                break;
            case 'suv':
                $image_url = 'img/categories_banner_suv_image.png'; 
                $color_hex = '#7d3c98'; 
                break;
            case 'hatchback':
                $image_url = 'img/categories_banner_hatchback_image.png'; 
                $color_hex = '#2ecc71'; 
                break;
            case 'bakkie': 
                $image_url = 'img/categories_banner_bakkie_image.png'; 
                $color_hex = '#e74c3c'; 
                break;
            case 'coupe':
                $image_url = 'https://images.unsplash.com/photo-1600860555776-bb583796d194?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=MnwzNTc5Mjh8MHwxfGFsbHwxfHx8fHwyfHwxNjk5MjQyMzAw&ixlib=rb-4.0.3&q=80&w=1080'; // Example Coupe image
                $color_hex = '#f1c40f'; 
                break;
            case 'convertible':
                $image_url = 'https://images.unsplash.com/photo-1633513101675-9b2f27a6e11c?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=MnwzNTc5Mjh8MHwxfGFsbHwxfHx8fHwyfHwxNjk5MjQyMzI1&ixlib=rb-4.0.3&q=80&w=1080'; // Example Convertible image
                $color_hex = '#9b59b6'; 
                break;
            case 'minibus':
                $image_url = 'https://images.unsplash.com/photo-1549419138-1647895f8e5f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=MnwzNTc5Mjh8MHwxfGFsbHwxfHx8fHwyfHwxNjk5MjQyMzU4&ixlib=rb-4.0.3&q=80&w=1080'; // Example Minibus image
                $color_hex = '#34495e'; 
                break;
            case 'station-wagon':
                $image_url = 'https://images.unsplash.com/photo-1541887309990-2591e1d0e9b9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=MnwzNTc5Mjh8MHwxfGFsbHwxfHx8fHwyfHwxNjk5MjQyNDA1&ixlib=rb-4.0.3&q=80&w=1080'; // Example Station Wagon image
                $color_hex = '#1abc9c'; 
                break;
            case 'commercial':
                $image_url = 'https://images.unsplash.com/photo-1558900609-b68420c22c15?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=MnwzNTc5Mjh8MHwxfGFsbHwxfHx8fHwyfHwxNjk5MjQyNDMw&ixlib=rb-4.0.3&q=80&w=1080'; // Example Commercial vehicle image
                $color_hex = '#d35400'; 
                break;
            case 'other':
                $image_url = 'https://images.unsplash.com/photo-1502877338535-766e11c2dae3?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=MnwzNTc5Mjh8MHwxfGFsbHwxfHx8fHwyfHwxNjk5MjQyNDg0&ixlib=rb-4.0.3&q=80&w=1080'; // Generic car image
                $color_hex = '#95a5a6'; 
                break;
            default:
              
                $image_url = 'https://via.placeholder.com/600x400?text=Car+Category';
                $color_hex = '#3a86ff'; 
                break;
        }

        $allCategories[] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'icon' => $category['icon_class'], 
            'color' => $color_hex,
            'count' => $category['product_count'],
          
            'image' => $image_url
        ];
    }

} catch (PDOException $e) {
    echo "Error fetching categories: " . $e->getMessage();

    $allCategories = [];
}


usort($allCategories, function($a, $b) {
    return $b['count'] <=> $a['count'];
});
$trendingCategories = array_slice($allCategories, 0, 4);


$popularSearches = [
    'Toyota', 'Ford Ranger', 'BMW 3 Series', 'VW Polo Vivo', 'Mercedes-Benz C-Class',
    'SUV', 'Hatchback', 'Sedan', 'Automatic cars', 'Diesel cars'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - UbuntuTrade</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="categories.css">
</head>
<body>
    <section id="header">
        <a href="index.php"><img src="img/ubuntuWheels_logo.png" class="logo" alt="UbuntuWheels Logo"></a>
        
        <div class="search-container">
<input type="text" placeholder="Search for make, model, etc...">
<i class="fa fa-search"></i>
</div>
        
        <div>
            <ul id="navbar">
                <li><a href="index.php">Home</a></li>
                <li><a href="browse.php">Browse</a></li>
                <li><a href="categories.php" class="active">Categories</a></li>
                <?php if($isLoggedIn): ?>
                    <li><a href="dashboard.php">My Account</a></li>
                    <li><a href="sell.php" class="sell-button">Sell Item</a></li>
                    <li><a href="message.php"><i class="far fa-envelope"></i></a></li>
                    <li><a href="notifications.php"><i class="far fa-bell"></i></a></li>
                    <li><a href="cart.php" class="cart-link">
                        <i class="far fa-shopping-bag"></i>
                        <span class="badge">0</span>
                    </a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="sell.php" class="sell-button">Sell Item</a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div id="mobile">
            <a href="cart.php"><i class="far fa-shopping-bag"></i></a>
            <i id="bar" class="fas fa-outdent"></i>
        </div>
    </section>

    <div class="mobile-search">
        <div class="search-container">
            <form action="browse.php" method="GET">
                <input type="text" name="search" placeholder="Search for anything...">
                <i class="far fa-search"></i>
            </form>
        </div>
    </div>

    <section id="categories-header">
        <div class="container">
            <h1>Browse Car Body Types</h1>
            <p>Find what you're looking for by body type</p>
        </div>
    </section>

    <section id="trending-categories" class="section-p1">
        <div class="container">
            <div class="section-title">
                <h2>Most Popular Body Types</h2>
                <p>The car body types with the most listings</p>
            </div>
            
            <div class="trending-container">
                <?php foreach($trendingCategories as $category): ?>
                    <div class="trending-category" style="--category-color: <?php echo $category['color']; ?>">
                        <div class="trending-image">
                            <img src="<?php echo $category['image']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                            <div class="trending-overlay"></div>
                        </div>
                        <div class="trending-content">
                            <div class="trending-icon">
                                <i class="<?php echo $category['icon']; ?>"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                            <p><?php echo number_format($category['count']); ?> listings</p>
                            <a href="browse.php?category=<?php echo $category['id']; ?>" class="btn-explore">Explore</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="all-categories" class="section-p1">
        <div class="container">
            <div class="section-title">
                <h2>All Car Body Types</h2>
                <p>Browse all available body types</p>
            </div>
            
            <div class="categories-grid">
                <?php foreach($allCategories as $category): ?>
                    <div class="category-card">
                        <div class="category-header" style="background-color: <?php echo $category['color']; ?>">
                            <div class="category-icon">
                                <i class="<?php echo $category['icon']; ?>"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                            <p><?php echo number_format($category['count']); ?> listings</p>
                        </div>
                        <div class="category-body">
                            <ul class="subcategory-list">
                                <li>
                                    <a href="browse.php?category=<?php echo $category['id']; ?>">
                                        View all <?php echo htmlspecialchars($category['name']); ?>s
                                        <span class="subcategory-count"><?php echo number_format($category['count']); ?></span>
                                    </a>
                                </li>
                            </ul>
                            <div class="view-all">
                                <a href="browse.php?category=<?php echo $category['id']; ?>">View All</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="popular-searches" class="section-p1">
        <div class="container">
            <div class="section-title">
                <h2>Popular Car Searches</h2>
                <p>What others are looking for</p>
            </div>
            
            <div class="search-tags">
                <?php foreach($popularSearches as $search): ?>
                    <a href="browse.php?search=<?php echo urlencode($search); ?>" class="search-tag">
                        <?php echo htmlspecialchars($search); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="category-banner" class="section-p1">
        <div class="container">
            <div class="banner-content">
                <h2>Ready to Sell Your Car?</h2>
                <p>List your car for free and start selling to thousands of buyers in your area.</p>
                <a href="sell.php" class="btn-sell-now">Sell Now</a>
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
            <img class="logo" src="img/ubuntuWheels_logo.png" alt="UbuntuTrade Logo">
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
</body>
</html>