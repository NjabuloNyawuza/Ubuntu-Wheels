<?php
session_start();
require_once '../db_connection.php';
require_once 'check_admin.php'; 

$currentPage = 'users';

$userId = $_GET['id'] ?? null;
$userData = null;
$errors = [];

if ($userId) {
    try {
       
        $stmt = $pdo->prepare("
            SELECT
                id, name, email, status, phone_number, bio, location, IsVerified, is_admin
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
        error_log("Error fetching user data for edit: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to load user data for editing.";
        header('Location: users.php');
        exit();
    }
} else {
    $_SESSION['error_message'] = "No user ID provided for editing.";
    header('Location: users.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = $_POST['status'] ?? '';
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $isVerified = isset($_POST['is_verified']) ? 1 : 0;
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0; 


    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (!in_array($status, ['active', 'suspended', 'deactivated'])) {
        $errors[] = "Invalid status selected.";
    }

    
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetch()) {
            $errors[] = "Email already exists for another user.";
        }
    } catch (PDOException $e) {
        error_log("Email uniqueness check error: " . $e->getMessage());
        $errors[] = "Database error during email check.";
    }


    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users
                SET
                    name = :name,
                    email = :email,
                    status = :status,
                    phone_number = :phone_number,
                    bio = :bio,
                    location = :location,
                    IsVerified = :is_verified,
                    is_admin = :is_admin,
                    updated_at = NOW()
                WHERE
                    id = :id
            ");

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':phone_number', $phoneNumber);
            $stmt->bindParam(':bio', $bio);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':is_verified', $isVerified, PDO::PARAM_INT);
            $stmt->bindParam(':is_admin', $isAdmin, PDO::PARAM_INT);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);

            $stmt->execute();

            $_SESSION['success_message'] = "User details updated successfully!";
            header("Location: user_details.php?id=" . $userId); 
            exit();

        } catch (PDOException $e) {
            error_log("Error updating user details: " . $e->getMessage());
            $errors[] = "Failed to update user details. Database error.";
        }
    } else {
     
        $userData['name'] = $name;
        $userData['email'] = $email;
        $userData['status'] = $status;
        $userData['phone_number'] = $phoneNumber;
        $userData['bio'] = $bio;
        $userData['location'] = $location;
        $userData['IsVerified'] = $isVerified;
        $userData['is_admin'] = $isAdmin;
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
        error_log("Database error fetching admin name/avatar for user_edit.php: " . $e->getMessage());
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
        error_log("Error fetching unread counts in user_edit.php: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User: <?php echo htmlspecialchars($userData['name']); ?> - UbuntuTrade Admin</title>
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
                    <h2>Edit User: <?php echo htmlspecialchars($userData['name']); ?></h2>
                    <div class="action-buttons">
                        <a href="user_details.php?id=<?php echo htmlspecialchars($userId); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Details</a>
                        <a href="users.php" class="btn btn-secondary"><i class="fas fa-users"></i> Back to Users</a>
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

                <form action="user_edit.php?id=<?php echo htmlspecialchars($userId); ?>" method="POST" class="admin-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo $userData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="suspended" <?php echo $userData['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="deactivated" <?php echo $userData['status'] === 'deactivated' ? 'selected' : ''; ?>>Deactivated</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="phone_number">Phone Number</label>
                            <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($userData['phone_number'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($userData['location'] ?? ''); ?>">
                        </div>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="is_verified" name="is_verified" value="1" <?php echo $userData['IsVerified'] ? 'checked' : ''; ?>>
                            <label for="is_verified">Is Verified</label>
                        </div>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="is_admin" name="is_admin" value="1" <?php echo $userData['is_admin'] ? 'checked' : ''; ?>>
                            <label for="is_admin">Is Admin</label>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="5"><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
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
</body>
</html>