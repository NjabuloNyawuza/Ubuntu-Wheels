<?php
// This header is included in all admin pages.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Ubuntu Wheels</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="admin/admin.css"> </head>
<body class="admin-body">
    <div class="admin-container">
        <header class="admin-header">
            <div class="header-left">
                <button id="sidebar-toggle" class="sidebar-toggle-btn"><i class="fas fa-bars"></i></button>
                <a href="dashboard.php" class="header-logo">UbuntuTrade Admin</a>
            </div>
            <div class="header-right">
                <div class="header-notifications dropdown">
                    <button class="notification-btn"><i class="fas fa-bell"></i><span class="badge">3</span></button>
                    <div class="notification-dropdown dropdown-content">
                        <h4>Notifications</h4>
                        <a href="#">New listing from Jane Doe</a>
                        <a href="#">Payment received for #12345</a>
                        <a href="#">User reported: John Smith</a>
                        <a href="#" class="view-all">View All</a>
                    </div>
                </div>
                <div class="header-messages dropdown">
                    <button class="message-btn"><i class="fas fa-envelope"></i><span class="badge">2</span></button>
                    <div class="message-dropdown dropdown-content">
                        <h4>Messages</h4>
                        <a href="#">From Seller Support</a>
                        <a href="#">Regarding listing #67890</a>
                        <a href="#" class="view-all">View All</a>
                    </div>
                </div>
                <button id="theme-toggle" class="theme-toggle-btn">
                    <i class="fas fa-moon"></i> </button>
                <div class="header-profile dropdown">
                    <button class="profile-btn">
                        <img src="<?php echo htmlspecialchars($_SESSION['user_avatar'] ?? '../images/avatars/default.jpg'); ?>" alt="Admin Avatar" class="admin-avatar">
                        <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="profile-dropdown dropdown-content">
                        <a href="#">Profile</a>
                        <a href="#">Settings</a>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </header>
        ```

**2. `admin/admin_sidebar.php`**

```php
<?php
// This sidebar is included in all admin pages.
// It contains the navigation links for the admin panel.
?>
        <aside class="admin-sidebar">
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="listings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'listings.php' ? 'active' : ''; ?>"><i class="fas fa-tag"></i> Listings</a></li>
                    <li><a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>"><i class="fas fa-th-list"></i> Categories</a></li>
                    <li><a href="transactions.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                    <li><a href="reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>"><i class="fas fa-star"></i> Reviews</a></li>
                    <li><a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-flag"></i> Reports</a></li>
                    <li><a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>