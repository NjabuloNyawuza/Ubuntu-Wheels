<?php

ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require_once 'db_connection.php';

session_start();





$isLoggedIn = isset($_SESSION['user_id']);





$search = $_GET['search'] ?? '';

$category = $_GET['category'] ?? ''; 

$minPrice = $_GET['min_price'] ?? '';

$maxPrice = $_GET['max_price'] ?? '';

$condition = $_GET['condition'] ?? []; 

$location = $_GET['location'] ?? ''; 





$make = $_GET['make'] ?? '';

$model = $_GET['model'] ?? '';

$minYear = $_GET['min_year'] ?? '';

$maxYear = $_GET['max_year'] ?? '';

$minMileage = $_GET['min_mileage'] ?? '';

$maxMileage = $_GET['max_mileage'] ?? '';

$fuelType = $_GET['fuel_type'] ?? '';

$transmission = $_GET['transmission'] ?? '';





$distance = $_GET['distance'] ?? '50'; 

$sort = $_GET['sort'] ?? 'newest';

$sellerType = $_GET['seller_type'] ?? [];

$page = $_GET['page'] ?? 1;

$view = $_GET['view'] ?? 'grid';





$itemsPerPage = 12;

$offset = ($page - 1) * $itemsPerPage;





$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';





$categories = [];

try {



$catStmt = $pdo->query("SELECT id, name FROM car_body_types ORDER BY name");

$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

$categoryError = "Error loading categories: " . $e->getMessage();

}





$locations = [];

