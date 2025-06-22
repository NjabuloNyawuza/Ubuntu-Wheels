<?php
session_start();
require_once '../db_connection.php';
require_once 'check_admin.php';

$currentPage = 'messages';


$admin_user_id = $_SESSION['user_id'] ?? null;

if (!$admin_user_id) {
    $_SESSION['error_message'] = "Admin user not logged in.";
    header('Location: login.php'); 
    exit();
}


$conversations = []; 
$recordsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$searchQuery = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? 'all'; 

$whereClauses = [];
$params = [':admin_id' => $admin_user_id];


$whereClauses[] = "m.receiver_id = :admin_id";


if (!empty($searchQuery)) {
    $whereClauses[] = "(m.subject LIKE :searchQuery OR m.message_text LIKE :searchQuery OR s.name LIKE :searchQuery OR p.ProductName LIKE :searchQuery)";
    $params[':searchQuery'] = '%' . $searchQuery . '%';
}

if ($filterStatus === 'read') {
    $whereClauses[] = "m.is_read = 1";
} elseif ($filterStatus === 'unread') {
    $whereClauses[] = "m.is_read = 0";
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

try {
   
    $stmtFindLatestMsgIds = $pdo->prepare("
        SELECT MAX(m.message_id) AS latest_message_id
        FROM messages m
        LEFT JOIN users s ON m.sender_id = s.id
        LEFT JOIN products p ON m.product_id = p.ProductID
        WHERE m.receiver_id = :admin_id " . ($searchQuery ? " AND (m.subject LIKE :searchQuery OR m.message_text LIKE :searchQuery OR s.name LIKE :searchQuery OR p.ProductName LIKE :searchQuery)" : "") . "
        GROUP BY COALESCE(m.parent_message_id, m.message_id)
        ORDER BY MAX(m.sent_at) DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmtFindLatestMsgIds->bindParam(':admin_id', $admin_user_id, PDO::PARAM_INT);
    if ($searchQuery) {
        $stmtFindLatestMsgIds->bindValue(':searchQuery', '%' . $searchQuery . '%');
    }
    $stmtFindLatestMsgIds->bindParam(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmtFindLatestMsgIds->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmtFindLatestMsgIds->execute();
    $latestMessageIds = $stmtFindLatestMsgIds->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($latestMessageIds)) {
        $placeholders = implode(',', array_fill(0, count($latestMessageIds), '?'));
        $stmt = $pdo->prepare("
            SELECT
                m.message_id, m.sender_id, m.receiver_id, m.subject, m.message_text, m.sent_at, m.is_read, m.product_id, m.parent_message_id,
                s.name AS SenderName,
                r.name AS ReceiverName,
                p.ProductName
            FROM
                messages m
            LEFT JOIN
                users s ON m.sender_id = s.id
            LEFT JOIN
                users r ON m.receiver_id = r.id
            LEFT JOIN
                products p ON m.product_id = p.ProductID
            WHERE
                m.message_id IN ($placeholders)
            " . ($filterStatus === 'read' ? " AND m.is_read = 1" : "") . "
            " . ($filterStatus === 'unread' ? " AND m.is_read = 0" : "") . "
            ORDER BY m.sent_at DESC
        ");
        foreach ($latestMessageIds as $i => $id) {
            $stmt->bindValue(($i + 1), $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

   
    $countStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT COALESCE(m.parent_message_id, m.message_id))
        FROM messages m
        LEFT JOIN users s ON m.sender_id = s.id
        LEFT JOIN products p ON m.product_id = p.ProductID
        WHERE m.receiver_id = :admin_id " . ($searchQuery ? " AND (m.subject LIKE :searchQuery OR m.message_text LIKE :searchQuery OR s.name LIKE :searchQuery OR p.ProductName LIKE :searchQuery)" : "") . "
        " . ($filterStatus === 'read' ? " AND m.is_read = 1" : "") . "
        " . ($filterStatus === 'unread' ? " AND m.is_read = 0" : "") . "
    ");
    $countStmt->bindParam(':admin_id', $admin_user_id, PDO::PARAM_INT);
    if ($searchQuery) {
        $countStmt->bindValue(':searchQuery', '%' . $searchQuery . '%');
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);


} catch (PDOException $e) {
    error_log("Error fetching messages data: " . $e->getMessage());
    $_SESSION['error_message'] = "Failed to load messages data. Please try again.";
    $conversations = [];
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
        error_log("Database error fetching admin name/avatar for messages.php: " . $e->getMessage());
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
        error_log("Error fetching unread counts in messages.php: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Messages - UbuntuTrade Admin</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin.css">
    <style>

        .message-row.unread {
            font-weight: bold;
            background-color: #f7f7f7;
        }
        .message-subject-cell {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .message-text-preview {
            font-size: 0.9em;
            color: #666;
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dropdown-actions {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            overflow: hidden;
            right: 0;
        }

        .dropdown-content a, .dropdown-content button {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 0.95em;
            transition: background-color 0.2s;
        }

        .dropdown-content a:hover, .dropdown-content button:hover {
            background-color: #f1f1f1;
        }

        .dropdown-actions.active .dropdown-content {
            display: block;
        }

        .btn-action-dropdown {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .btn-action-dropdown:hover {
            background-color: #0056b3;
        }
        .filter-controls {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .filter-controls select {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

    </style>
</head>
<body class="admin-body">
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <a href="../index.php" target="_blank">
                    <img src="../images/logo.png" alt="UbuntuTrade Logo" class>
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
                    <h2>Manage Messages</h2>
                    <div class="section-actions">
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
                    <div class="data-table-container" id="messages-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" class="select-all"></th>
                                    <th>Message ID</th>
                                    <th>Sender</th>
                                    <th>Subject</th>
                                    <th>Product</th>
                                    <th>Preview</th>
                                    <th>Sent At</th>
                                    <th>Read Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($conversations) > 0): ?>
                                    <?php foreach ($conversations as $message):
                                    
                                        $conversationRootId = $message['parent_message_id'] ?? $message['message_id'];
                                    ?>
                                        <tr class="<?php echo $message['is_read'] ? '' : 'unread'; ?>">
                                            <td><input type="checkbox" class="select-item" value="<?php echo htmlspecialchars($message['message_id']); ?>"></td>
                                            <td><?php echo htmlspecialchars($message['message_id']); ?></td>
                                            <td>
                                                <?php if ($message['SenderName']): ?>
                                                    <a href="user_details.php?id=<?php echo htmlspecialchars($message['sender_id']); ?>" target="_blank" title="View Sender Profile">
                                                        <?php echo htmlspecialchars($message['SenderName']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    [User Deleted]
                                                <?php endif; ?>
                                            </td>
                                            <td class="message-subject-cell">
                                                <?php echo htmlspecialchars($message['subject'] ?: 'No Subject'); ?>
                                            </td>
                                            <td>
                                                <?php if ($message['ProductName']): ?>
                                                    <a href="listing_details.php?id=<?php echo htmlspecialchars($message['product_id']); ?>" target="_blank" title="View Listing Details">
                                                        <?php echo htmlspecialchars($message['ProductName']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td class="message-text-preview"><?php echo htmlspecialchars($message['message_text']); ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($message['sent_at'])); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $message['is_read'] ? 'status-reviewed' : 'status-pending'; ?>">
                                                    <?php echo $message['is_read'] ? 'Read' : 'Unread'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="dropdown-actions">
                                                    <button class="btn-action-dropdown" onclick="toggleDropdown(this)"><i class="fas fa-ellipsis-h"></i> Actions</button>
                                                    <div class="dropdown-content">
                                                        <a href="message_details.php?id=<?php echo htmlspecialchars($message['message_id']); ?>">View Conversation</a>
                                                        <?php if ($message['is_read']): ?>
                                                            <button onclick="updateMessageStatus(<?php echo htmlspecialchars($message['message_id']); ?>, 0)">Mark as Unread</button>
                                                        <?php else: ?>
                                                            <button onclick="updateMessageStatus(<?php echo htmlspecialchars($message['message_id']); ?>, 1)">Mark as Read</button>
                                                        <?php endif; ?>
                                                        <button onclick="deleteMessage(<?php echo htmlspecialchars($message['message_id']); ?>)">Delete</button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="no-results">No messages found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="scroll-btn scroll-right" id="scroll-right-btn"><i class="fas fa-chevron-right"></i></button>
                </div>

                <div class="pagination">
                    <button class="page-btn prev" <?php echo ($page <= 1) ? 'disabled' : ''; ?> onclick="window.location.href='messages.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchQuery); ?>&status=<?php echo urlencode($filterStatus); ?>'">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <button class="page-btn <?php echo ($i === $page) ? 'active' : ''; ?>" onclick="window.location.href='messages.php?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>&status=<?php echo urlencode($filterStatus); ?>'">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>

                    <button class="page-btn next" <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?> onclick="window.location.href='messages.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchQuery); ?>&status=<?php echo urlencode($filterStatus); ?>'">
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
                const currentStatus = document.getElementById('status-filter').value;
                window.location.href = `messages.php?search=${encodeURIComponent(searchQuery)}&status=${encodeURIComponent(currentStatus)}`;
            }
        }

  
        function filterMessages() {
            const status = document.getElementById('status-filter').value;
            const currentSearch = document.querySelector('.table-search input[name="search"]').value;
            window.location.href = `messages.php?search=${encodeURIComponent(currentSearch)}&status=${encodeURIComponent(status)}`;
        }


        const messagesTableContainer = document.getElementById('messages-table-container');
        const scrollLeftBtn = document.getElementById('scroll-left-btn');
        const scrollRightBtn = document.getElementById('scroll-right-btn');
        const scrollAmount = 200;

        if (messagesTableContainer && scrollLeftBtn && scrollRightBtn) {
            function toggleScrollButtons() {
                if (messagesTableContainer.scrollWidth > messagesTableContainer.clientWidth) {
                    scrollLeftBtn.style.display = 'block';
                    scrollRightBtn.style.display = 'block';
                    scrollLeftBtn.disabled = (messagesTableContainer.scrollLeft <= 0);
                    scrollRightBtn.disabled = (messagesTableContainer.scrollLeft + messagesTableContainer.clientWidth >= messagesTableContainer.scrollWidth);
                } else {
                    scrollLeftBtn.style.display = 'none';
                    scrollRightBtn.style.display = 'none';
                }
            }
            toggleScrollButtons();
            window.addEventListener('resize', toggleScrollButtons);
            messagesTableContainer.addEventListener('scroll', toggleScrollButtons);

            scrollLeftBtn.addEventListener('click', () => {
                messagesTableContainer.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });
            scrollRightBtn.addEventListener('click', () => {
                messagesTableContainer.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });
        }

  
        function toggleDropdown(button) {
            document.querySelectorAll('.dropdown-actions.active').forEach(dropdown => {
                if (dropdown !== button.closest('.dropdown-actions')) {
                    dropdown.classList.remove('active');
                }
            });
            button.closest('.dropdown-actions').classList.toggle('active');
        }


        window.onclick = function(event) {
            if (!event.target.matches('.btn-action-dropdown') && !event.target.matches('.btn-action-dropdown *')) {
                document.querySelectorAll('.dropdown-actions.active').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        }


        function updateMessageStatus(messageId, isRead) {
            const statusText = isRead === 1 ? 'read' : 'unread';
            if (confirm(`Are you sure you want to mark this message as ${statusText}?`)) {
                fetch('update_message_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `message_ids=${JSON.stringify([messageId])}&is_read=${isRead}` 
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Message status updated successfully!');
                        window.location.reload();
                    } else {
                        alert('Error updating message status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating message status.');
                });
            }
        }

    
        function deleteMessage(messageId) {
            if (confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
                fetch('delete_messages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `message_ids=${JSON.stringify([messageId])}` 
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Message deleted successfully!');
                        window.location.reload();
                    } else {
                        alert('Error deleting message: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the message.');
                });
            }
        }


        function applyBulkAction() {
            const selectedAction = document.getElementById('bulk-action-select').value;
            const selectedItems = Array.from(document.querySelectorAll('.select-item:checked')).map(cb => cb.value);

            if (!selectedAction || selectedItems.length === 0) {
                alert('Please select an action and at least one message.');
                return;
            }

            if (selectedAction === 'delete') {
                if (confirm(`Are you sure you want to delete ${selectedItems.length} selected messages? This action cannot be undone.`)) {
                    fetch('delete_messages.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `message_ids=${JSON.stringify(selectedItems)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Messages deleted successfully!');
                            window.location.reload();
                        } else {
                            alert('Error deleting messages: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting messages.');
                    });
                }
            } else if (selectedAction === 'read' || selectedAction === 'unread') {
                const isReadStatus = (selectedAction === 'read') ? 1 : 0;
                if (confirm(`Are you sure you want to mark ${selectedItems.length} selected messages as ${selectedAction}?`)) {
                    fetch('update_message_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `message_ids=${JSON.stringify(selectedItems)}&is_read=${isReadStatus}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Messages status updated successfully!');
                            window.location.reload();
                        } else {
                            alert('Error updating messages status: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating messages status.');
                    });
                }
            }
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