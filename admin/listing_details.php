<?php
session_start();
require_once '../db_connection.php';
require_once 'check_admin.php';

$currentPage = 'listings';

$productId = $_GET['id'] ?? null;
$productData = null;

if ($productId) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                P.*,
                U.name AS SellerName, U.email AS SellerEmail, U.phone_number AS SellerPhone,
                C.CategoryName
            FROM
                Products P
            LEFT JOIN
                users U ON P.SellerID = U.id
            LEFT JOIN
                Categories C ON P.CategoryID = C.CategoryID
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
        error_log("Error fetching product details: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to load listing details. Please try again.";
        header('Location: listings.php');
        exit();
    }
} else {
    $_SESSION['error_message'] = "No listing ID provided.";
    header('Location: listings.php');
    exit();
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
        error_log("Database error fetching admin name/avatar for listing_details.php: " . $e->getMessage());
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
        error_log("Error fetching unread counts in listing_details.php: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Details: <?php echo htmlspecialchars($productData['ProductName']); ?> - UbuntuTrade Admin</title>
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
                    <h2>Listing Details: <?php echo htmlspecialchars($productData['ProductName']); ?></h2>
                    <div class="action-buttons">
                        <a href="listing_edit.php?id=<?php echo htmlspecialchars($productData['ProductID']); ?>" class="btn btn-edit" title="Edit Listing"><i class="fas fa-edit"></i> Edit</a>
                        <button class="btn btn-delete" data-product-id="<?php echo htmlspecialchars($productData['ProductID']); ?>" title="Delete Listing"><i class="fas fa-trash-alt"></i> Delete</button>
                        <a href="listings.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Listings</a>
                    </div>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert success-alert">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert error-alert">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <div class="listing-details-grid">
                    <div class="detail-card main-info-card">
                        <h3><?php echo htmlspecialchars($productData['ProductName']); ?></h3>
                        <p class="listing-price">R <?php echo number_format($productData['Price'], 2); ?></p>
                        <div class="listing-status">
                            <strong>Status:</strong> <span class="status-badge status-<?php echo htmlspecialchars($productData['status']); ?>"><?php echo htmlspecialchars(ucfirst($productData['status'])); ?></span>
                            <?php if ($productData['Featured']): ?>
                                <span class="badge featured-badge"><i class="fas fa-star"></i> Featured</span>
                            <?php endif; ?>
                            <?php if ($productData['IsBestSeller']): ?>
                                <span class="badge bestseller-badge"><i class="fas fa-trophy"></i> Best Seller</span>
                            <?php endif; ?>
                        </div>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($productData['CategoryName'] ?? 'N/A'); ?></p>
                        <p><strong>Condition:</strong> <?php echo htmlspecialchars($productData['Condition']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($productData['Location']); ?></p>
                        <p><strong>Views:</strong> <?php echo htmlspecialchars($productData['ViewsCount']); ?></p>
                        <p><strong>Date Listed:</strong> <?php echo date('Y-m-d H:i', strtotime($productData['DateListed'])); ?></p>
                    </div>

                    <div class="detail-card car-specs-card">
                        <h4>Vehicle Specifications</h4>
                        <p><strong>Make:</strong> <?php echo htmlspecialchars($productData['Make'] ?? 'N/A'); ?></p>
                        <p><strong>Model:</strong> <?php echo htmlspecialchars($productData['Model'] ?? 'N/A'); ?></p>
                        <p><strong>Year:</strong> <?php echo htmlspecialchars($productData['Year'] ?? 'N/A'); ?></p>
                        <p><strong>Mileage:</strong> <?php echo number_format($productData['Mileage'], 0); ?> km</p>
                        <p><strong>Fuel Type:</strong> <?php echo htmlspecialchars($productData['FuelType'] ?? 'N/A'); ?></p>
                        <p><strong>Transmission:</strong> <?php echo htmlspecialchars($productData['Transmission'] ?? 'N/A'); ?></p>
                    </div>

                    <div class="detail-card description-card full-width">
                        <h4>Description</h4>
                        <p><?php echo nl2br(htmlspecialchars($productData['Description'])); ?></p>
                    </div>

                    <div class="detail-card seller-info-card">
                        <h4>Seller Information</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($productData['SellerName'] ?? 'N/A'); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($productData['SellerEmail'] ?? 'N/A'); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($productData['SellerPhone'] ?? 'N/A'); ?></p>
                        <p><a href="user_details.php?id=<?php echo htmlspecialchars($productData['SellerID']); ?>" class="btn-link">View Seller Profile</a></p>
                    </div>

                    <div class="detail-card image-gallery-card full-width">
                        <h4>Product Images</h4>
                        <div class="image-gallery">
                            <?php for ($i = 1; $i <= 5; $i++):
                                $imageUrl = $productData['ImageURL' . ($i > 1 ? $i : '')];
                                if (!empty($imageUrl)): ?>
                                    <img src="../<?php echo htmlspecialchars($imageUrl); ?>" alt="Product Image <?php echo $i; ?>" onerror="this.onerror=null;this.src='../images/default_product.png';">
                                <?php endif;
                            endfor; ?>
                        </div>
                    </div>

                    </div>
            </section>
        </main>
    </div>

    <script src="../admin.js"></script>
    <script>
     
        document.querySelector('.btn-delete').addEventListener('click', function() {
            const productId = this.dataset.productId;
            if (confirm('Are you sure you want to delete listing ID ' + productId + '? This action cannot be undone.')) {
                fetch('../api/delete_listing.php', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Listing deleted successfully!');
                        window.location.href = 'listings.php'; 
                    } else {
                        alert('Error deleting listing: ' + (data.message || 'Unknown error.'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete listing due to a network or server error.');
                });
            }
        });

        
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const adminContainer = document.querySelector('.admin-container');
            if (sidebarToggle && adminContainer) {
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