try {

$locStmt = $pdo->query("SELECT DISTINCT Location FROM Products WHERE Location IS NOT NULL AND Location != '' ORDER BY Location");

$locations = $locStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {

$locationError = "Error loading locations: " . $e->getMessage();

}



$makes = [];

$models = []; 

$fuelTypes = [];

$transmissions = [];



try {

$makeStmt = $pdo->query("SELECT DISTINCT Make FROM Products WHERE Make IS NOT NULL AND Make != '' ORDER BY Make");

$makes = $makeStmt->fetchAll(PDO::FETCH_COLUMN);



$makes = array_filter($makes);

sort($makes); 



$fuelTypeStmt = $pdo->query("SELECT DISTINCT FuelType FROM Products WHERE FuelType IS NOT NULL AND FuelType != '' ORDER BY FuelType");

$fuelTypes = $fuelTypeStmt->fetchAll(PDO::FETCH_COLUMN);

$fuelTypes = array_filter($fuelTypes);

sort($fuelTypes);



$transmissionStmt = $pdo->query("SELECT DISTINCT Transmission FROM Products WHERE Transmission IS NOT NULL AND Transmission != '' ORDER BY Transmission");

$transmissions = $transmissionStmt->fetchAll(PDO::FETCH_COLUMN);

$transmissions = array_filter($transmissions);

sort($transmissions);





if (!empty($make)) {

$modelStmt = $pdo->prepare("SELECT DISTINCT Model FROM Products WHERE Make = :make AND Model IS NOT NULL AND Model != '' ORDER BY Model");

$modelStmt->execute([':make' => $make]);

$models = $modelStmt->fetchAll(PDO::FETCH_COLUMN);

$models = array_filter($models);

sort($models);

}

} catch (PDOException $e) {


error_log("Error loading car specific filters: " . $e->getMessage());

}





$conditions = ['New', 'Like New', 'Good', 'Fair', 'Used'];




$products = [];

$totalProducts = 0;



try {


$whereClause = "WHERE 1=1";

$params = []; 




if (!empty($search)) {

$whereClause .= " AND (p.ProductName LIKE :search OR p.Description LIKE :search OR p.Make LIKE :search OR p.Model LIKE :search)";

$params[':search'] = '%' . $search . '%';

}





if (!empty($category)) {

$whereClause .= " AND p.CategoryID = :category_id";

$params[':category_id'] = $category;

}





if (is_numeric($minPrice) && $minPrice !== '') {

$whereClause .= " AND p.Price >= :min_price";

$params[':min_price'] = $minPrice;

}

if (is_numeric($maxPrice) && $maxPrice !== '') {

$whereClause .= " AND p.Price <= :max_price";

$params[':max_price'] = $maxPrice;

}





if (!empty($condition) && is_array($condition)) {

$inPlaceholders = [];

foreach ($condition as $i => $condVal) {

$placeholder = ":condition_" . $i;

$inPlaceholders[] = $placeholder;

$params[$placeholder] = $condVal;

}

$whereClause .= " AND p.Condition IN (" . implode(',', $inPlaceholders) . ")";

}





if (!empty($location)) {

$whereClause .= " AND p.Location = :location_val";

$params[':location_val'] = $location;

}





if (!empty($sellerType) && in_array('verified', $sellerType)) {

$whereClause .= " AND u.IsVerified = 1"; 

}




if (!empty($make)) {

$whereClause .= " AND p.Make = :make_val";

$params[':make_val'] = $make;

}

if (!empty($model)) {

$whereClause .= " AND p.Model = :model_val";

$params[':model_val'] = $model;

}

if (is_numeric($minYear) && $minYear !== '') {

$whereClause .= " AND p.Year >= :min_year";

$params[':min_year'] = $minYear;

}

if (is_numeric($maxYear) && $maxYear !== '') {

$whereClause .= " AND p.Year <= :max_year";

$params[':max_year'] = $maxYear;

}

if (is_numeric($minMileage) && $minMileage !== '') {

$whereClause .= " AND p.Mileage >= :min_mileage";

$params[':min_mileage'] = $minMileage;

}

if (is_numeric($maxMileage) && $maxMileage !== '') {

$whereClause .= " AND p.Mileage <= :max_mileage";

$params[':max_mileage'] = $maxMileage;

}

if (!empty($fuelType)) {

$whereClause .= " AND p.FuelType = :fuel_type_val";

$params[':fuel_type_val'] = $fuelType;

}

if (!empty($transmission)) {

$whereClause .= " AND p.Transmission = :transmission_val";

$params[':transmission_val'] = $transmission;

}







$countSql = "SELECT COUNT(*) FROM Products p

JOIN users u ON p.SellerID = u.id

LEFT JOIN sellers s ON u.id = s.user_id

JOIN car_body_types cbt ON p.CategoryID = cbt.id " . $whereClause;



$countStmt = $pdo->prepare($countSql);

foreach ($params as $paramName => $paramValue) {

$countStmt->bindValue($paramName, $paramValue);

}

$countStmt->execute();

$totalProducts = $countStmt->fetchColumn();





$sql = "SELECT p.*,

cbt.name AS CategoryName,

u.name AS SellerName,

u.rating AS SellerRating,

u.IsVerified AS SellerIsVerified,

u.location AS SellerLocation,

u.avatar AS SellerAvatar

FROM Products p

JOIN users u ON p.SellerID = u.id

LEFT JOIN sellers s ON u.id = s.user_id

JOIN car_body_types cbt ON p.CategoryID = cbt.id " . $whereClause;




switch ($sort) {

case 'price_low':

$sql .= " ORDER BY p.Price ASC";

break;

case 'price_high':

$sql .= " ORDER BY p.Price DESC";

break;

case 'newest':

$sql .= " ORDER BY p.DateListed DESC";

break;

case 'oldest':

$sql .= " ORDER BY p.DateListed ASC";

break;

case 'popular':

$sql .= " ORDER BY p.ViewsCount DESC, p.DateListed DESC";

break;

default:

$sql .= " ORDER BY p.DateListed DESC"; 

break;

}




$sql .= " LIMIT :itemsPerPage OFFSET :offset";

$params[':itemsPerPage'] = $itemsPerPage;

$params[':offset'] = $offset;





$stmt = $pdo->prepare($sql);





foreach ($params as $paramName => $paramValue) {

$pdoType = PDO::PARAM_STR;

if (is_int($paramValue)) {

$pdoType = PDO::PARAM_INT;

} elseif (is_bool($paramValue)) {

$pdoType = PDO::PARAM_BOOL;

} elseif (is_float($paramValue)) {

$pdoType = PDO::PARAM_STR;

}

if ($paramName === ':itemsPerPage' || $paramName === ':offset') {

$stmt->bindValue($paramName, (int)$paramValue, PDO::PARAM_INT);

} else {

$stmt->bindValue($paramName, $paramValue, $pdoType);

}

}



$stmt->execute();

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);



} catch (PDOException $e) {

die("Error fetching products: " . $e->getMessage());

}



$totalPages = ceil($totalProducts / $itemsPerPage);





