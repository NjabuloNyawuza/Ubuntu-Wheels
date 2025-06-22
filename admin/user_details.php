<?php
session_start();
require_once '../db_connection.php';
require_once 'check_admin.php'; 

$currentPage = 'users'; 

$userId = $_GET['id'] ?? null;
$userData = null;

if ($userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                id, name, email, status, phone_number, bio, rating, total_reviews,
                location, IsVerified, avatar, created_at, updated_at, is_admin, email_verified_at
            FROM
                users
            WHERE
                id = :id
        ");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            $_SESSION['error_message'] = "User not found.";
            header('Location: users.php');
            exit();
        }

    } catch (PDOException $e) {
        error_log("Error fetching user details: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to load user details. Please try again.";
        header('Location: users.php');
        exit();
    }
} else {
    $_SESSION['error_message'] = "No user ID provided.";
    header('Location: users.php');
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
        error_log("Database error fetching admin name/avatar for user_details.php: " . $e->getMessage());
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
        error_log("Error fetching unread counts in user_details.php: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details: <?php echo htmlspecialchars($userData['name']); ?> - UbuntuTrade Admin</title>
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
                    <h2>User Details: <?php echo htmlspecialchars($userData['name']); ?></h2>
                    <div class="action-buttons">
                        <a href="user_edit.php?id=<?php echo htmlspecialchars($userData['id']); ?>" class="btn btn-edit" title="Edit User"><i class="fas fa-edit"></i> Edit</a>
                        <button class="btn btn-delete" data-user-id="<?php echo htmlspecialchars($userData['id']); ?>" title="Delete User"><i class="fas fa-trash-alt"></i> Delete</button>
                        <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Users</a>
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

                <div class="user-details-grid">
                    <div class="detail-card profile-card">
                        <div class="profile-header">
                            <img src="<?php echo htmlspecialchars($userData['avatar'] ?? '../images/avatars/default.jpg'); ?>" alt="User Avatar" class="user-detail-avatar" onerror="this.onerror=null;this.src='../images/avatars/default.jpg';">
                            <h3><?php echo htmlspecialchars($userData['name']); ?>
                                <?php if ($userData['is_admin']): ?>
                                    <span class="badge admin-badge">Admin</span>
                                <?php endif; ?>
                            </h3>
                            <p class="user-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($userData['email']); ?></p>
                            <?php if (!empty($userData['phone_number'])): ?>
                                <p class="user-phone"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($userData['phone_number']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="profile-body">
                            <p><strong>Status:</strong> <span class="status-badge status-<?php echo htmlspecialchars($userData['status']); ?>"><?php echo htmlspecialchars(ucfirst($userData['status'])); ?></span></p>
                            <p><strong>Verified:</strong> <?php echo $userData['IsVerified'] ? '<i class="fas fa-check-circle verified-icon"></i> Yes' : '<i class="fas fa-times-circle unverified-icon"></i> No'; ?></p>
                            <?php if (!empty($userData['location'])): ?>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($userData['location']); ?></p>
                            <?php endif; ?>
                            <p><strong>Joined:</strong> <?php echo date('Y-m-d H:i', strtotime($userData['created_at'])); ?></p>
                            <p><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i', strtotime($userData['updated_at'])); ?></p>
                            <?php if (!empty($userData['email_verified_at'])): ?>
                                <p><strong>Email Verified:</strong> <?php echo date('Y-m-d H:i', strtotime($userData['email_verified_at'])); ?></p>
                            <?php else: ?>
                                <p><strong>Email Verified:</strong> Not Verified</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-card bio-card">
                        <h4>About User</h4>
                        <p><?php echo !empty($userData['bio']) ? nl2br(htmlspecialchars($userData['bio'])) : 'No bio provided.'; ?></p>
                    </div>

                    <div class="detail-card rating-reviews-card">
                        <h4>Ratings & Reviews</h4>
                        <p><strong>Rating:</strong> <?php echo htmlspecialchars(number_format($userData['rating'], 2)); ?> / 5.00</p>
                        <p><strong>Total Reviews:</strong> <?php echo htmlspecialchars($userData['total_reviews']); ?></p>
                        </div>

                    </div>
            </section>
        </main>
    </div>

    <script src="../admin.js"></script>
    <script>
       
        document.querySelector('.btn-delete').addEventListener('click', function() {
            const userId = this.dataset.userId;
            if (confirm('Are you sure you want to delete user ID ' + userId + '? This action cannot be undone.')) {
                fetch('../api/delete_user.php', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User deleted successfully!');
                        window.location.href = 'users.php'; 
                    } else {
                        alert('Error deleting user: ' + (data.message || 'Unknown error.'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete user due to a network or server error.');
                });
            }
        });
    </script>
</body>
</html>