<?php
session_start();
require_once '../db_connection.php';
require_once 'check_admin.php'; 

$currentPage = 'settings';

$admin_user_id = $_SESSION['user_id'] ?? null;

if (!$admin_user_id) {
    $_SESSION['error_message'] = "Admin user not logged in.";
    header('Location: login.php');
    exit();
}

$adminName = "Admin User";
$adminEmail = "";
$adminAvatar = "../images/avatars/default.jpg";
$adminInfo = null;


try {
    $stmt = $pdo->prepare("SELECT name, email, profile_picture FROM users WHERE id = :admin_id AND is_admin = 1");
    $stmt->bindParam(':admin_id', $admin_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $adminInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($adminInfo) {
        $adminName = htmlspecialchars($adminInfo['name']);
        $adminEmail = htmlspecialchars($adminInfo['email']);
        $adminAvatar = !empty($adminInfo['profile_picture']) ? htmlspecialchars($adminInfo['profile_picture']) : "../images/avatars/default.jpg";
    } else {
        $_SESSION['error_message'] = "Admin user not found or not recognized.";
        header('Location: logout.php'); 
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error fetching admin info for settings: " . $e->getMessage());
    $_SESSION['error_message'] = "Failed to load admin profile data.";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newName = trim($_POST['name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $uploadDir = '../images/avatars/';
    $newAvatarPath = $adminInfo['profile_picture']; 

   
    if (empty($newName) || empty($newEmail)) {
        $_SESSION['error_message'] = "Name and Email cannot be empty.";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
    } else {
      
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
            $fileName = $_FILES['profile_picture']['name'];
            $fileSize = $_FILES['profile_picture']['size'];
            $fileType = $_FILES['profile_picture']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = ['jpg', 'gif', 'png', 'jpeg'];
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                  
                    if ($adminInfo['profile_picture'] && $adminInfo['profile_picture'] !== '../images/avatars/default.jpg' && file_exists($adminInfo['profile_picture'])) {
                        unlink($adminInfo['profile_picture']);
                    }
                    $newAvatarPath = $destPath;
                } else {
                    $_SESSION['error_message'] = "Failed to upload profile picture.";
                }
            } else {
                $_SESSION['error_message'] = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
            }
        }

      
        if (!isset($_SESSION['error_message'])) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email, profile_picture = :profile_picture WHERE id = :admin_id");
                $stmt->bindParam(':name', $newName);
                $stmt->bindParam(':email', $newEmail);
                $stmt->bindParam(':profile_picture', $newAvatarPath);
                $stmt->bindParam(':admin_id', $admin_user_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Profile updated successfully!";
                
                    $_SESSION['user_name'] = $newName;
                    $_SESSION['profile_picture'] = $newAvatarPath;
                
                    header("Location: settings.php"); 
                    exit();
                } else {
                    $_SESSION['error_message'] = "Failed to update profile.";
                }
            } catch (PDOException $e) {
                error_log("Database error updating admin profile: " . $e->getMessage());
                $_SESSION['error_message'] = "Database error: " . $e->getMessage();
            }
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

  
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['error_message'] = "All password fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $_SESSION['error_message'] = "New password and confirm password do not match.";
    } elseif (strlen($newPassword) < 8) {
        $_SESSION['error_message'] = "New password must be at least 8 characters long.";
    } else {
        try {
          
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :admin_id AND is_admin = 1");
            $stmt->bindParam(':admin_id', $admin_user_id, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($currentPassword, $user['password'])) {
              
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

              
                $stmt = $pdo->prepare("UPDATE users SET password = :new_password WHERE id = :admin_id");
                $stmt->bindParam(':new_password', $hashedNewPassword);
                $stmt->bindParam(':admin_id', $admin_user_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Password changed successfully!";
                    header("Location: settings.php"); 
                    exit();
                } else {
                    $_SESSION['error_message'] = "Failed to change password.";
                }
            } else {
                $_SESSION['error_message'] = "Incorrect current password.";
            }
        } catch (PDOException $e) {
            error_log("Database error changing admin password: " . $e->getMessage());
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        }
    }
}


$unreadAdminMessages = 0;
$unreadNotifications = 0;

