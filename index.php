<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php'; 
session_start(); 


$location = isset($_SESSION['location']) ? $_SESSION['location'] : 'All South Africa';


$bodyTypes = [];
try {
  
    $stmtBodyTypes = $pdo->query("SELECT id, name, icon_class FROM car_body_types ORDER BY name LIMIT 8");
    $bodyTypes = $stmtBodyTypes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    error_log("Error fetching body types (categories): " . $e->getMessage());
}


$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unreadCount = $stmt->fetchColumn();
}


$notifCount = 0;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $notifQuery = $pdo->prepare("SELECT COUNT(*) FROM Notifications WHERE UserID = ? AND IsRead = 0");
    $notifQuery->execute([$userId]);
    $notifCount = $notifQuery->fetchColumn();
}


$cartItemCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; 


$currentFilter = $_GET['filter'] ?? 'all';


function getActiveClass($filterName, $currentFilter) {
    return ($filterName === $currentFilter) ? 'active' : '';
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>UbuntuWheels - Buy & Sell Pre-owned Cars in South Africa</title>
    <link
      rel="stylesheet"
      href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"
    />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="browse.css" /> 
  </head>

  <body>
    <section id="header">
      <a href="index.php"><img src="img/ubuntuWheels_logo.png" class="logo" alt="UbuntuWheels Logo" /></a>

      <div class="search-container">
<input type="text" placeholder="Search for make, model, etc...">
<i class="fa fa-search"></i>
</div>


<div>
        <ul id="navbar">


          <li><a class="active" href="index.php">Home</a></li>
          <li><a href="browse.php">Browse Cars</a></li>
          <li><a href="categories.php">Car Types</a></li>
          <?php if(isset($_SESSION['user_id'])): ?>
            <li><a href="dashboard.php">My Account</a></li>
            <li><a href="sell.php">Sell Your Car</a></li> 
            <li>
                <a href="message.php"><i class="far fa-envelope"></i>
                <?php if($unreadCount > 0): ?>
                    <span class="badge"><?php echo htmlspecialchars($unreadCount); ?></span>
                <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="notifications.php"><i class="far fa-bell"></i>
                <?php if($notifCount > 0): ?>
                    <span class="badge"><?php echo htmlspecialchars($notifCount); ?></span>
                <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="wishlist.php" class="cart-link"> <i class="far fa-heart"></i>
                <?php if($cartItemCount > 0): ?>
                    <span class="badge"><?php echo htmlspecialchars($cartItemCount); ?></span>
                <?php endif; ?>
                </a>
            </li>
            <li><a href="logout.php">Logout</a></li>
          <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
            <li><a href="sell.php" class="sell-button">Sell Your Car</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div id="mobile">
        <i class="fas fa-search search-icon"></i>
        <?php if(isset($_SESSION['user_id'])): ?>
          <a href="notifications.php"><i class="far fa-bell"></i>
            <?php if($notifCount > 0): ?>
              <span class="badge"><?php echo htmlspecialchars($notifCount); ?></span>
            <?php endif; ?>
          </a>
        <?php endif; ?>
        <a href="saved-cars.php" class="cart-link"> <i class="far fa-heart"></i> <?php if($cartItemCount > 0): ?>
            <span class="badge"><?php echo htmlspecialchars($cartItemCount); ?></span>
          <?php endif; ?>
        </a>
        <i id="bar" class="fas fa-outdent"></i>
      </div>
    </section>

    <div class="mobile-search">
        <div class="search-container">
            <form action="browse.php" method="GET">
                <input type="text" name="search" placeholder="Search for anything..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <button type="submit" style="background: none; border: none; cursor: pointer;"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <section id="hero">
      <h4>Find Your Perfect Ride in South Africa</h4>
      <h2>Discover Quality Pre-owned Vehicles from Your Community</h2>
      <h1>Your Trusted Marketplace for Buying & Selling Cars</h1>
      <p>Connecting Car Buyers and Sellers Across the Nation</p>
      <div class="hero-buttons">
        <button class="primary">Browse Cars</button>
        <button class="secondary">Sell Your Car</button>
      </div>
    </section>

    <section class="category-nav">
    <?php if (!empty($bodyTypes)): ?>
        <?php foreach($bodyTypes as $bodyType): ?>
          <a href="browse.php?category=<?php echo urlencode($bodyType['id']); ?>" class="category-item">
                <div class="category-icon">
                    <i class="<?php echo htmlspecialchars($bodyType['icon_class'] ?? 'fas fa-car'); ?>"></i>
                </div>
                <span>
                    <?php echo htmlspecialchars($bodyType['name']); ?> 
                </span>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No car types available.</p>
    <?php endif; ?>
    <a href="categories.php" class="category-item">
      <div class="category-icon"><i class="fas fa-ellipsis-h"></i></div>
      <span>View All Types</span>
    </a>
</section>

    <section class="location-filter section-p1">
      <h2>Cars Near You</h2>
      <p>Find vehicles in your area or browse nationwide</p>
      
      <div class="location-selector">
        <div class="current-location">
          <i class="fas fa-map-marker-alt"></i>
          <span><?php echo htmlspecialchars($location); ?></span>
          <button class="change-location">Change</button>
        </div>
        
        <div class="distance-filter">
          <label for="distance">Distance:</label>
          <select id="distance" name="distance">
            <option value="5">5 km</option>
            <option value="10">10 km</option>
            <option value="25">25 km</option>
            <option value="50" selected>50 km</option>
            <option value="100">100 km</option>
            <option value="any">Any distance</option>
          </select>
        </div>
      </div>
    </section>

    <section id="feature" class="section-p1">
      <div class="fe-box">
        <img src="img/secure_transactions_image.png" alt="Secure Transactions" /> <h6>Secure Transactions</h6>
      </div>
      <div class="fe-box">
        <img src="img/verified_sellers_and_dealers_image.png" alt="Verified Sellers & Dealers" />
        <h6>Verified Sellers & Dealers</h6>
      </div>
      <div class="fe-box">
        <img src="img/local_viewings_and_test_drives_images.png" alt="Local Viewings & Test Drives" /> <h6>Local Viewings & Test Drives</h6>
      </div>
      <div class="fe-box">
        <img src="img/buyer_protection_and_fraud_prevention_image.png" alt="Buyer Protection & Fraud Prevention" />
        <h6>Buyer Protection & Fraud Prevention</h6>
      </div>
      <div class="fe-box">
        <img src="img/vehicle_history_checks_images.png" alt="Vehicle History Checks" /> <h6>Vehicle History Checks</h6>
      </div>
      <div class="fe-box">
        <img src="img/dedicated_support_image.png" alt="Dedicated Support" /> <h6>Dedicated Support</h6>
      </div>
    </section>

    <section id="product1" class="section-p1">
    <?php
   
    $sectionTitle = "Featured Vehicles";
    $sectionSubtitle = "Discover Top Picks from Sellers Across South Africa";

 
    switch ($currentFilter) {
        case 'all':
            $sectionTitle = "All Cars";
            $sectionSubtitle = "Discover a wide range of vehicles for sale";
            break;
        case 'trending':
            $sectionTitle = "Trending Vehicles";
            $sectionSubtitle = "Most popular and sought-after models right now";
            break;
        case 'new':
            $sectionTitle = "New Arrivals";
            $sectionSubtitle = "The latest cars added to UbuntuWheels";
            break;
        case 'deals':
            $sectionTitle = "Hot Deals & Price Drops";
            $sectionSubtitle = "Grab a bargain on these amazing cars!";
            break;
        case 'premium':
            $sectionTitle = "Premium & Luxury Selection";
            $sectionSubtitle = "High-quality vehicles from verified dealers and private sellers";
            break;
    }
    ?>

    <h2 id="section-title"><?php echo htmlspecialchars($sectionTitle); ?></h2>
    <p id="section-subtitle"><?php echo htmlspecialchars($sectionSubtitle); ?></p>
    
    <div class="filters">
       <a href="index.php?filter=all#product1" class="filter-button <?php echo getActiveClass('all', $currentFilter); ?>">All Cars</a>
        <a href="index.php?filter=trending#product1" class="filter-button <?php echo getActiveClass('trending', $currentFilter); ?>"><i class="fas fa-fire"></i> Trending</a>
        <a href="index.php?filter=new#product1" class="filter-button <?php echo getActiveClass('new', $currentFilter); ?>"><i class="fas fa-star"></i> New</a>
        <a href="index.php?filter=deals#product1" class="filter-button <?php echo getActiveClass('deals', $currentFilter); ?>"><i class="fas fa-tags"></i> Deals</a>
        <a href="index.php?filter=premium#product1" class="filter-button <?php echo getActiveClass('premium', $currentFilter); ?>"><i class="fas fa-gem"></i> Premium</a>
    </div>
    
    <div class="pro-container products-container grid-view">
    <?php
     
        $productWhereClause = "WHERE p.status = 'active'"; 
        $productOrderBy = "ORDER BY p.DateListed DESC"; 
        $limit = 8;

   
        switch ($currentFilter) {
            case 'all':
            
                break;
            case 'trending':
                $productOrderBy = "ORDER BY p.ViewsCount DESC, p.DateListed DESC"; 
                break;
            case 'new':
                $productWhereClause .= " AND p.DateListed >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"; 
                $productOrderBy = "ORDER BY p.DateListed DESC";
                break;
            case 'deals':
           
                $productWhereClause .= " AND p.IsBestSeller = 1"; 
                $productOrderBy = "ORDER BY p.Price ASC, p.DateListed DESC"; 
                break;
            case 'premium':
               
                $productWhereClause .= " AND u.IsVerified = 1"; 
                
                $productOrderBy = "ORDER BY p.Price DESC, p.ViewsCount DESC"; 
                break;
        }

        
        try {
          $stmt = $pdo->query("
          SELECT
              p.ProductID AS id,
              p.ProductName AS title,
              p.Description AS description,
              p.Price AS price,
              p.`Condition` AS `condition`,
              p.Location AS location,
              p.DateListed AS created_at,
              p.ViewsCount AS views,
              p.Featured AS featured,
              p.Make AS make,
              p.Model AS model,
              p.Year AS year,
              p.Mileage AS mileage,
              p.FuelType AS fuel_type,
              p.Transmission AS transmission,
              p.ImageURL AS ImageURL,
              p.SellerID AS user_id,
              cbt.name AS CategoryName,
              u.name AS Username,
              u.avatar AS ProfileImage,
              u.IsVerified AS UserIsVerified,
              u.rating AS UserRating,
              (SELECT COUNT(*) FROM Reviews WHERE user_id = u.id) AS TotalReviews, -- <<< CHANGE THIS LINE
              s.store_name AS SellerStoreName,
              s.status AS SellerStatus
          FROM Products p
          JOIN users u ON p.SellerID = u.id
          LEFT JOIN sellers s ON u.id = s.user_id
          LEFT JOIN car_body_types cbt ON p.CategoryID = cbt.id
          " . $productWhereClause . "
          " . $productOrderBy . "
          LIMIT " . $limit . "
          ");
          $carsToDisplay = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($carsToDisplay) {
                foreach ($carsToDisplay as $car) {
                  
                    $product = [
                        'id' => $car['id'],
                        'title' => $car['title'], 
                        'description' => $car['description'] ?? '',
                        'price' => $car['price'] ?? 0,
                        'category' => $car['CategoryName'] ?? 'N/A', 
                        'condition' => $car['condition'] ?? 'N/A', 
                        'location' => $car['location'] ?? 'South Africa',
                        'date_posted' => $car['created_at'] ?? 'now',
                        'views' => $car['views'] ?? 0,
                        'featured' => (bool)($car['featured'] ?? false), 
                        'make' => $car['make'] ?? 'Unknown Make',
                        'model' => $car['model'] ?? 'Unknown Model',
                        'year' => $car['year'] ?? 'N/A',
                        'mileage' => $car['mileage'] ?? 'N/A',
                        'fuel_type' => $car['fuel_type'] ?? 'N/A',
                        'transmission' => $car['transmission'] ?? 'N/A',
                        'image' => $car['ImageURL'] ?? 'images/default_car.png',
                        'seller' => [
                            'id' => $car['user_id'], 
                            'name' => $car['SellerStoreName'] ?? $car['Username'],
                            'rating' => $car['UserRating'] ?? 0.0,
                            'verified' => (bool)($car['UserIsVerified'] ?? false),
                            'avatar' => $car['ProfileImage'] ?? 'images/default-avatar.png'
                        ],
                        'total_reviews' => $car['TotalReviews'] ?? 0 
                    ];
            ?>
                <div class="product-card">
                    <?php if($product['featured']): ?>
                        <div class="featured-tag">Featured</div>
                    <?php endif; ?>
                    <div class="condition-badge"><?php echo htmlspecialchars($product['condition']); ?></div>
                    
                    <div class="product-image">
                        <a href="product-details.php?id=<?php echo $product['id']; ?>">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                        </a>
                    </div>
                    
                    <div class="product-details">
                        <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                        <h3 class="product-title">
                            <a href="product-details.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['title']); ?></a>
                        </h3>
                        <div class="product-price">R<?php echo number_format($product['price'], 2); ?></div>
                        
                        <div class="product-description">
                            <?php echo htmlspecialchars(substr($product['description'], 0, 150)) . '...'; ?>
                        </div>

                        <div class="car-specs">
                            <span><i class="fas fa-car"></i> <?php echo htmlspecialchars($product['make'] . ' ' . $product['model']); ?></span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($product['year']); ?></span>
                            <span><i class="fas fa-tachometer-alt"></i> <?php echo htmlspecialchars(number_format($product['mileage'])) . ' km'; ?></span>
                            <span><i class="fas fa-gas-pump"></i> <?php echo htmlspecialchars($product['fuel_type']); ?></span>
                            <span><i class="fas fa-cogs"></i> <?php echo htmlspecialchars($product['transmission']); ?></span>
                        </div>
                        
                        <div class="location-info">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($product['location']); ?></span>
                        </div>
                        
                        <div class="seller-info">
                            <div class="seller-avatar">
                                <img src="<?php echo htmlspecialchars($product['seller']['avatar']); ?>" alt="Seller Avatar">
                            </div>
                            <div class="seller-details">
                                <div class="seller-name"><?php echo htmlspecialchars($product['seller']['name']); ?></div>
                                <div class="seller-rating">
                                    <?php 
                                        $rating = $product['seller']['rating'];
                                        for($i = 1; $i <= 5; $i++): 
                                            if($i <= floor($rating)): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif($i - $rating < 1): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; 
                                        endfor; 
                                    ?>
                                    <span><?php echo number_format($rating, 1); ?></span>
                                </div>
                                <?php if($product['seller']['verified']): ?>
                                    <div class="verified-seller">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Verified</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="product-meta">
                            <div class="posted-date">
                                Posted <?php echo date('j M Y', strtotime($product['date_posted'])); ?>
                            </div>
                            <div class="views">
                                <i class="far fa-eye"></i>
                                <span><?php echo $product['views']; ?> views</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="product-actions">
                        <a href="wishlist.php?add=<?php echo $product['id']; ?>" class="action-button wishlist-btn" title="Add to Wishlist">
                            <i class="far fa-heart"></i>
                        </a>
                        <a href="message.php?seller=<?php echo $product['seller']['id']; ?>&product=<?php echo $product['id']; ?>" class="action-button message-btn" title="Message Seller">
                            <i class="far fa-comment-alt"></i>
                        </a>
                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="action-button view-btn" title="View Details">
                            <i class="far fa-eye"></i>
                        </a>
                    </div>
                </div>
            <?php
                }
            } else {
                echo '<p class="no-results">No ' . htmlspecialchars($currentFilter) . ' cars found matching the criteria.</p>';
            }
        } catch (PDOException $e) {
            echo '<p class="error-message">Error fetching ' . htmlspecialchars($currentFilter) . ' cars: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>
      
      <div class="view-more">
        <a href="browse.php?sort=<?php echo htmlspecialchars($currentFilter); ?>" class="btn-view-more">View More <?php echo htmlspecialchars($sectionTitle); ?></a>
      </div>
</section>

    <section class="trust-badges">
      <div class="trust-badge">
        <i class="fas fa-shield-alt"></i>
        <h5>Secure Car Transactions</h5>
        <p>Our platform ensures safe and transparent trading between buyers and sellers</p>
      </div>
      <div class="trust-badge">
        <i class="fas fa-car-check"></i> <h5>Verified Vehicles & Sellers</h5>
        <p>We verify seller identities and encourage vehicle checks for your peace of mind</p>
      </div>
      <div class="trust-badge">
        <i class="fas fa-calculator"></i> <h5>Transparent Pricing & Finance</h5>
        <p>Clear pricing with no hidden fees and optional finance support</p>
      </div>
      <div class="trust-badge">
        <i class="fas fa-headset"></i>
        <h5>Dedicated Car Support</h5>
        <p>Our team is here to help you through every step of your car journey</p>
      </div>
    </section>

    <section id="banner" class="section-m1">
      <h4>Join Our Car Community</h4>
      <h2>Over <span>10,000</span> Happy Drivers Across South Africa</h2>
      <button class="normal">List Your Car Today</button>
    </section>

    <section id="product1" class="section-p1"> <h2>Recently Added Vehicles</h2>
      <p>The Latest Cars Added by Sellers in Your Area</p>
      <div class="pro-container products-container grid-view">
        <?php
      
        $productWhereClause = "WHERE p.status = 'active'";
        $productOrderBy = "ORDER BY p.DateListed DESC";
        $limit = 8; 

        try {
          
            $stmt = $pdo->query("
            SELECT
                p.ProductID AS id,
                p.ProductName AS title,
                p.Description AS description,
                p.Price AS price,
                p.`Condition` AS `condition`, -- Backticked 'Condition' here
                p.Location AS location,
                p.DateListed AS created_at,
                p.ViewsCount AS views,
                p.Featured AS featured,
                p.Make AS make,
                p.Model AS model,
                p.Year AS year,
                p.Mileage AS mileage,
                p.FuelType AS fuel_type,
                p.Transmission AS transmission,
                p.ImageURL AS ImageURL, -- Main image URL directly from Products table
                p.SellerID AS user_id, -- SellerID from Products maps to user_id for seller info
                cbt.name AS CategoryName, -- Get car body type name from car_body_types
                u.name AS Username,
                u.avatar AS ProfileImage,
                u.IsVerified AS UserIsVerified,
                u.rating AS UserRating, -- Get user rating for seller
                (SELECT COUNT(*) FROM Reviews WHERE user_id = u.id) AS TotalReviews, -- Corrected column name: user_id for reviews
                s.store_name AS SellerStoreName,
                s.status AS SellerStatus
            FROM Products p
            JOIN users u ON p.SellerID = u.id -- Join with users using SellerID from Products
            LEFT JOIN sellers s ON u.id = s.user_id
            LEFT JOIN car_body_types cbt ON p.CategoryID = cbt.id -- Join for category name, using p.CategoryID
            " . $productWhereClause . "
            " . $productOrderBy . "
            LIMIT " . $limit . "
            ");
            $recentCarsToDisplay = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($recentCarsToDisplay) {
                foreach ($recentCarsToDisplay as $car) {
                   
                    $product = [
                        'id' => $car['id'],
                        'title' => $car['title'], 
                        'description' => $car['description'] ?? '',
                        'price' => $car['price'] ?? 0,
                        'category' => $car['CategoryName'] ?? 'N/A', 
                        'condition' => $car['condition'] ?? 'N/A',
                        'location' => $car['location'] ?? 'South Africa',
                        'date_posted' => $car['created_at'] ?? 'now',
                        'views' => $car['views'] ?? 0, 
                        'featured' => (bool)($car['featured'] ?? false), 
                        'make' => $car['make'] ?? 'Unknown Make',
                        'model' => $car['model'] ?? 'Unknown Model',
                        'year' => $car['year'] ?? 'N/A',
                        'mileage' => $car['mileage'] ?? 'N/A',
                        'fuel_type' => $car['fuel_type'] ?? 'N/A',
                        'transmission' => $car['transmission'] ?? 'N/A',
                        'image' => $car['ImageURL'] ?? 'images/default_car.png',
                        'seller' => [
                            'id' => $car['user_id'], 
                            'name' => $car['SellerStoreName'] ?? $car['Username'],
                            'rating' => $car['UserRating'] ?? 0.0,
                            'verified' => (bool)($car['UserIsVerified'] ?? false),
                            'avatar' => $car['ProfileImage'] ?? 'images/default-avatar.png'
                        ],
                        'total_reviews' => $car['TotalReviews'] ?? 0 
                    ];
            ?>
                <div class="product-card">
                    <?php if($product['featured']): ?>
                        <div class="featured-tag">New Listing</div> <?php endif; ?>

                    <?php if (!empty($product['condition'])): ?>
                        <div class="condition-badge"><?php echo htmlspecialchars($product['condition']); ?></div>
                    <?php endif; ?>

                    <div class="product-image">
                        <a href="product-details.php?id=<?php echo $product['id']; ?>">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" />
                        </a>
                    </div>

                    <div class="product-details">
                        <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                        <h3 class="product-title">
                            <a href="product-details.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['title']); ?></a>
                        </h3>
                        <div class="product-price">R<?php echo number_format($product['price'], 2); ?></div>

                        <div class="product-description">
                            <?php echo htmlspecialchars(substr($product['description'], 0, 150)) . '...'; ?>
                        </div>

                        <div class="car-specs">
                            <span><i class="fas fa-car"></i> <?php echo htmlspecialchars($product['make'] . ' ' . $product['model']); ?></span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($product['year']); ?></span>
                            <span><i class="fas fa-tachometer-alt"></i> <?php echo htmlspecialchars(number_format($product['mileage'])) . ' km'; ?></span>
                            <span><i class="fas fa-gas-pump"></i> <?php echo htmlspecialchars($product['fuel_type']); ?></span>
                            <span><i class="fas fa-cogs"></i> <?php echo htmlspecialchars($product['transmission']); ?></span>
                        </div>

                        <div class="location-info">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($product['location']); ?></span>
                        </div>

                        <div class="seller-info">
                            <div class="seller-avatar">
                                <img src="<?php echo htmlspecialchars($product['seller']['avatar']); ?>" alt="Seller Avatar" />
                            </div>
                            <div class="seller-details">
                                <div class="seller-name"><?php echo htmlspecialchars($product['seller']['name']); ?></div>
                                <div class="seller-rating">
                                    <?php 
                                        $rating = $product['seller']['rating'];
                                        for($i = 1; $i <= 5; $i++): 
                                            if($i <= floor($rating)): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif($i - $rating < 1): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; 
                                        endfor; 
                                    ?>
                                    <span><?php echo number_format($rating, 1); ?></span>
                                </div>
                                <?php if($product['seller']['verified']): ?>
                                    <div class="verified-seller">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Verified</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="product-meta">
                            <div class="posted-date">
                                Posted <?php echo date('j M Y', strtotime($product['date_posted'])); ?>
                            </div>
                            <div class="views">
                                <i class="far fa-eye"></i>
                                <span><?php echo $product['views']; ?> views</span>
                            </div>
                        </div>
                    </div>

                    <div class="product-actions">
                        <a href="wishlist.php?add=<?php echo $product['id']; ?>" class="action-button wishlist-btn" title="Add to Wishlist">
                            <i class="far fa-heart"></i>
                        </a>
                        <a href="message.php?seller=<?php echo $product['seller']['id']; ?>&product=<?php echo $product['id']; ?>" class="action-button message-btn" title="Message Seller">
                            <i class="far fa-comment-alt"></i>
                        </a>
                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="action-button view-btn" title="View Details">
                            <i class="far fa-eye"></i>
                        </a>
                    </div>
                </div>
            <?php
                }
            } else {
                echo '<p class="no-results">No recently added cars found.</p>';
            }
        } catch (PDOException $e) {
            echo '<p class="error-message">Error fetching recently added cars: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>
      
      <div class="view-more">
        <a href="browse.php?sort=newest" class="btn-view-more">View All Recent Cars</a>
      </div>
    </section>

    <section id="sm-banner" class="section-p1">
      <div class="banner-box">
        <h4>Sell Your Car</h4>
        <h2>Turn Your Ride Into Cash</h2>
        <span>List your vehicle quickly and easily on UbuntuWheels</span>
        <button class="white">Start Selling Your Car</button>
      </div>
      <div class="banner-box banner-box2">
        <h4>Shop Confidently</h4>
        <h2>Secure Car Transactions</h2>
        <span>Our platform ensures safe and verified car trading</span>
        <button class="white">Learn More</button>
      </div>
    </section>

    <section id="banner3">
      <div class="banner-box">
        <h2>SUVs & Crossovers</h2>
        <h3>Explore Our Range of Utility Vehicles</h3>
      </div>
      <div class="banner-box banner-box2">
        <h2>Sedans & Hatchbacks</h2>
        <h3>Efficient and Stylish Urban Rides</h3>
      </div>
      <div class="banner-box banner-box3">
        <h2>Bakkies & Trucks</h2>
        <h3>Tough and Reliable Workhorses</h3>
      </div>
    </section>

    <section class="how-it-works section-p1">
      <h2>How UbuntuWheels Works</h2>
      <p>Simple, secure car trading between community members</p>
      
      <div class="steps-container">
        <div class="step">
          <div class="step-icon">
            <i class="fas fa-car-side"></i> </div>
          <h3>List Your Car</h3>
          <p>Upload photos and describe your vehicle's details and features.</p>
        </div>
        
        <div class="step-arrow">
          <i class="fas fa-chevron-right"></i>
        </div>
        
        <div class="step">
          <div class="step-icon">
            <i class="fas fa-comments"></i>
          </div>
          <h3>Connect with Buyers</h3>
          <p>Chat with interested buyers to arrange viewings and answer questions.</p>
        </div>
        
        <div class="step-arrow">
          <i class="fas fa-chevron-right"></i>
        </div>
        
        <div class="step">
          <div class="step-icon">
            <i class="fas fa-money-check-alt"></i> </div>
          <h3>Finalize Sale & Handover</h3>
          <p>Complete the transaction securely and safely hand over the vehicle.</p>
        </div>
        
        <div class="step-arrow">
          <i class="fas fa-chevron-right"></i>
        </div>
        
        <div class="step">
          <div class="step-icon">
            <i class="fas fa-star"></i>
          </div>
          <h3>Rate & Review</h3>
          <p>Share your experience to help build trust in the UbuntuWheels community.</p>
        </div>
      </div>
      
      <div class="cta-button">
        <button class="primary">Get Started Selling Your Car</button>
      </div>
    </section>

    <section id="newsletter" class="section-p1 section-m1">
      <div class="newstext">
        <h4>Sign Up For Our Car Newsletter</h4>
        <p>
          Get email updates about new car listings in your area and
          <span>special offers.</span>
        </p>
      </div>
      <div class="form">
        <input type="email" placeholder="Your email address" />
        <button class="normal">Sign Up</button>
      </div>
    </section>

    <footer class="section-p1">
      <div class="col">
        <img class="logo" src="img/ubuntuWheels_logo.png" alt="UbuntuWheels Logo" />
        <h4>Contact</h4>
        <p><strong>Address:</strong> 123 Autopark Drive, Johannesburg, South Africa</p> <p><strong>Phone:</strong> +27 11 987 6543</p> <p><strong>Hours:</strong> 09:00 - 17:00, Mon - Sat</p> <div class="follow">
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
        <a href="saved-cars.php">My Saved Cars</a> <a href="wishlist.php">My Wishlist</a>
        <a href="my-listings.php">My Car Listings</a>
        <a href="help.php">Help & Support</a>
      </div>

      <div class="col">
        <h4>Sell Your Car</h4>
        <a href="create-listing.php">List a Car</a>
        <a href="seller-guide.php">Seller's Guide</a>
        <a href="car-valuation.php">Car Valuation</a> <a href="seller-protection.php">Seller Protection</a>
        <a href="seller-faq.php">Seller FAQ</a>
      </div>

      <div class="col install">
        <h4>Install App</h4>
        <p>From App Store or Google Play</p>
        <div class="row">
          <img src="img/app_store_image.png" alt="App Store" />
          <img src="img/google_play_image.png" alt="Google Play" />
        </div>
        <p>Secure Payment Gateways</p>
        <img src="img/payment_gateway_image.png" alt="Payment Methods" />
      </div>

      <div class="copyright">
        <p>&copy; 2025 - UbuntuWheels. All Rights Reserved.</p>
      </div>
    </footer>

    <script src="script.js"></script>
    <script>
    
        document.addEventListener('DOMContentLoaded', function() {
            const mobileSearchIcon = document.querySelector('#mobile .search-icon');
            const mobileSearchContainer = document.querySelector('.mobile-search');

            if (mobileSearchIcon && mobileSearchContainer) {
                mobileSearchIcon.addEventListener('click', function() {
                    mobileSearchContainer.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>