foreach ($products as &$product) {

$product['id'] = $product['ProductID'];

$product['title'] = $product['ProductName'];

$product['description'] = $product['Description'];

$product['price'] = $product['Price'];

$product['category'] = $product['CategoryName'];

$product['category_id'] = $product['CategoryID'];

$product['condition'] = $product['Condition'];

$product['location'] = $product['Location'] ?? ($product['SellerLocation'] ?? 'N/A'); 

$product['date_posted'] = $product['DateListed'];

$product['views'] = $product['ViewsCount']; 

$product['featured'] = (bool)$product['Featured'];




$product['make'] = $product['Make'] ?? 'Unknown Make';

$product['model'] = $product['Model'] ?? 'Unknown Model';

$product['year'] = $product['Year'] ?? 'N/A';

$product['mileage'] = $product['Mileage'] ?? 'N/A';

$product['fuel_type'] = $product['FuelType'] ?? 'N/A';

$product['transmission'] = $product['Transmission'] ?? 'N/A';




$product['seller'] = [

'id' => $product['SellerID'], 

'name' => $product['SellerName'] ?? 'N/A', 

'rating' => $product['SellerRating'] ?? 0.0, 

'verified' => (bool)($product['SellerIsVerified'] ?? false), 

'avatar' => $product['SellerAvatar'] ?? 'https://via.placeholder.com/50x50?text=Avatar'

];



if (!empty($product['ImageURL'])) {

$product['image'] = $product['ImageURL'];

} elseif (!empty($product['ImageURL2'])) {

$product['image'] = $product['ImageURL2'];

} else {

$product['image'] = 'https://via.placeholder.com/300?text=No+Image'; 

}

}





function render_product_listings($products, $totalProducts, $totalPages, $page, $view, $search, $isAjax) {

ob_start(); 

?>

<div class="products-content">

<div class="browse-options">

<div class="results-count">

<span><?php echo $totalProducts; ?> results</span>

</div>



<div class="sort-options">

<label for="sort-select">Sort by:</label>

<select id="sort-select" name="sort">

<option value="newest" <?php echo ($_GET['sort'] ?? 'newest') == 'newest' ? 'selected' : ''; ?>>Newest First</option>

<option value="price_low" <?php echo ($_GET['sort'] ?? '') == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>

<option value="price_high" <?php echo ($_GET['sort'] ?? '') == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>

<option value="popular" <?php echo ($_GET['sort'] ?? '') == 'popular' ? 'selected' : ''; ?>>Most Popular</option>

<option value="oldest" <?php echo ($_GET['sort'] ?? '') == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>

</select>

</div>



<div class="view-options">

<a href="#" data-view="grid" class="view-option <?php echo ($view == 'grid') ? 'active' : ''; ?>">

<i class="fas fa-th"></i>

</a>

<a href="#" data-view="list" class="view-option <?php echo ($view == 'list') ? 'active' : ''; ?>">

<i class="fas fa-list"></i>

</a>

</div>

</div>



<div class="mobile-filter-toggle">

<button id="show-filters">

<i class="fas fa-filter"></i> Filters

</button>

</div>



<?php if (empty($products)): ?>

<div class="no-results">

<div class="no-results-icon">

<i class="far fa-search"></i>

</div>

<h2>No Results Found</h2>

<p>We couldn't find any products matching your search criteria.</p>

<div class="no-results-actions">

<a href="browse.php" class="btn-clear-search">Clear Filters</a>

</div>

</div>

<?php else: ?>

<div class="products-container <?php echo ($view == 'list') ? 'list-view' : 'grid-view'; ?>">

<?php foreach($products as $product): ?>

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

<a href="product.php?id=<?php echo $product['id']; ?>" class="action-button view-btn" title="View Details">

<i class="far fa-eye"></i>

</a>

</div>

</div>

<?php endforeach; ?>

</div>



<?php if($totalPages > 1): ?>

<div class="pagination">

<?php if($page > 1): ?>

<a href="#" data-page="<?php echo $page - 1; ?>" class="pagination-arrow">

<i class="fas fa-chevron-left"></i>

</a>

<?php endif; ?>



<?php

$startPage = max(1, $page - 2);

$endPage = min($totalPages, $startPage + 4);

if ($endPage - $startPage < 4) {

$startPage = max(1, $endPage - 4);

}

?>



<?php if($startPage > 1): ?>

<a href="#" data-page="1" class="pagination-link">1</a>

<?php if($startPage > 2): ?>

<span class="pagination-ellipsis">...</span>

<?php endif; ?>

<?php endif; ?>



<?php for($i = $startPage; $i <= $endPage; $i++): ?>

<a href="#" data-page="<?php echo $i; ?>"

class="pagination-link <?php echo ($i == $page) ? 'active' : ''; ?>">

<?php echo $i; ?>

</a>

<?php endfor; ?>



<?php if($endPage < $totalPages): ?>

<?php if($endPage < $totalPages - 1): ?>

<span class="pagination-ellipsis">...</span>

<?php endif; ?>

<a href="#" data-page="<?php echo $totalPages; ?>" class="pagination-link">

<?php echo $totalPages; ?>

</a>

<?php endif; ?>



<?php if($page < $totalPages): ?>

<a href="#" data-page="<?php echo $page + 1; ?>" class="pagination-arrow">

<i class="fas fa-chevron-right"></i>

</a>

<?php endif; ?>

</div>

<?php endif; ?>

<?php endif; ?>

</div>

<?php

return ob_get_clean(); 

}





