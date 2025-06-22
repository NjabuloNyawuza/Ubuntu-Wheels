<?php
session_start();
require_once '../db_connection.php';
require_once 'check_admin.php';

$currentPage = 'reports';


$reports = [];
$recordsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$searchQuery = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? ''; 

$whereClauses = [];
$params = [];

if (!empty($searchQuery)) {
  
    $whereClauses[] = "(r.report_reason LIKE :searchQuery OR p.ProductName LIKE :searchQuery OR u.name LIKE :searchQuery)";
    $params[':searchQuery'] = '%' . $searchQuery . '%';
}

if (!empty($filterStatus) && in_array($filterStatus, ['pending', 'reviewed', 'resolved'])) {
    $whereClauses[] = "r.status = :filterStatus";
    $params[':filterStatus'] = $filterStatus;
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

try {

    $countStmt = $pdo->prepare("
        SELECT COUNT(r.report_id)
        FROM reports r
        LEFT JOIN products p ON r.listing_id = p.ProductID
        LEFT JOIN users u ON r.reporter_user_id = u.id
        {$whereSql}
    ");
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);


    $stmt = $pdo->prepare("
        SELECT
            r.report_id, r.listing_id, r.reporter_user_id, r.report_reason, r.status, r.reported_at,
            p.ProductName, p.Price AS ListingPrice, p.ImageURL,
            u.name AS ReporterUsername, u.email AS ReporterEmail
        FROM
            reports r
        LEFT JOIN
            products p ON r.listing_id = p.ProductID
        LEFT JOIN
            users u ON r.reporter_user_id = u.id
        {$whereSql}
        ORDER BY
            r.reported_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching reports data: " . $e->getMessage());
    $_SESSION['error_message'] = "Failed to load reports data. Please try again.";
    $reports = [];
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
        error_log("Database error fetching admin name/avatar for reports.php: " . $e->getMessage());
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
        error_log("Error fetching unread counts in reports.php: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - UbuntuTrade Admin</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin.css">
    <style>
 
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            color: #fff;
            white-space: nowrap; 
        }
        .status-badge.status-pending { background-color: #ffc107; color: #333; }
        .status-badge.status-reviewed { background-color: #17a2b8; }
        .status-badge.status-resolved { background-color: #28a745; }

        .report-reason-cell {
            max-width: 250px; 
            white-space: normal;
            word-wrap: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
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
                    <h2>Manage Reports</h2>
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
                    <div class="data-table-container" id="reports-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" class="select-all"></th>
                                    <th>Report ID</th>
                                    <th>Reported Listing</th>
                                    <th>Reporter</th>
                                    <th class="report-reason-cell">Reason</th>
                                    <th>Status</th>
                                    <th>Reported At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($reports) > 0): ?>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td><input type="checkbox" class="select-item" value="<?php echo htmlspecialchars($report['report_id']); ?>"></td>
                                            <td><?php echo htmlspecialchars($report['report_id']); ?></td>
                                            <td>
                                                <?php if ($report['ProductName']): ?>
                                                    <a href="listing_details.php?id=<?php echo htmlspecialchars($report['listing_id']); ?>" target="_blank" title="View Listing Details">
                                                        <?php echo htmlspecialchars($report['ProductName']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    [Listing Deleted]
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($report['ReporterUsername']): ?>
                                                    <a href="user_details.php?id=<?php echo htmlspecialchars($report['reporter_user_id']); ?>" target="_blank" title="View Reporter Details">
                                                        <?php echo htmlspecialchars($report['ReporterUsername']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    [User Deleted]
                                                <?php endif; ?>
                                            </td>
                                            <td class="report-reason-cell"><?php echo htmlspecialchars($report['report_reason']); ?></td>
                                            <td><span class="status-badge status-<?php echo htmlspecialchars($report['status']); ?>"><?php echo htmlspecialchars(ucfirst($report['status'])); ?></span></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($report['reported_at'])); ?></td>
                                            <td>
                                                <div class="dropdown-actions">
                                                    <button class="btn-action-dropdown" onclick="toggleDropdown(this)"><i class="fas fa-ellipsis-h"></i> Actions</button>
                                                    <div class="dropdown-content">
                                                        <a href="report_details.php?id=<?php echo htmlspecialchars($report['report_id']); ?>">View Details</a>
                                                        <?php if ($report['status'] !== 'resolved'): ?>
                                                            <button onclick="updateReportStatus(<?php echo htmlspecialchars($report['report_id']); ?>, 'reviewed')">Mark as Reviewed</button>
                                                        <?php endif; ?>
                                                        <?php if ($report['status'] !== 'resolved'): ?>
                                                            <button onclick="updateReportStatus(<?php echo htmlspecialchars($report['report_id']); ?>, 'resolved')">Mark as Resolved</button>
                                                        <?php endif; ?>
                                                        <?php if ($report['status'] === 'resolved'): ?>
                                                            <button onclick="updateReportStatus(<?php echo htmlspecialchars($report['report_id']); ?>, 'pending')">Mark as Pending</button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="no-results">No reports found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="scroll-btn scroll-right" id="scroll-right-btn"><i class="fas fa-chevron-right"></i></button>
                </div>

                <div class="pagination">
                    <button class="page-btn prev" <?php echo ($page <= 1) ? 'disabled' : ''; ?> onclick="window.location.href='reports.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchQuery); ?>&status=<?php echo urlencode($filterStatus); ?>'">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <button class="page-btn <?php echo ($i === $page) ? 'active' : ''; ?>" onclick="window.location.href='reports.php?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>&status=<?php echo urlencode($filterStatus); ?>'">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>

                    <button class="page-btn next" <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?> onclick="window.location.href='reports.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchQuery); ?>&status=<?php echo urlencode($filterStatus); ?>'">
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
                window.location.href = `reports.php?search=${encodeURIComponent(searchQuery)}&status=${encodeURIComponent(currentStatus)}`;
            }
        }

    
        function filterReports() {
            const status = document.getElementById('status-filter').value;
            const currentSearch = document.querySelector('.table-search input[name="search"]').value;
            window.location.href = `reports.php?search=${encodeURIComponent(currentSearch)}&status=${encodeURIComponent(status)}`;
        }

 
        const reportsTableContainer = document.getElementById('reports-table-container');
        const scrollLeftBtn = document.getElementById('scroll-left-btn');
        const scrollRightBtn = document.getElementById('scroll-right-btn');
        const scrollAmount = 200;

        if (reportsTableContainer && scrollLeftBtn && scrollRightBtn) {
            function toggleScrollButtons() {
                if (reportsTableContainer.scrollWidth > reportsTableContainer.clientWidth) {
                    scrollLeftBtn.style.display = 'block';
                    scrollRightBtn.style.display = 'block';
                    scrollLeftBtn.disabled = (reportsTableContainer.scrollLeft <= 0);
                    scrollRightBtn.disabled = (reportsTableContainer.scrollLeft + reportsTableContainer.clientWidth >= reportsTableContainer.scrollWidth);
                } else {
                    scrollLeftBtn.style.display = 'none';
                    scrollRightBtn.style.display = 'none';
                }
            }
            toggleScrollButtons();
            window.addEventListener('resize', toggleScrollButtons);
            reportsTableContainer.addEventListener('scroll', toggleScrollButtons);

            scrollLeftBtn.addEventListener('click', () => {
                reportsTableContainer.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });
            scrollRightBtn.addEventListener('click', () => {
                reportsTableContainer.scrollBy({ left: scrollAmount, behavior: 'smooth' });
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

       
        function updateReportStatus(reportId, newStatus) {
            if (confirm(`Are you sure you want to mark this report as ${newStatus}?`)) {
                fetch('update_report_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `report_id=${reportId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Report status updated successfully!');
                        window.location.reload(); 
                    } else {
                        alert('Error updating report status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating report status.');
                });
            }
        }

      
        function applyBulkAction() {
            const selectedAction = document.getElementById('bulk-action-select').value;
            const selectedItems = Array.from(document.querySelectorAll('.select-item:checked')).map(cb => cb.value);

            if (selectedAction && selectedItems.length > 0) {
                if (confirm(`Are you sure you want to mark ${selectedItems.length} selected reports as ${selectedAction}?`)) {
                    fetch('update_report_status.php', { 
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `report_ids=${JSON.stringify(selectedItems)}&status=${selectedAction}` 
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Reports status updated successfully!');
                            window.location.reload();
                        } else {
                            alert('Error updating reports status: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating reports status.');
                    });
                }
            } else {
                alert('Please select an action and at least one report.');
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