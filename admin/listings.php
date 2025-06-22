<?php
session_start();
require_once '../db_connection.php'; 
require_once 'check_admin.php'; 


$currentPage = 'listings';


$products = [];
$recordsPerPage = 10; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$searchQuery = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterFeatured = $_GET['featured'] ?? '';
$filterBestSeller = $_GET['bestseller'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$filterMake = $_GET['make'] ?? '';

$whereClauses = [];
$params = [];

if (!empty($searchQuery)) {
    $whereClauses[] = "(ProductName LIKE :searchQuery OR Description LIKE :searchQuery OR Make LIKE :searchQuery OR Model LIKE :searchQuery OR Location LIKE :searchQuery)";
    $params[':searchQuery'] = '%' . $searchQuery . '%';
}

if (!empty($filterStatus) && in_array($filterStatus, ['active', 'pending', 'sold', 'draft', 'archived'])) {
    $whereClauses[] = "P.status = :status"; 
    $params[':status'] = $filterStatus;
}

if ($filterFeatured !== '' && in_array($filterFeatured, ['0', '1'])) {
    $whereClauses[] = "Featured = :featured";
    $params[':featured'] = (int)$filterFeatured;
}

if ($filterBestSeller !== '' && in_array($filterBestSeller, ['0', '1'])) {
    $whereClauses[] = "IsBestSeller = :isBestSeller";
    $params[':isBestSeller'] = (int)$filterBestSeller;
}

if (!empty($filterCategory)) {
    $whereClauses[] = "C.CategoryID = :category_id"; 
    $params[':category_id'] = (int)$filterCategory;
}

if (!empty($filterMake)) {
    $whereClauses[] = "Make LIKE :make";
    $params[':make'] = '%' . $filterMake . '%';
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

try {

    $countStmt = $pdo->prepare("
        SELECT COUNT(P.ProductID)
        FROM Products P
        LEFT JOIN Categories C ON P.CategoryID = C.CategoryID
        {$whereSql}
    ");
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);

   
    $stmt = $pdo->prepare("
        SELECT
            P.ProductID, P.ProductName, P.Price, P.CategoryID, P.Condition,
            P.ImageURL, P.DateListed, P.Featured, P.SellerID, P.Location,
            P.Make, P.Model, P.Year, P.Mileage, P.FuelType, P.Transmission,
            P.ViewsCount, P.IsBestSeller, P.status,
            U.name AS SellerName,
            C.CategoryName
        FROM
            Products P
        LEFT JOIN
            users U ON P.SellerID = U.id
        LEFT JOIN
            Categories C ON P.CategoryID = C.CategoryID
        {$whereSql}
        ORDER BY
            P.DateListed DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

 
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

   
    $categoriesStmt = $pdo->query("SELECT CategoryID, CategoryName FROM Categories ORDER BY CategoryName");
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching products data: " . $e->getMessage());
    $_SESSION['error_message'] = "Failed to load product data. Please try again.";
    $products = [];
    $totalRecords = 0;
    $totalPages = 0;
    $categories = [];
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
        error_log("Database error fetching admin name/avatar for listings.php: " . $e->getMessage());
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
        error_log("Error fetching unread counts in listings.php: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Listings - UbuntuTrade Admin</title>
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

            <section id="listings-page">
    <h2>Manage Listings</h2>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th><input type="checkbox" class="select-all"></th>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Make/Model/Year</th>
                    <th>Price</th>
                    <th>Category</th>
                    <th>Condition</th>
                    <th>Seller</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Featured</th>
                    <th>Best Seller</th>
                    <th>Views</th>
                    <th>Date Listed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><input type="checkbox" class="select-item" value="<?php echo htmlspecialchars($product['ProductID']); ?>"></td>
                            <td><?php echo htmlspecialchars($product['ProductID']); ?></td>
                            <td>
                                <img src="../<?php echo htmlspecialchars($product['ImageURL']); ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>" class="table-thumbnail" onerror="this.onerror=null;this.src='../images/default_product.png';">
                            </td>
                            <td><?php echo htmlspecialchars($product['ProductName']); ?></td>
                            <td><?php echo htmlspecialchars($product['Make'] ?? 'N/A') . ' ' . htmlspecialchars($product['Model'] ?? 'N/A') . ' (' . htmlspecialchars($product['Year'] ?? 'N/A') . ')'; ?></td>
                            <td>R <?php echo number_format($product['Price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($product['CategoryName'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($product['Condition']); ?></td>
                            <td><?php echo htmlspecialchars($product['SellerName'] ?? 'Unknown Seller'); ?></td>
                            <td><?php echo htmlspecialchars($product['Location'] ?? 'N/A'); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars($product['status']); ?>"><?php echo htmlspecialchars(ucfirst($product['status'])); ?></span></td>
                            <td><?php echo $product['Featured'] ? '<i class="fas fa-check-circle verified-icon"></i> Yes' : '<i class="fas fa-times-circle unverified-icon"></i> No'; ?></td>
                            <td><?php echo $product['IsBestSeller'] ? '<i class="fas fa-check-circle verified-icon"></i> Yes' : '<i class="fas fa-times-circle unverified-icon"></i> No'; ?></td>
                            <td><?php echo htmlspecialchars($product['ViewsCount']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($product['DateListed'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="listing_details.php?id=<?php echo htmlspecialchars($product['ProductID']); ?>" class="btn btn-view" title="View Details"><i class="fas fa-eye"></i></a>
                                    <button class="btn btn-edit" title="Edit Listing" data-product-id="<?php echo htmlspecialchars($product['ProductID']); ?>"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-delete" title="Delete Listing" data-product-id="<?php echo htmlspecialchars($product['ProductID']); ?>"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="16" class="no-results">No listings found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div> <div class="pagination">
        <button class="page-btn prev" <?php echo ($page <= 1) ? 'disabled' : ''; ?> onclick="window.location.href='listings.php?page=<?php echo $page - 1; ?>&<?php echo http_build_query($_GET); ?>'">
            <i class="fas fa-chevron-left"></i> Previous
        </button>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <button class="page-btn <?php echo ($i === $page) ? 'active' : ''; ?>" onclick="window.location.href='listings.php?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>'">
                <?php echo $i; ?>
            </button>
        <?php endfor; ?>

        <button class="page-btn next" <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?> onclick="window.location.href='listings.php?page=<?php echo $page + 1; ?>&<?php echo http_build_query($_GET); ?>'">
            Next <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</section>
        </main>
    </div>

    <script src="../admin.js"></script>
    <script>
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


            const bulkActionSelect = document.querySelector('.bulk-actions select');
            const bulkActionApply = document.querySelector('.bulk-actions .btn-apply');
            const selectAllCheckbox = document.querySelector('.select-all');
            const itemCheckboxes = document.querySelectorAll('.select-item');

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    itemCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            if (bulkActionApply) {
                bulkActionApply.addEventListener('click', function() {
                    const action = bulkActionSelect.value;
                    const selectedItems = Array.from(itemCheckboxes)
                                            .filter(cb => cb.checked)
                                            .map(cb => cb.value);

                    if (!action) {
                        alert('Please select a bulk action.');
                        return;
                    }

                    if (selectedItems.length === 0) {
                        alert('Please select at least one listing.');
                        return;
                    }

                    if (confirm(`Are you sure you want to ${action} ${selectedItems.length} listing(s)?`)) {
                      
                        console.log(`Applying action: ${action} to products:`, selectedItems);
                        alert('Bulk action functionality is a placeholder. See console for details.');
                       
                    }
                });
            }

           
            // <a href="listing_details.php?id=<?php echo htmlspecialchars($product['ProductID']); ?>" class="btn btn-view" title="View Details"><i class="fas fa-eye"></i></a>

            document.querySelectorAll('.btn-edit').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId; 
                    window.location.href = 'listing_edit.php?id=' + productId; 
                });
            });

            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function() {
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
                                location.reload(); 
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
            });

           
            window.tableSearch = function(inputElement) {
                const searchTerm = inputElement.value.toLowerCase();
                const tableRows = document.querySelectorAll('.data-table tbody tr');

                tableRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            };
        });
    </script>
</body>
</html>