if ($isAjax) {

header('Content-Type: application/json');

echo json_encode([

'productsHtml' => render_product_listings($products, $totalProducts, $totalPages, $page, $view, $search, true),

'totalProducts' => $totalProducts,

'totalPages' => $totalPages,



'models' => $models ?? []

]);

exit; 

}




?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta http-equiv="X-UA-Compatible" content="IE=edge">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Browse Products - UbuntuTrade</title>

<link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">

<link rel="stylesheet" href="style.css">

<link rel="stylesheet" href="browse.css">

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

<li><a href="browse.php" class="active">Browse</a></li>

<li><a href="categories.php">Categories</a></li>

<?php if($isLoggedIn): ?>

<li><a href="login.php">My Account</a></li>

<li><a href="sell.php">Sell Item</a></li>

<li><a href="messages.php"><i class="far fa-envelope"></i></a></li>

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

<input type="text" name="search" placeholder="Search for anything..." value="<?php echo htmlspecialchars($search); ?>">

<i class="far fa-search"></i>

</form>

</div>

</div>



<section id="browse-header">

<div class="container">

<h1><?php echo !empty($search) ? 'Search Results for "' . htmlspecialchars($search) . '"' : 'Browse All Products'; ?></h1>

<p>Find great deals from trusted sellers in your community</p>

</div>

</section>



<section id="browse-content" class="section-p1">

<div class="container">

<div class="browse-layout">

<div class="filters-sidebar">

<div class="filters-header">

<h3>Filters</h3>

<a href="browse.php" class="clear-filters">Clear All</a>

</div>



<form id="filter-form" action="browse.php" method="GET">

<input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">

<input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">

<input type="hidden" name="page" value="<?php echo htmlspecialchars($page); ?>">

<input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">



<div class="filter-group">

<h4>Categories (Car Body Type)</h4>

<div class="filter-options">

<select name="category" id="category-select">

<option value="">All Body Types</option>

<?php foreach($categories as $cat): ?>

<option value="<?php echo $cat['id']; ?>" <?php echo ($category == $cat['id']) ? 'selected' : ''; ?>>

<?php echo htmlspecialchars($cat['name']); ?>

</option>

<?php endforeach; ?>

</select>

</div>

</div>



<div class="filter-group">

<h4>Make</h4>

<div class="filter-options">

<select name="make" id="make-select">

<option value="">All Makes</option>

<?php foreach($makes as $mk): ?>

<option value="<?php echo htmlspecialchars($mk); ?>" <?php echo ($make == $mk) ? 'selected' : ''; ?>>

<?php echo htmlspecialchars($mk); ?>

</option>

<?php endforeach; ?>

</select>

</div>

</div>



<div class="filter-group">

<h4>Model</h4>

<div class="filter-options">

<select name="model" id="model-select" <?php echo empty($make) ? 'disabled' : ''; ?>>

<option value="">All Models</option>

<?php foreach($models as $mdl): ?>

<option value="<?php echo htmlspecialchars($mdl); ?>" <?php echo ($model == $mdl) ? 'selected' : ''; ?>>

<?php echo htmlspecialchars($mdl); ?>

</option>

<?php endforeach; ?>

</select>

</div>

</div>



<div class="filter-group">

<h4>Year Range</h4>

<div class="price-inputs"> <div class="price-input">

<input type="number" name="min_year" id="min-year-input" placeholder="Min Year" min="1900" max="2099">

</div>

<span class="price-separator">to</span>

<div class="price-input">

<input type="number" name="max_year" id="max-year-input" placeholder="Max Year" min="1900" max="2099">

