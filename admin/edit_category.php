<?php
session_start();
require_once '../db_connection.php';
require_once 'check_admin.php';

$currentPage = 'categories';
$page_title = "Edit Car Body Type";

$category = null;
$categoryId = $_GET['id'] ?? null;


if ($categoryId) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, icon_class, slug, Description FROM car_body_types WHERE id = :id");
        $stmt->bindParam(':id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            $_SESSION['error_message'] = "Car body type not found.";
            header('Location: categories.php');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database error fetching category for edit: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error fetching category.";
        header('Location: categories.php');
        exit();
    }
} else {
    $_SESSION['error_message'] = "No category ID provided for editing.";
    header('Location: categories.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? ''); 
    $name = trim($_POST['name'] ?? '');
    $icon_class = trim($_POST['icon_class'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $errors = [];


    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($icon_class)) {
        $errors[] = "Icon Class is required.";
    }
    if (empty($slug)) {
        $slug = strtolower(str_replace(' ', '-', $name));
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM car_body_types WHERE (name = :name OR slug = :slug) AND id != :id");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "A category with this name or slug already exists.";
        }
    } catch (PDOException $e) {
        error_log("Database error checking duplicate category on edit: " . $e->getMessage());
        $errors[] = "Database error during duplicate check.";
    }


    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE car_body_types SET name = :name, icon_class = :icon_class, slug = :slug, Description = :description WHERE id = :id");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':icon_class', $icon_class);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Car body type '{$name}' updated successfully!";
                header('Location: categories.php');
                exit();
            } else {
                $_SESSION['error_message'] = "Failed to update car body type.";
            }
        } catch (PDOException $e) {
            error_log("Database error updating category: " . $e->getMessage());
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = implode("<br>", $errors);
    }

    $_GET['id'] = $id; 
    header('Location: edit_category.php?id=' . $id); 
    exit();
}


$formData = $_SESSION['form_data'] ?? $category;
unset($_SESSION['form_data']); 


$adminName = "Admin User";
$adminAvatar = "../images/avatars/default.jpg";

if (isset($_SESSION['user_id'])) {
    $current_admin_id = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT name, profile_picture FROM users WHERE id = :admin_id AND is_admin = 1");
        $stmt->bindParam(':admin_id', $current_admin_id, PDO::PARAM_INT);
        $stmt->execute();
        $adminInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($adminInfo) {
            $adminName = $adminInfo['name'] ?? "Admin User";
            $adminAvatar = !empty($adminInfo['profile_picture']) ? $adminInfo['profile_picture'] : "../images/avatars/default.jpg";
        }
    } catch (PDOException $e) {
        error_log("Database error fetching admin name/avatar for edit_category.php: " . $e->getMessage());
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
        error_log("Error fetching unread counts in edit_category.php: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - UbuntuTrade Admin</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .form-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            max-width: 700px;
            margin: 30px auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }
        .form-actions .btn {
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        .form-actions .btn-primary {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
        }
        .form-actions .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .form-actions .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: 1px solid #6c757d;
        }
        .form-actions .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 1em;
            color: #fff;
        }
        .alert.error-alert {
            background-color: #dc3545;
        }
        .alert.success-alert {
            background-color: #28a745;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .section-header h2 {
            margin: 0;
            font-size: 1.8em;
            color: #333;
        }
        .section-actions .btn {
            padding: 8px 15px;
            font-size: 0.9em;
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
                                    echo '<p>User reported: John Smith</p>';
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
                    <h2><?php echo $page_title; ?> (ID: <?php echo htmlspecialchars($categoryId); ?>)</h2>
                    <div class="section-actions">
                        <a href="categories.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Car Body Types</a>
                    </div>
                </div>

                <div class="form-container">
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert error-alert">
                            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert success-alert">
                            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="edit_category.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($formData['id'] ?? ''); ?>">
                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="icon_class">Icon Class (e.g., fas fa-car-side):</label>
                            <input type="text" id="icon_class" name="icon_class" value="<?php echo htmlspecialchars($formData['icon_class'] ?? ''); ?>" required>
                            <small>Find icons at <a href="https://fontawesome.com/icons?d=gallery&m=free" target="_blank">Font Awesome Free</a></small>
                        </div>
                        <div class="form-group">
                            <label for="slug">Slug (URL friendly, e.g., sedan):</label>
                            <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($formData['slug'] ?? ''); ?>">
                            <small>Leave empty to auto-generate from name.</small>
                        </div>
                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea id="description" name="description"><?php echo htmlspecialchars($formData['Description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                            <a href="categories.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
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

        
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');

            if (nameInput && slugInput) {
                
                slugInput.dataset.manual = (slugInput.value !== '') ? 'true' : 'false';

                nameInput.addEventListener('input', function() {
                    if (slugInput.dataset.manual === 'false') { 
                        slugInput.value = nameInput.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                    }
                });
                slugInput.addEventListener('input', function() {
                    if (slugInput.value !== '') {
                        slugInput.dataset.manual = 'true'; 
                    } else {
                        slugInput.dataset.manual = 'false'; 
                    }
                });
            }
        });
    </script>
</body>
</html>