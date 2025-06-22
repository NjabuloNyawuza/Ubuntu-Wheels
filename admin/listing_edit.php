<?php
session_start();
require_once '../db_connection.php';
require_once 'check_admin.php';

$currentPage = 'listings';

$productId = $_GET['id'] ?? null;
$productData = null;
$errors = [];
$categories = []; 


try {
    $categoriesStmt = $pdo->query("SELECT CategoryID, CategoryName FROM Categories ORDER BY CategoryName");
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories for listing_edit.php: " . $e->getMessage());
    $errors[] = "Failed to load categories.";
}


if ($productId) {
    try {
        
        $stmt = $pdo->prepare("
            SELECT
                P.*, U.name AS SellerName
            FROM
                Products P
            LEFT JOIN users U ON P.SellerID = U.id
            WHERE
                P.ProductID = :product_id
        ");
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        $productData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$productData) {
            $_SESSION['error_message'] = "Listing not found.";
            header('Location: listings.php');
            exit();
        }

    } catch (PDOException $e) {
        error_log("Error fetching product data for edit: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to load listing data for editing.";
        header('Location: listings.php');
        exit();
    }
} else {
    $_SESSION['error_message'] = "No listing ID provided for editing.";
    header('Location: listings.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = trim($_POST['ProductName'] ?? '');
    $description = trim($_POST['Description'] ?? '');
    $price = filter_var($_POST['Price'] ?? '', FILTER_VALIDATE_FLOAT);
    $categoryId = filter_var($_POST['CategoryID'] ?? '', FILTER_VALIDATE_INT);
    $condition = trim($_POST['Condition'] ?? '');
    $location = trim($_POST['Location'] ?? '');
    $make = trim($_POST['Make'] ?? '');
    $model = trim($_POST['Model'] ?? '');
    $year = filter_var($_POST['Year'] ?? '', FILTER_VALIDATE_INT);
    $mileage = filter_var($_POST['Mileage'] ?? '', FILTER_VALIDATE_INT);
    $fuelType = trim($_POST['FuelType'] ?? '');
    $transmission = trim($_POST['Transmission'] ?? '');
    $status = $_POST['status'] ?? '';
    $featured = isset($_POST['Featured']) ? 1 : 0;
    $isBestSeller = isset($_POST['IsBestSeller']) ? 1 : 0;

    // Basic validation
    if (empty($productName)) $errors[] = "Product Name is required.";
    if ($price === false || $price <= 0) $errors[] = "Invalid price.";
    if ($categoryId === false || !in_array($categoryId, array_column($categories, 'CategoryID'))) $errors[] = "Invalid category.";
    if (empty($condition)) $errors[] = "Condition is required.";
    if (!in_array($status, ['active', 'pending', 'sold', 'draft', 'archived'])) $errors[] = "Invalid status selected.";
    if ($year === false || $year < 1900 || $year > date('Y') + 1) $errors[] = "Invalid year.";
    if ($mileage === false || $mileage < 0) $errors[] = "Invalid mileage.";


    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE Products
                SET
                    ProductName = :product_name,
                    Description = :description,
                    Price = :price,
                    CategoryID = :category_id,
                    Condition = :condition,
                    Location = :location,
                    Make = :make,
                    Model = :model,
                    Year = :year,
                    Mileage = :mileage,
                    FuelType = :fuel_type,
                    Transmission = :transmission,
                    status = :status,
                    Featured = :featured,
                    IsBestSeller = :is_best_seller,
                    DateListed = NOW() -- Update last modified time
                WHERE
                    ProductID = :product_id
            ");

            $stmt->bindParam(':product_name', $productName);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->bindParam(':condition', $condition);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':make', $make);
            $stmt->bindParam(':model', $model);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->bindParam(':mileage', $mileage, PDO::PARAM_INT);
            $stmt->bindParam(':fuel_type', $fuelType);
            $stmt->bindParam(':transmission', $transmission);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':featured', $featured, PDO::PARAM_INT);
            $stmt->bindParam(':is_best_seller', $isBestSeller, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);

            $stmt->execute();

            $_SESSION['success_message'] = "Listing updated successfully!";
            header("Location: listing_details.php?id=" . $productId); 
            exit();

        } catch (PDOException $e) {
            error_log("Error updating product details: " . $e->getMessage());
            $errors[] = "Failed to update listing details. Database error.";
        }
    } else {
   
        $productData['ProductName'] = $productName;
        $productData['Description'] = $description;
        $productData['Price'] = $price;
        $productData['CategoryID'] = $categoryId;
        $productData['Condition'] = $condition;
        $productData['Location'] = $location;
        $productData['Make'] = $make;
        $productData['Model'] = $model;
        $productData['Year'] = $year;
        $productData['Mileage'] = $mileage;
        $productData['FuelType'] = $fuelType;
        $productData['Transmission'] = $transmission;
        $productData['status'] = $status;
        $productData['Featured'] = $featured;
        $productData['IsBestSeller'] = $isBestSeller;
    }
}