</div>

</div>

<button type="button" class="btn-apply-year">Apply</button>

</div>



<div class="filter-group">

<h4>Mileage (km)</h4>

<div class="price-inputs">

<div class="price-input">

<input type="number" name="min_mileage" placeholder="Min km" value="<?php echo htmlspecialchars($minMileage); ?>" min="0">

</div>

<span class="price-separator">to</span>

<div class="price-input">

<input type="number" name="max_mileage" placeholder="Max km" value="<?php echo htmlspecialchars($maxMileage); ?>" min="0">

</div>

</div>

<button type="button" class="btn-apply-mileage">Apply</button>

</div>



<div class="filter-group">

<h4>Fuel Type</h4>

<div class="filter-options">

<select name="fuel_type" id="fuel-type-select">

<option value="">All Fuel Types</option>

<?php foreach($fuelTypes as $ft): ?>

<option value="<?php echo htmlspecialchars($ft); ?>" <?php echo ($fuelType == $ft) ? 'selected' : ''; ?>>

<?php echo htmlspecialchars($ft); ?>

</option>

<?php endforeach; ?>

</select>

</div>

</div>



<div class="filter-group">

<h4>Transmission</h4>

<div class="filter-options">

<select name="transmission" id="transmission-select">

<option value="">All Transmissions</option>

<?php foreach($transmissions as $tr): ?>

<option value="<?php echo htmlspecialchars($tr); ?>" <?php echo ($transmission == $tr) ? 'selected' : ''; ?>>

<?php echo htmlspecialchars($tr); ?>

</option>

<?php endforeach; ?>

</select>

</div>

</div>



<div class="filter-group">

<h4>Price Range</h4>

<div class="price-inputs">

<div class="price-input">

<span class="currency">R</span>

<input type="number" name="min_price" placeholder="Min" value="<?php echo htmlspecialchars($minPrice); ?>">

</div>

<span class="price-separator">to</span>

<div class="price-input">

<span class="currency">R</span>

<input type="number" name="max_price" placeholder="Max" value="<?php echo htmlspecialchars($maxPrice); ?>">

</div>

</div>

<button type="button" class="btn-apply-price">Apply</button>

</div>



<div class="filter-group">

<h4>Condition</h4>

<div class="filter-options">

<?php foreach($conditions as $cond): ?>

<div class="checkbox-group">

<input type="checkbox" id="condition-<?php echo strtolower(str_replace(' ', '-', $cond)); ?>"

name="condition[]" value="<?php echo $cond; ?>"

<?php echo (is_array($condition) && in_array($cond, $condition)) ? 'checked' : ''; ?>>

<label for="condition-<?php echo strtolower(str_replace(' ', '-', $cond)); ?>"><?php echo $cond; ?></label>

</div>

<?php endforeach; ?>

</div>

</div>



<div class="filter-group">

<h4>Product Location</h4>

<div class="filter-options">

<select name="location" id="location-select">

<option value="">All Locations</option>

<?php foreach($locations as $loc): ?>

<option value="<?php echo htmlspecialchars($loc); ?>" <?php echo ($location == $loc) ? 'selected' : ''; ?>>

<?php echo htmlspecialchars($loc); ?>

</option>

<?php endforeach; ?>

</select>

</div>

</div>



<div class="filter-group">

<h4>Distance</h4>

<div class="filter-options">

<select name="distance" id="distance-select" disabled title="Distance filter requires geolocation">

<option value="0">Any Distance (Not Active)</option>

</select>

<p style="font-size: 0.8em; color: #888;">(Requires geolocation data)</p>

</div>

</div>



<div class="filter-group">

<h4>Seller Type</h4>

<div class="filter-options">

<div class="checkbox-group">

<input type="checkbox" id="seller-verified" name="seller_type[]" value="verified"

<?php echo (is_array($sellerType) && in_array('verified', $sellerType)) ? 'checked' : ''; ?>>

<label for="seller-verified">Verified Sellers</label>

</div>

</div>

</div>



<button type="submit" class="btn-apply-filters">Apply Filters</button>

</form>

</div>



<div id="product-listing-section">

<?php echo render_product_listings($products, $totalProducts, $totalPages, $page, $view, $search, false); ?>

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

<p>&copy; 2025 - UbuntuTrade. All Rights Reserved.</p>

</div>

</footer>



<script src="script.js"></script>

<script src="browse.js"></script>

</body>

</html>