if (isset($_SESSION['user_id'])) {
    $admin_user_id_for_counts = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT COUNT(message_id) FROM messages WHERE receiver_id = :admin_id AND is_read = 0");
        $stmt->bindParam(':admin_id', $admin_user_id_for_counts, PDO::PARAM_INT);
        $stmt->execute();
        $unreadAdminMessages = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(NotificationID) FROM Notifications WHERE UserID = :admin_id AND IsRead = 0");
        $stmt->bindParam(':admin_id', $admin_user_id_for_counts, PDO::PARAM_INT);
        $stmt->execute();
        $unreadNotifications = $stmt->fetchColumn();

    } catch (PDOException $e) {
        error_log("Error fetching unread counts in settings.php: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Ubuntu Wheels Admin</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .settings-container {
            display: flex;
            flex-direction: column;
            gap: 30px;
            max-width: 800px;
            margin: 20px auto;
            padding: 25px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .settings-section {
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .settings-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .settings-section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
            font-size: 1.5em;
        }
        .settings-form .form-group {
            margin-bottom: 15px;
        }
        .settings-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .settings-form input[type="text"],
        .settings-form input[type="email"],
        .settings-form input[type="password"],
        .settings-form input[type="file"] {
            width: calc(100% - 22px); 
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .settings-form button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .settings-form button:hover {
            background-color: #0056b3;
        }
        .profile-picture-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 10px;
            border: 2px solid #ddd;
            display: block; 
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .alert.success-alert {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .alert.error-alert {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
    </style>
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
                                <?php
                                if ($unreadNotifications > 0) {
                                    echo '<p>New listing from Jane Doe</p>';
                                    echo '<p>Payment received for #12345</p>';
                                } else {
                                    echo '<p class="no-items">No new notifications.</p>';
                                }
                                ?>
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
                                <?php
                                if ($unreadAdminMessages > 0) {
                                    echo '<p>From Seller Support</p>';
                                    echo '<p>Regarding listing #67890</p>';
                                } else {
                                    echo '<p class="no-items">No new messages.</p>';
                                }
                                ?>
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
                <div class="section-header">
                    <h2>Admin Settings</h2>
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

                <div class="settings-container">
                    <div class="settings-section">
                        <h3>Update Profile Information</h3>
                        <form action="settings.php" method="POST" enctype="multipart/form-data" class="settings-form">
                            <div class="form-group">
                                <label for="name">Name:</label>
                                <input type="text" id="name" name="name" value="<?php echo $adminName; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" value="<?php echo $adminEmail; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="profile_picture">Profile Picture:</label>
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                                <img src="<?php echo $adminAvatar; ?>" alt="Current Profile Picture" class="profile-picture-preview">
                            </div>
                            <button type="submit" name="update_profile"><i class="fas fa-save"></i> Save Profile</button>
                        </form>
                    </div>

                    <div class="settings-section">
                        <h3>Change Password</h3>
                        <form action="settings.php" method="POST" class="settings-form">
                            <div class="form-group">
                                <label for="current_password">Current Password:</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password:</label>
                                <input type="password" id="new_password" name="new_password" required minlength="8">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password:</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                            </div>
                            <button type="submit" name="change_password"><i class="fas fa-key"></i> Change Password</button>
                        </form>
                    </div>

                    <div class="settings-section">
                        <h3>General Site Settings (Coming Soon)</h3>
                        <p>This section will allow you to manage global settings for UbuntuTrade, such as site name, contact information, default values, etc.</p>
                        <button class="btn btn-secondary" disabled>Edit Site Settings</button>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="../js/main.js"></script>
    <script src="admin_scripts.js"></script>
    <script>
       
        document.getElementById('global-search-button').addEventListener('click', function() {
            const globalSearchQuery = document.getElementById('global-search-input').value;
            if (globalSearchQuery) {
                window.location.href = 'dashboard.php?search=' + encodeURIComponent(globalSearchQuery);
            }
        });

   
        document.getElementById('profile_picture').addEventListener('change', function(event) {
            const [file] = event.target.files;
            if (file) {
                document.querySelector('.profile-picture-preview').src = URL.createObjectURL(file);
            }
        });
    </script>
</body>
</html>