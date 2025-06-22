<?php
session_start();
require_once '../db_connection.php'; 
require_once 'check_admin.php'; 


$currentPage = 'categories';


$categories = []; 
$recordsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$searchQuery = $_GET['search'] ?? '';

$whereClauses = [];
$params = [];

if (!empty($searchQuery)) {
    $whereClauses[] = "(name LIKE :searchQuery OR Description LIKE :searchQuery OR slug LIKE :searchQuery)";
    $params[':searchQuery'] = '%' . $searchQuery . '%';
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

try {
   
    $countStmt = $pdo->prepare("
        SELECT COUNT(id)
        FROM car_body_types
        {$whereSql}
    ");
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);

   
    $stmt = $pdo->prepare("
        SELECT
            id, name, icon_class, slug, Description, created_at
        FROM
            car_body_types
        {$whereSql}
        ORDER BY
            name ASC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

   
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC); 

} catch (PDOException $e) {
    error_log("Error fetching categories data: " . $e->getMessage());
    $_SESSION['error_message'] = "Failed to load categories data. Please try again.";
    $categories = [];
    $totalRecords = 0;
    $totalPages = 0;
}


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
        error_log("Database error fetching admin name/avatar for categories.php: " . $e->getMessage());
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
        error_log("Error fetching unread counts in categories.php: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Ubuntu Wheels Admin</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="../style.css"> <link rel="stylesheet" href="admin.css"> <style>
     
        .category-icon {
            font-size: 1.5em;
            vertical-align: middle;
            margin-right: 5px;
        }
        .data-table td.description-cell {
            max-width: 300px; 
            white-space: normal; 
            word-wrap: break-word; 
            overflow: hidden;
            text-overflow: ellipsis; 
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
                    <h2>Manage Car Body Types (Categories)</h2>
                    <div class="section-actions">
                        <a href="add_category.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Body Type</a>
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


                <div class="data-table-controls-wrapper">
                    <button class="scroll-btn scroll-left" id="scroll-left-btn"><i class="fas fa-chevron-left"></i></button>
                    <div class="data-table-container" id="categories-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" class="select-all"></th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Icon</th>
                                    <th>Slug</th>
                                    <th class="description-cell">Description</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($categories) > 0): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><input type="checkbox" class="select-item" value="<?php echo htmlspecialchars($category['id']); ?>"></td>
                                            <td><?php echo htmlspecialchars($category['id']); ?></td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><i class="<?php echo htmlspecialchars($category['icon_class']); ?> category-icon" title="<?php echo htmlspecialchars($category['icon_class']); ?>"></i></td>
                                            <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                            <td class="description-cell"><?php echo htmlspecialchars($category['Description']); ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($category['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="edit_category.php?id=<?php echo htmlspecialchars($category['id']); ?>" class="btn btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                                    <button class="btn btn-delete" title="Delete" onclick="confirmDelete(<?php echo htmlspecialchars($category['id']); ?>, '<?php echo htmlspecialchars($category['name']); ?>')"><i class="fas fa-trash-alt"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="no-results">No car body types found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="scroll-btn scroll-right" id="scroll-right-btn"><i class="fas fa-chevron-right"></i></button>
                </div>

                <div class="pagination">
                    <button class="page-btn prev" <?php echo ($page <= 1) ? 'disabled' : ''; ?> onclick="window.location.href='categories.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchQuery); ?>'">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <button class="page-btn <?php echo ($i === $page) ? 'active' : ''; ?>" onclick="window.location.href='categories.php?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>'">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>

                    <button class="page-btn next" <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?> onclick="window.location.href='categories.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchQuery); ?>'">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
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

    
        function handleSearchEnter(event) {
            if (event.key === 'Enter') {
                const searchQuery = event.target.value;
                window.location.href = `categories.php?search=${encodeURIComponent(searchQuery)}`;
            }
        }

        
        const categoriesTableContainer = document.getElementById('categories-table-container');
        const scrollLeftBtn = document.getElementById('scroll-left-btn');
        const scrollRightBtn = document.getElementById('scroll-right-btn');
        const scrollAmount = 200;

        if (categoriesTableContainer && scrollLeftBtn && scrollRightBtn) {
            function toggleScrollButtons() {
                if (categoriesTableContainer.scrollWidth > categoriesTableContainer.clientWidth) {
                    scrollLeftBtn.style.display = 'block';
                    scrollRightBtn.style.display = 'block';
                    scrollLeftBtn.disabled = (categoriesTableContainer.scrollLeft <= 0);
                    scrollRightBtn.disabled = (categoriesTableContainer.scrollLeft + categoriesTableContainer.clientWidth >= categoriesTableContainer.scrollWidth);
                } else {
                    scrollLeftBtn.style.display = 'none';
                    scrollRightBtn.style.display = 'none';
                }
            }
            toggleScrollButtons();
            window.addEventListener('resize', toggleScrollButtons);
            categoriesTableContainer.addEventListener('scroll', toggleScrollButtons);

            scrollLeftBtn.addEventListener('click', () => {
                categoriesTableContainer.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });
            scrollRightBtn.addEventListener('click', () => {
                categoriesTableContainer.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });
        }

        
        function applyBulkAction() {
            const selectedAction = document.getElementById('bulk-action-select').value;
            const selectedItems = Array.from(document.querySelectorAll('.select-item:checked')).map(cb => cb.value);

            if (selectedAction && selectedItems.length > 0) {
                if (selectedAction === 'delete') {
                    if (confirm('Are you sure you want to delete the selected categories? This action cannot be undone.')) {
                        
                        deleteCategories(selectedItems);
                    }
                } else {
                    alert('Action "' + selectedAction + '" not yet implemented for bulk categories.');
                }
            } else {
                alert('Please select an action and at least one category.');
            }
        }

        function confirmDelete(id, name) {
            if (confirm(`Are you sure you want to delete the category "${name}" (ID: ${id})? This action cannot be undone.`)) {
                deleteCategories([id]);
            }
        }

        function deleteCategories(ids) {
            fetch('delete_categories.php', { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ids=${JSON.stringify(ids)}` 
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Categories deleted successfully!');
                    window.location.reload(); 
                } else {
                    alert('Error deleting categories: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting categories.');
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const selectAllCheckbox = document.querySelector('.select-all');
            const itemCheckboxes = document.querySelectorAll('.select-item');

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    itemCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }
        });
    </script>
</body>
</html>