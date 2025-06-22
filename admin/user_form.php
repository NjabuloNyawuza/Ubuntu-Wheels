<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../db_connection.php';
session_start();


if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php?redirect=admin/user_form');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin User';
$adminAvatar = $_SESSION['user_avatar'] ?? '../images/avatars/default.jpg';

$currentPage = 'users'; 

$userId = null;
$userData = [
    'name' => '',
    'email' => '',
    'is_admin' => false,
    'avatar' => '../images/avatars/default.jpg', 
    'location' => '',
    'phone_number' => '',
    'bio' => ''
];
$formTitle = 'Add New User';
$message = '';
$messageType = '';


if (isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    $formTitle = 'Edit User';

    try {
        $stmt = $pdo->prepare("SELECT id, name, email, is_admin, avatar, location, phone_number, bio FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $fetchedData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fetchedData) {
            $userData = array_merge($userData, $fetchedData);
           
            $userData['is_admin'] = (bool)$userData['is_admin'];
        } else {
            $message = "User not found.";
            $messageType = 'error';
            $userId = null; 
        }
    } catch (PDOException $e) {
        $message = "Database error fetching user: " . $e->getMessage();
        $messageType = 'error';
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['id'] ?? null; 
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
    $location = trim($_POST['location'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    $avatar = $_POST['avatar'] ?? $userData['avatar']; 

    if (empty($name) || empty($email)) {
        $message = "Name and Email are required.";
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = 'error';
    } elseif (!empty($password) && strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $messageType = 'error';
    } else {
        try {
            $pdo->beginTransaction();

            $hashedPassword = null;
            if (!empty($password)) { 
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                if ($hashedPassword === false) {
                    throw new Exception("Password hashing failed.");
                }
            }

            if ($userId) { 
                
                if ($userId == $_SESSION['user_id'] && $isAdmin == 0) {
                    
                     $stmtCountAdmins = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_admin = TRUE");
                     $stmtCountAdmins->execute();
                     if ($stmtCountAdmins->fetchColumn() <= 1) {
                        throw new Exception("Cannot revoke admin status from the last remaining administrator account.");
                     }
                }
                
                $sql = "UPDATE users SET name = ?, email = ?, is_admin = ?, location = ?, phone_number = ?, bio = ?, avatar = ?";
                $params = [$name, $email, $isAdmin, $location, $phone_number, $bio, $avatar];
                
                if ($hashedPassword) { 
                    $sql .= ", password_hash = ?";
                    $params[] = $hashedPassword;
                }
                $sql .= " WHERE id = ?";
                $params[] = $userId;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $message = "User updated successfully!";
                $messageType = 'success';

              
                if ($userId == $_SESSION['user_id']) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['is_admin'] = (bool)$isAdmin;
                    $_SESSION['user_avatar'] = $avatar;
                }

            } else { 
                if (empty($password)) {
                    throw new Exception("Password is required for new users.");
                }

                $sql = "INSERT INTO users (name, email, password_hash, is_admin, location, phone_number, bio, avatar) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $email, $hashedPassword, $isAdmin, $location, $phone_number, $bio, $avatar]);
                $message = "User added successfully!";
                $messageType = 'success';
                $userId = $pdo->lastInsertId(); 

            }
            $pdo->commit();
        
            header("Location: users.php?message=" . urlencode($message) . "&type=" . $messageType);
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
         
            if ($e->getCode() == 23000) { 
                $message = "Error: The email '" . htmlspecialchars($email) . "' is already in use.";
            } else {
                $message = "Database error: " . $e->getMessage();
            }
            $messageType = 'error';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }
 
    $userData = [
        'name' => $name,
        'email' => $email,
        'is_admin' => (bool)$isAdmin,
        'avatar' => $avatar,
        'location' => $location,
        'phone_number' => $phone_number,
        'bio' => $bio
    ];
}


$userData['is_admin'] = (bool)$userData['is_admin'];


if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $messageType = htmlspecialchars($_GET['type']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $formTitle; ?> - UbuntuTrade Admin</title>
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
                                <span class="badge">5</span>
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
                        <input type="text" name="query" placeholder="Search...">
                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <div class="header-actions">
                    <div class="header-notifications">
                        <button class="notification-btn">
                            <i class="far fa-bell"></i>
                            <span class="badge">3</span>
                        </button>
                        <div class="notification-dropdown">
                            </div>
                    </div>
                    
                    <div class="header-messages">
                        <button class="message-btn">
                            <i class="far fa-envelope"></i>
                            <span class="badge">5</span>
                        </button>
                        <div class="message-dropdown">
                            </div>
                    </div>
                    
                    <div class="header-profile">
                        <button class="profile-btn">
                            <img src="<?php echo $adminAvatar; ?>" alt="<?php echo $adminName; ?>">
                            <span><?php echo $adminName; ?></span>
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
            
            <div class="admin-content">
                <div class="page-header">
                    <h1><?php echo $formTitle; ?></h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo $message; ?>
                        <button type="button" class="close-alert">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="form-card">
                    <form action="user_form.php" method="POST" class="admin-form">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($userId); ?>">
                        
                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password (leave blank to keep current):</label>
                            <input type="password" id="password" name="password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>

                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="is_admin" name="is_admin" <?php echo $userData['is_admin'] ? 'checked' : ''; ?> 
                                <?php echo ($userId == $_SESSION['user_id'] && $_SESSION['is_admin']) ? 'disabled' : ''; ?>
                                >
                            <label for="is_admin">Is Admin?</label>
                            <?php if ($userId == $_SESSION['user_id'] && $_SESSION['is_admin']): ?>
                                <small class="form-text text-muted">You cannot revoke your own admin status if you are the only admin.</small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="location">Location:</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($userData['location']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone_number">Phone Number:</label>
                            <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($userData['phone_number']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="bio">Bio:</label>
                            <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($userData['bio']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="avatar">Avatar Image Path (e.g., ../images/avatars/user1.jpg):</label>
                            <input type="text" id="avatar" name="avatar" value="<?php echo htmlspecialchars($userData['avatar']); ?>">
                            <small class="form-text text-muted">For file uploads, you'd integrate a file input here.</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><?php echo $userId ? 'Update User' : 'Add User'; ?></button>
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script src="admin.js"></script> <script>
    
        document.addEventListener('DOMContentLoaded', function() {
            const closeAlertBtn = document.querySelector('.alert .close-alert');
            if (closeAlertBtn) {
                closeAlertBtn.addEventListener('click', function() {
                    this.closest('.alert').style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>