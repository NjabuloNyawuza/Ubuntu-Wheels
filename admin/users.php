<?php
session_start();
require_once '../db_connection.php'; 
require_once 'check_admin.php'; 


$currentPage = 'users';


$users = [];
$recordsPerPage = 10; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$searchQuery = $_GET['search'] ?? ''; 
$filterStatus = $_GET['status'] ?? ''; 
$filterVerified = $_GET['verified'] ?? ''; 

$whereClauses = [];
$params = [];

if (!empty($searchQuery)) {
    $whereClauses[] = "(name LIKE :searchQuery OR email LIKE :searchQuery OR location LIKE :searchQuery)";
    $params[':searchQuery'] = '%' . $searchQuery . '%';
}

if (!empty($filterStatus) && in_array($filterStatus, ['active', 'suspended', 'deactivated'])) {
    $whereClauses[] = "status = :status";
    $params[':status'] = $filterStatus;
}

if ($filterVerified !== '' && in_array($filterVerified, ['0', '1'])) {
    $whereClauses[] = "IsVerified = :isVerified";
    $params[':isVerified'] = (int)$filterVerified;
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

try {
   
    $countStmt = $pdo->prepare("SELECT COUNT(id) FROM users {$whereSql}");
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);


    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            email,
            status,
            phone_number,
            location,
            IsVerified,
            avatar,
            created_at,
            is_admin
        FROM
            users
        {$whereSql}
        ORDER BY
            created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);


    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching users data: " . $e->getMessage());

    $users = [];
    $totalRecords = 0;
    $totalPages = 0;
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
        error_log("Database error fetching admin name/avatar for users.php: " . $e->getMessage());
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
        error_log("Error fetching unread counts in users.php: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - UbuntuTrade Admin</title>
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

            <section class="admin-content">
                <h2>Manage Users</h2>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="select-all"></th>
                                <th>ID</th>
                                <th>Avatar</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Verified</th>
                                <th>Location</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><input type="checkbox" class="select-item" value="<?php echo htmlspecialchars($user['id']); ?>"></td>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="table-avatar" onerror="this.onerror=null;this.src='../images/avatars/default.jpg';">
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($user['name']); ?>
                                            <?php if ($user['is_admin']): ?>
                                                <span class="badge admin-badge">Admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><span class="status-badge status-<?php echo htmlspecialchars($user['status']); ?>"><?php echo htmlspecialchars(ucfirst($user['status'])); ?></span></td>
                                        <td><?php echo $user['IsVerified'] ? '<i class="fas fa-check-circle verified-icon"></i> Yes' : '<i class="fas fa-times-circle unverified-icon"></i> No'; ?></td>
                                        <td><?php echo htmlspecialchars($user['location'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="user_details.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-view" title="View Details"><i class="fas fa-eye"></i></a>
                                                <button class="btn btn-edit" title="Edit User"><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-delete" title="Delete User"><i class="fas fa-trash-alt"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="no-results">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <button class="page-btn prev" <?php echo ($page <= 1) ? 'disabled' : ''; ?> onclick="window.location.href='users.php?page=<?php echo $page - 1; ?>&search=<?php echo htmlspecialchars($searchQuery); ?>&status=<?php echo htmlspecialchars($filterStatus); ?>&verified=<?php echo htmlspecialchars($filterVerified); ?>'">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <button class="page-btn <?php echo ($i === $page) ? 'active' : ''; ?>" onclick="window.location.href='users.php?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($searchQuery); ?>&status=<?php echo htmlspecialchars($filterStatus); ?>&verified=<?php echo htmlspecialchars($filterVerified); ?>'">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>

                    <button class="page-btn next" <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?> onclick="window.location.href='users.php?page=<?php echo $page + 1; ?>&search=<?php echo htmlspecialchars($searchQuery); ?>&status=<?php echo htmlspecialchars($filterStatus); ?>&verified=<?php echo htmlspecialchars($filterVerified); ?>'">
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
                        alert('Please select at least one user.');
                        return;
                    }

                    if (confirm(`Are you sure you want to ${action} ${selectedItems.length} user(s)?`)) {
                        
                        console.log(`Applying action: ${action} to users:`, selectedItems);
                        alert('Bulk action functionality is a placeholder. See console for details.');
                       
                    }
                });
            }

            // <a href="user_details.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-view" title="View Details"><i class="fas fa-eye"></i></a>

            document.querySelectorAll('.btn-edit').forEach(button => {
                button.addEventListener('click', function() {
                 
                    const userId = this.closest('tr').querySelector('.select-item').value;
                    window.location.href = 'user_edit.php?id=' + userId; 
                });
            });

            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function() {
                 
                    const userId = this.closest('tr').querySelector('.select-item').value;
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
                                location.reload(); 
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