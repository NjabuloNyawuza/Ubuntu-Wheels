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