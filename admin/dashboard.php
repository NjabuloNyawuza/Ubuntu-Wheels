<?php

session_start();

require_once '../db_connection.php'; 
require_once 'check_admin.php';


$stats = [
    'total_users' => 0,
    'new_users_today' => 0,
    'active_listings' => 0,
    'pending_listings' => 0,
    'total_transactions' => 0,
    'transactions_today' => 0,
    'total_revenue' => 0.00,
    'revenue_today' => 0.00,
];
$unreadAdminMessages = 0;
$pendingSellerApplications = 0;
$pendingReports = 0; 
$unreadNotifications = 0; 

$recentUsers = [];
$recentListings = [];
$recentTransactions = [];
$recentReports = []; 


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
        error_log("Database error fetching admin name/avatar: " . $e->getMessage());
       
    }
}


$admin_user_id = $current_admin_id ?? 13; 


try {

    $stmt = $pdo->query("SELECT COUNT(id) FROM users");
    $stats['total_users'] = $stmt->fetchColumn();


    $stmt = $pdo->query("SELECT COUNT(id) FROM users WHERE DATE(created_at) = CURDATE()");
    $stats['new_users_today'] = $stmt->fetchColumn();

   
    $stmt = $pdo->query("SELECT COUNT(ProductID) FROM Products WHERE status = 'active'");
    $stats['active_listings'] = $stmt->fetchColumn();

  
    $stmt = $pdo->query("SELECT COUNT(ProductID) FROM Products WHERE status = 'pending'");
    $stats['pending_listings'] = $stmt->fetchColumn();


    $stmt = $pdo->query("SELECT COUNT(transaction_id) FROM transactions");
    $stats['total_transactions'] = $stmt->fetchColumn();

  
    $stmt = $pdo->query("SELECT COUNT(transaction_id) FROM transactions WHERE DATE(transaction_date) = CURDATE()");
    $stats['transactions_today'] = $stmt->fetchColumn();


    $stmt = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'completed'");
    $stats['total_revenue'] = $stmt->fetchColumn() ?? 0.00; 


    $stmt = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'completed' AND DATE(transaction_date) = CURDATE()");
    $stats['revenue_today'] = $stmt->fetchColumn() ?? 0.00; 


    $stmt = $pdo->prepare("SELECT COUNT(message_id) FROM messages WHERE receiver_id = :admin_id AND is_read = 0");
    $stmt->bindParam(':admin_id', $admin_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $unreadAdminMessages = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(user_id) FROM sellers WHERE status = 'pending'");
    $pendingSellerApplications = $stmt->fetchColumn();


    $stmt = $pdo->query("SELECT COUNT(report_id) FROM reports WHERE status = 'pending'");
    $pendingReports = $stmt->fetchColumn();


    $stmt = $pdo->prepare("SELECT COUNT(NotificationID) FROM Notifications WHERE UserID = :admin_id AND IsRead = 0");
    $stmt->bindParam(':admin_id', $admin_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $unreadNotifications = $stmt->fetchColumn();


} catch (PDOException $e) {
 
    error_log("Database error fetching dashboard stats: " . $e->getMessage());
 
    $stats = array_fill_keys(array_keys($stats), 0);
    $unreadAdminMessages = 0;
    $pendingSellerApplications = 0;
    $pendingReports = 0;
    $unreadNotifications = 0;
}



try {
  
    $stmt = $pdo->query("
        SELECT id, name, email, location, status, avatar, created_at AS joined
        FROM users
        ORDER BY created_at DESC LIMIT 5
    ");
    $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

  
    $stmt = $pdo->query("
        SELECT
            p.ProductID AS id,          -- Correct: Use ProductID as 'id' for consistency with PHP results array
            p.ProductName AS title,     -- Correct: Use ProductName as 'title'
            p.Price AS price,
            p.status,
            p.DateListed AS date,       -- Correct: Use DateListed as 'date'
            s.store_name AS seller,
            cbt.name AS category,       -- Correct: Use cbt.name (from car_body_types) as 'category'
            p.ImageURL AS image         -- Correct: Use ImageURL directly from Products table
        FROM Products p                 -- Correct table name 'Products' (capital P)
        LEFT JOIN sellers s ON p.SellerID = s.user_id -- Correct join column: p.SellerID to s.user_id
        LEFT JOIN car_body_types cbt ON p.CategoryID = cbt.id -- Correct table name 'car_body_types' and join column
        ORDER BY p.DateListed DESC LIMIT 5
    ");
    $recentListings = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
    $stmt = $pdo->query("
        SELECT
            t.transaction_id AS id,
            p.ProductName AS item,          -- Correct: Use ProductName from Products table
            t.amount,
            ub.name AS buyer,
            us.name AS seller,
            t.transaction_date AS date,
            t.status
        FROM transactions t
        LEFT JOIN Products p ON t.listing_id = p.ProductID -- Correct join: Products.ProductID
        LEFT JOIN users ub ON t.buyer_id = ub.id
        LEFT JOIN users us ON t.seller_id = us.id
        ORDER BY t.transaction_date DESC LIMIT 5
    ");
    $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
    $stmt = $pdo->query("
        SELECT
            r.report_id AS id,
            p.ProductName AS reported_item,
            ur.name AS reporter,
            r.report_reason AS reason,
            r.status,
            r.reported_at AS date
        FROM reports r
        LEFT JOIN Products p ON r.listing_id = p.ProductID
        LEFT JOIN users ur ON r.reporter_user_id = ur.id
        ORDER BY r.reported_at DESC LIMIT 5
    ");
    $recentReports = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    error_log("Database error fetching recent activity: " . $e->getMessage());
    $recentUsers = [];
    $recentListings = [];
    $recentTransactions = [];
    $recentReports = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UbuntuTrade</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <div class="header-search">
                <form action="search.php" method="GET">
    <input type="text" placeholder="Search..." name="query">
    <button type="submit"><i class="fas fa-search"></i></button>
</form>
                </div>

                <div class="header-actions">
                    <div class="header-notifications">
                        <button class="notification-btn">
                            <i class="far fa-bell"></i>
                            <span class="badge">3</span> </button>
                        <div class="notification-dropdown">
                            <div class="notification-header">
                                <h3>Notifications</h3>
                                <a href="notifications.php">View All</a>
                            </div>
                            <div class="notification-list">
                                <a href="#" class="notification-item unread">
                                    <div class="notification-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p>New user registration: <strong>John Doe</strong></p>
                                        <span class="notification-time">2 minutes ago</span>
                                    </div>
                                </a>
                                <a href="#" class="notification-item unread">
                                    <div class="notification-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p>Listing reported: <strong>iPhone 13 Pro</strong></p>
                                        <span class="notification-time">45 minutes ago</span>
                                    </div>
                                </a>
                                <a href="#" class="notification-item unread">
                                    <div class="notification-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p>New transaction: <strong>#9002</strong></p>
                                        <span class="notification-time">1 hour ago</span>
                                    </div>
                                </a>
                                <a href="#" class="notification-item">
                                    <div class="notification-icon">
                                        <i class="fas fa-flag"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p>Dispute opened: <strong>#9004</strong></p>
                                        <span class="notification-time">3 hours ago</span>
                                    </div>
                                </a>
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
                                <a href="#" class="message-item unread">
                                    <div class="message-avatar">
                                        <img src="../images/avatars/user1.jpg" alt="John Doe">
                                    </div>
                                    <div class="message-content">
                                        <div class="message-info">
                                            <h4>John Doe</h4>
                                            <span class="message-time">5 min ago</span>
                                        </div>
                                        <p>I need help with my account verification...</p>
                                    </div>
                                </a>
                                <a href="#" class="message-item unread">
                                    <div class="message-avatar">
                                        <img src="../images/avatars/user2.jpg" alt="Sarah Smith">
                                    </div>
                                    <div class="message-content">
                                        <div class="message-info">
                                            <h4>Sarah Smith</h4>
                                            <span class="message-time">25 min ago</span>
                                        </div>
                                        <p>When will my listing be approved?</p>
                                    </div>
                                </a>
                                <a href="#" class="message-item">
                                    <div class="message-avatar">
                                        <img src="../images/avatars/user3.jpg" alt="Michael Johnson">
                                    </div>
                                    <div class="message-content">
                                        <div class="message-info">
                                            <h4>Michael Johnson</h4>
                                            <span class="message-time">2 hours ago</span>
                                        </div>
                                        <p>Thank you for resolving my issue so quickly!</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="header-profile">
                        <button class="profile-btn">
                            <img src="<?php echo htmlspecialchars($adminAvatar); ?>" alt="<?php echo htmlspecialchars($adminName); ?>">
                            <span><?php echo htmlspecialchars($adminName); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="profile-dropdown">
                            <a href="profile.php">
                                <i class="fas fa-user"></i>
                                <span>My Profile</span>
                            </a>
                            <a href="settings.php">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                            <a href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>

                    <button id="theme-toggle" class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </header>
            <div id="searchResultsOverlay" class="search-results-overlay">
                <div class="search-results-container">
                    <button class="close-search-results"><i class="fas fa-times"></i></button>
                    <h2>Search Results for: "<span id="searchQueryDisplay"></span>"</h2>

                    <div class="search-results-content">
                        <div class="search-category" id="searchUsersCategory">
                            <h3>Users (<span id="usersResultCount">0</span>)</h3>
                            <ul id="searchResultsUsers" class="result-list">
                                </ul>
                            <a href="users.php" class="view-all-link" id="viewAllUsersSearch">View All Users</a>
                        </div>

                        <div class="search-category" id="searchListingsCategory">
                            <h3>Listings (<span id="listingsResultCount">0</span>)</h3>
                            <ul id="searchResultsListings" class="result-list">
                                </ul>
                            <a href="listings.php" class="view-all-link" id="viewAllListingsSearch">View All Listings</a>
                        </div>

                        <div class="search-category" id="searchTransactionsCategory">
                            <h3>Transactions (<span id="transactionsResultCount">0</span>)</h3>
                            <ul id="searchResultsTransactions" class="result-list">
                                </ul>
                            <a href="transactions.php" class="view-all-link" id="viewAllTransactionsSearch">View All Transactions</a>
                        </div>

                        <div class="search-category" id="searchReportsCategory">
                            <h3>Reports (<span id="reportsResultCount">0</span>)</h3>
                            <ul id="searchResultsReports" class="result-list">
                                </ul>
                            <a href="reports.php" class="view-all-link" id="viewAllReportsSearch">View All Reports</a>
                        </div>

                        <div class="no-results-message" id="noSearchResults" style="display: none;">
                            <p>No results found for your search query.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-content">
                <div class="page-header">
                    <h1>Dashboard</h1>
                    <div class="page-actions">
                        <button class="btn-refresh" onclick="location.reload();">
                            <i class="fas fa-sync-alt"></i>
                            <span>Refresh</span>
                        </button>
                        <div class="date-filter">
    <label for="dateFilter">Filter by Date:</label>
    <select id="dateFilter"> <option value="all_time">All Time</option>
        <option value="today">Today</option>
        <option value="yesterday">Yesterday</option>
        <option value="this_week">This Week</option>
        <option value="this_month">This Month</option>
        <option value="this_year">This Year</option>
        </select>
</div>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stats-card">
                        <div class="stats-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-info">
                            <h3>Total Users</h3>
                            <div class="stats-number"><?php echo number_format($stats['total_users'] ?? 0); ?></div>
                            <div class="stats-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span><?php echo $stats['new_users_today'] ?? 0; ?> today</span>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card">
                        <div class="stats-icon listings">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stats-info">
                            <h3>Active Listings</h3>
                            <div class="stats-number"><?php echo number_format($stats['active_listings'] ?? 0); ?></div>
                            <div class="stats-change neutral">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $stats['pending_listings'] ?? 0; ?> pending</span>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card">
                        <div class="stats-icon transactions">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="stats-info">
                            <h3>Transactions</h3>
                            <div class="stats-number"><?php echo number_format($stats['total_transactions'] ?? 0); ?></div>
                            <div class="stats-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span><?php echo $stats['transactions_today'] ?? 0; ?> today</span>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card">
                        <div class="stats-icon revenue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stats-info">
                            <h3>Total Revenue</h3>
                            <div class="stats-number">R<?php echo number_format($stats['total_revenue'] ?? 0.00, 2); ?></div>
                            <div class="stats-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>R<?php echo number_format($stats['revenue_today'] ?? 0.00, 2); ?> today</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="charts-section">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Revenue Overview</h3>
                            <div class="chart-actions">
                                <button class="btn-chart-option active" data-period="week">Week</button>
                                <button class="btn-chart-option" data-period="month">Month</button>
                                <button class="btn-chart-option" data-period="year">Year</button>
                            </div>
                        </div>
                        <div class="chart-body">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>User Growth</h3>
                            <div class="chart-actions">
                                <button class="btn-chart-option active" data-period="week">Week</button>
                                <button class="btn-chart-option" data-period="month">Month</button>
                                <button class="btn-chart-option" data-period="year">Year</button>
                            </div>
                        </div>
                        <div class="chart-body">
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="recent-section">
                    <div class="section-header">
                        <h2>Recent Activity</h2>
                    </div>

                    <div class="tabs">
                        <button class="tab-btn active" data-tab="users">Users</button>
                        <button class="tab-btn" data-tab="listings">Listings</button>
                        <button class="tab-btn" data-tab="transactions">Transactions</button>
                    </div>

                    <div class="tab-content active" id="users-tab">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Location</th>
                                        <th>Joined</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentUsers)): ?>
                                        <tr><td colspan="6">No recent users found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentUsers as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="user-info">
                                                        <img src="<?php echo htmlspecialchars($user['avatar'] ?? '../images/avatars/default.jpg'); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>">
                                                        <div>
                                                            <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                                            <span>ID: <?php echo htmlspecialchars($user['id']); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['location'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($user['joined'])); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo htmlspecialchars($user['status']); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <a href="users.php?view=<?php echo htmlspecialchars($user['id']); ?>" class="btn-action view" title="View User">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="users.php?edit=<?php echo htmlspecialchars($user['id']); ?>" class="btn-action edit" title="Edit User">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn-action delete" title="Delete User" data-id="<?php echo htmlspecialchars($user['id']); ?>">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-footer">
                            <a href="users.php" class="btn-view-all">View All Users</a>
                        </div>
                    </div>

                    <div class="tab-content" id="listings-tab">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Listing</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Seller</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentListings)): ?>
                                        <tr><td colspan="7">No recent listings found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentListings as $listing): ?>
                                            <tr>
                                                <td>
                                                    <div class="listing-info">
                                                        <img src="<?php echo htmlspecialchars($listing['image'] ?? '../images/placeholders/car_placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                                                        <div>
                                                            <h4><?php echo htmlspecialchars($listing['title']); ?></h4>
                                                            <span>ID: <?php echo htmlspecialchars($listing['id']); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($listing['category'] ?? 'N/A'); ?></td>
                                                <td>R<?php echo number_format($listing['price'] ?? 0.00, 2); ?></td>
                                                <td><?php echo htmlspecialchars($listing['seller'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($listing['date'])); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo htmlspecialchars($listing['status']); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($listing['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <a href="listings.php?view=<?php echo htmlspecialchars($listing['id']); ?>" class="btn-action view" title="View Listing">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="listings.php?edit=<?php echo htmlspecialchars($listing['id']); ?>" class="btn-action edit" title="Edit Listing">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn-action delete" title="Delete Listing" data-id="<?php echo htmlspecialchars($listing['id']); ?>">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-footer">
                            <a href="listings.php" class="btn-view-all">View All Listings</a>
                        </div>
                    </div>

                    <div class="tab-content" id="transactions-tab">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Item</th>
                                        <th>Amount</th>
                                        <th>Buyer</th>
                                        <th>Seller</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentTransactions)): ?>
                                        <tr><td colspan="8">No recent transactions found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentTransactions as $transaction): ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($transaction['id']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['item'] ?? 'N/A'); ?></td>
                                                <td>R<?php echo number_format($transaction['amount'] ?? 0.00, 2); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['buyer'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['seller'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo htmlspecialchars($transaction['status']); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($transaction['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <a href="transactions.php?view=<?php echo htmlspecialchars($transaction['id']); ?>" class="btn-action view" title="View Transaction">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (($transaction['status'] ?? '') === 'disputed'): ?>
                                                            <a href="transactions.php?resolve=<?php echo htmlspecialchars($transaction['id']); ?>" class="btn-action resolve" title="Resolve Dispute">
                                                                <i class="fas fa-gavel"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-footer">
                            <a href="transactions.php" class="btn-view-all">View All Transactions</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this item? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel">Cancel</button>
                <button class="btn-delete">Delete</button>
            </div>
        </div>
    </div>

    <script src="admin.js"></script>
</body>
</html>