$adminName = "Admin User";
$adminAvatar = "../images/avatars/default.jpg";

if (isset($_SESSION['user_id'])) {
    $current_admin_id = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT name, avatar FROM users WHERE id = :admin_id AND is_admin = 1");
        $stmt->bindParam(':admin_id', $current_admin_id, PDO::PARAM_INT);
        $stmt->execute();
        $adminInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($adminInfo) {
            $adminName = $adminInfo['name'] ?? "Admin User";
            $adminAvatar = $adminInfo['avatar'] ?? "../images/avatars/default.jpg";
        }
    } catch (PDOException $e) {
        error_log("Database error fetching admin name/avatar for listing_edit.php: " . $e->getMessage());
    }
}


$unreadAdminMessages = 0;
$unreadNotifications = 0;

if (isset($_SESSION['user_id'])) {
    $admin_user_id = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT COUNT(message_id) FROM messages WHERE receiver_id = :admin_id AND is_read = 0");
        $stmt->bindParam(':admin_id', $admin_user_id, PDO::PARAM_INT);
        $stmt->execute();
        $unreadAdminMessages = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(NotificationID) FROM Notifications WHERE UserID = :admin_id AND IsRead = 0");
        $stmt->bindParam(':admin_id', $admin_user_id, PDO::PARAM_INT);
        $stmt->execute();
        $unreadNotifications = $stmt->fetchColumn();

    } catch (PDOException $e) {
        error_log("Error fetching unread counts in listing_edit.php: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Listing: <?php echo htmlspecialchars($productData['ProductName']); ?> - UbuntuTrade Admin</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <a href="../index.php" target="_blank">
                    <img src="../images/logo.png" alt="UbuntuTrade Logo" class="sidebar-logo">
                </a>
                <button id="sidebar-toggle" class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <div class="sidebar-content">
                <nav class="sidebar-nav">
                    <ul>
                        <li>
                            <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="users.php" class="<?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                                <i class="fas fa-users"></i>
                                <span>Users</span>
                            </a>
                        </li>
                        <li>
                            <a href="listings.php" class="<?php echo $currentPage === 'listings' ? 'active' : ''; ?>">
                                <i class="fas fa-tags"></i>
                                <span>Listings</span>
                            </a>
                        </li>
                        <li>
                            <a href="transactions.php" class="<?php echo $currentPage === 'transactions' ? 'active' : ''; ?>">
                                <i class="fas fa-exchange-alt"></i>
                                <span>Transactions</span>
                            </a>
                        </li>
                        <li>
                            <a href="categories.php" class="<?php echo $currentPage === 'categories' ? 'active' : ''; ?>">
                                <i class="fas fa-th-large"></i>
                                <span>Categories</span>
                            </a>
                        </li>
                        <li>
                            <a href="reports.php" class="<?php echo $currentPage === 'reports' ? 'active' : ''; ?>">
                                <i class="fas fa-chart-bar"></i>
                                <span>Reports</span>
                            </a>
                        </li>
                        <li>
                            <a href="messages.php" class="<?php echo $currentPage === 'messages' ? 'active' : ''; ?>">
                                <i class="fas fa-envelope"></i>
                                <span>Messages</span>
                                <?php if ($unreadAdminMessages > 0): ?>
                                    <span class="badge"><?php echo htmlspecialchars($unreadAdminMessages); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li>
                            <a href="settings.php" class="<?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="sidebar-footer">
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <div class="header-search table-search">
                    <input type="text" placeholder="Search..." id="global-search-input">
                    <button type="button" id="global-search-button"><i class="fas fa-search"></i></button>
                </div>

                <div class="header-actions">
                    <div class="header-notifications">
                        <button class="notification-btn">
                            <i class="far fa-bell"></i>
                            <span class="badge"><?php echo htmlspecialchars($unreadNotifications); ?></span>
                        </button>
                        <div class="notification-dropdown">
                            <div class="notification-header">
                                <h3>Notifications</h3>
                                <a href="notifications.php">View All</a>
                            </div>
                            <div class="notification-list">
                                <p class="no-items">No new notifications.</p>
                            </div>
                        </div>
                    </div>

                    <div class="header-messages">
                        <button class="message-btn">
                            <i class="far fa-envelope"></i>
                            <?php if ($unreadAdminMessages > 0): ?>
                                <span class="badge"><?php echo htmlspecialchars($unreadAdminMessages); ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="message-dropdown">
                            <div class="message-header">
                                <h3>Messages</h3>
                                <a href="messages.php">View All</a>
                            </div>
                            <div class="message-list">
                                <p class="no-items">No new messages.</p>
                            </div>
                        </div>
                    </div>

                    <div class="header-profile">
                        <img src="<?php echo htmlspecialchars($adminAvatar); ?>" alt="Admin Avatar" class="profile-avatar">
                        <span class="profile-name"><?php echo htmlspecialchars($adminName); ?></span>
                        <i class="fas fa-chevron-down profile-arrow"></i>
                        <div class="profile-dropdown">
                            <a href="settings.php">Profile Settings</a>
                            <a href="../logout.php">Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <section class="admin-content">
                <div class="content-header">
                    <h2>Edit Listing: <?php echo htmlspecialchars($productData['ProductName']); ?></h2>
                    <div class="action-buttons">
                        <a href="listing_details.php?id=<?php echo htmlspecialchars($productId); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Details</a>
                        <a href="listings.php" class="btn btn-secondary"><i class="fas fa-tags"></i> Back to Listings</a>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert error-alert">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="listing_edit.php?id=<?php echo htmlspecialchars($productId); ?>" method="POST" class="admin-form">
                    <h3>Product Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="ProductName">Product Name</label>
                            <input type="text" id="ProductName" name="ProductName" value="<?php echo htmlspecialchars($productData['ProductName']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="Price">Price (R)</label>
                            <input type="number" id="Price" name="Price" step="0.01" value="<?php echo htmlspecialchars($productData['Price']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="CategoryID">Category</label>
                            <select id="CategoryID" name="CategoryID" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['CategoryID']); ?>" <?php echo (string)$productData['CategoryID'] === (string)$cat['CategoryID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['CategoryName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="Condition">Condition</label>
                            <select id="Condition" name="Condition">
                                <option value="New" <?php echo $productData['Condition'] === 'New' ? 'selected' : ''; ?>>New</option>
                                <option value="Like New" <?php echo $productData['Condition'] === 'Like New' ? 'selected' : ''; ?>>Like New</option>
                                <option value="Excellent" <?php echo $productData['Condition'] === 'Excellent' ? 'selected' : ''; ?>>Excellent</option>
                                <option value="Good" <?php echo $productData['Condition'] === 'Good' ? 'selected' : ''; ?>>Good</option>
                                <option value="Used" <?php echo $productData['Condition'] === 'Used' ? 'selected' : ''; ?>>Used</option>
                                <option value="Fair" <?php echo $productData['Condition'] === 'Fair' ? 'selected' : ''; ?>>Fair</option>
                                <option value="Poor" <?php echo $productData['Condition'] === 'Poor' ? 'selected' : ''; ?>>Poor</option>
                                <option value="For Parts" <?php echo $productData['Condition'] === 'For Parts' ? 'selected' : ''; ?>>For Parts</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="Location">Location</label>
                            <input type="text" id="Location" name="Location" value="<?php echo htmlspecialchars($productData['Location'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo $productData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="pending" <?php echo $productData['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="sold" <?php echo $productData['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                <option value="draft" <?php echo $productData['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="archived" <?php echo $productData['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="Featured" name="Featured" value="1" <?php echo $productData['Featured'] ? 'checked' : ''; ?>>
                            <label for="Featured">Featured Listing</label>
                        </div>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="IsBestSeller" name="IsBestSeller" value="1" <?php echo $productData['IsBestSeller'] ? 'checked' : ''; ?>>
                            <label for="IsBestSeller">Best Seller</label>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="Description">Description</label>
                        <textarea id="Description" name="Description" rows="6"><?php echo htmlspecialchars($productData['Description'] ?? ''); ?></textarea>
                    </div>

                    <h3>Vehicle Specifications</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="Make">Make</label>
                            <input type="text" id="Make" name="Make" value="<?php echo htmlspecialchars($productData['Make'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="Model">Model</label>
                            <input type="text" id="Model" name="Model" value="<?php echo htmlspecialchars($productData['Model'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="Year">Year</label>
                            <input type="number" id="Year" name="Year" value="<?php echo htmlspecialchars($productData['Year'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="Mileage">Mileage (km)</label>
                            <input type="number" id="Mileage" name="Mileage" value="<?php echo htmlspecialchars($productData['Mileage'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="FuelType">Fuel Type</label>
                            <input type="text" id="FuelType" name="FuelType" value="<?php echo htmlspecialchars($productData['FuelType'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="Transmission">Transmission</label>
                            <input type="text" id="Transmission" name="Transmission" value="<?php echo htmlspecialchars($productData['Transmission'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <h4>Current Images (Paths Only - Image Uploads require separate logic)</h4>
                        <p class="form-hint">Direct image upload functionality is complex and not included in this first pass. You can only view/edit the paths here.</p>
                        <div class="image-path-inputs form-grid">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="form-group">
                                    <label for="ImageURL<?php echo $i; ?>">Image URL <?php echo $i; ?></label>
                                    <input type="text" id="ImageURL<?php echo $i; ?>" name="ImageURL<?php echo $i; ?>" value="<?php echo htmlspecialchars($productData['ImageURL' . ($i > 1 ? $i : '')] ?? ''); ?>">
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>


                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                        <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset Form</button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script src="../admin.js"></script>
    <script>
       
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const adminContainer = document.querySelector('.admin-container');
            if (sidebarToggle && adminContainer) {
                sidebarContainer.classList.toggle('collapsed'); 
                sidebarToggle.addEventListener('click', () => {
                    adminContainer.classList.toggle('sidebar-collapsed');
                });
            }

            const profileHeader = document.querySelector('.header-profile');
            if (profileHeader) {
                profileHeader.addEventListener('click', (e) => {
                    const isProfileClick = e.target.closest('.header-profile') && !e.target.closest('.profile-dropdown');
                    if (isProfileClick) {
                        const profileDropdown = profileHeader.querySelector('.profile-dropdown');
                        profileDropdown.classList.toggle('active');
                        profileHeader.classList.toggle('active');
                    }
                });
                document.addEventListener('click', (e) => {
                    if (!profileHeader.contains(e.target)) {
                        profileHeader.querySelector('.profile-dropdown').classList.remove('active');
                        profileHeader.classList.remove('active');
                    }
                });
            }

            const notificationBtn = document.querySelector('.notification-btn');
            const notificationDropdown = document.querySelector('.notification-dropdown');
            if (notificationBtn && notificationDropdown) {
                notificationBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationDropdown.classList.toggle('active');
                });
                document.addEventListener('click', function(e) {
                    if (!notificationBtn.contains(e.target) && !notificationDropdown.contains(e.target)) {
                        notificationDropdown.classList.remove('active');
                    }
                });
            }

            const messageBtn = document.querySelector('.message-btn');
            const messageDropdown = document.querySelector('.message-dropdown');
            if (messageBtn && messageDropdown) {
                messageBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    messageDropdown.classList.toggle('active');
                });
                document.addEventListener('click', function(e) {
                    if (!messageBtn.contains(e.target) && !messageDropdown.contains(e.target)) {
                        messageDropdown.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>