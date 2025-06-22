<?php
session_start();
require_once '../db_connection.php'; 
require_once 'check_admin.php'; 


$currentPage = 'transactions';

$transaction = null;
$error_message = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $transaction_id = (int)$_GET['id'];

    try {
     
        $stmt = $pdo->prepare("
            SELECT
                t.transaction_id, t.amount, t.transaction_date, t.status,
                p.ProductID, p.ProductName, p.Description, p.Price AS ListingPrice, p.Condition, p.ImageURL,
                p.Make, p.Model, p.Year, p.Mileage, p.FuelType, p.Transmission, p.Location AS ProductLocation,
                bu.id AS BuyerID, bu.name AS BuyerUsername, bu.email AS BuyerEmail, bu.phone_number AS BuyerPhone, bu.location AS BuyerLocation,
                su.id AS SellerID, su.name AS SellerUsername, su.email AS SellerEmail, su.phone_number AS SellerPhone, su.location AS SellerLocation
            FROM
                transactions t
            JOIN
                products p ON t.listing_id = p.ProductID
            JOIN
                users bu ON t.buyer_id = bu.id
            JOIN
                users su ON t.seller_id = su.id
            WHERE
                t.transaction_id = :transaction_id
        ");
        $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
        $stmt->execute();
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            $error_message = "Transaction not found.";
        }

    } catch (PDOException $e) {
        error_log("Error fetching transaction details: " . $e->getMessage());
        $error_message = "Failed to load transaction details due to a database error.";
    }
} else {
    $error_message = "Invalid transaction ID provided.";
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
        error_log("Database error fetching admin name/avatar for transaction_details.php: " . $e->getMessage());
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
        error_log("Error fetching unread counts in transaction_details.php: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details - UbuntuTrade Admin</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="../style.css"> <link rel="stylesheet" href="admin.css"> <style>
      
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .details-grid h3 {
            grid-column: 1 / -1; 
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-top: 20px;
            margin-bottom: 15px;
            color: #333;
        }
        .details-item {
            padding: 5px 0;
        }
        .details-item strong {
            display: inline-block;
            width: 120px; 
            color: #555;
        }
        .details-item span {
            color: #333;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            color: #fff;
        }
        .status-badge.status-completed { background-color: #28a745; }
        .status-badge.status-pending { background-color: #ffc107; color: #333; }
        .status-badge.status-cancelled { background-color: #dc3545; }

        .product-image-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .product-image-container img {
            max-width: 300px;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .action-buttons-details {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: right;
        }
        .action-buttons-details .btn {
            margin-left: 10px;
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
                    <h2>Transaction Details (ID: <?php echo htmlspecialchars($transaction_id); ?>)</h2>
                    <div class="section-actions">
                        <a href="transactions.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Transactions</a>
                    </div>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert error-alert"><?php echo htmlspecialchars($error_message); ?></div>
                <?php elseif ($transaction): ?>
                    <div class="product-image-container">
                        <img src="<?php echo htmlspecialchars($transaction['ImageURL']); ?>" alt="<?php echo htmlspecialchars($transaction['ProductName']); ?>">
                    </div>

                    <div class="details-grid">
                        <h3>Transaction Information</h3>
                        <div class="details-item"><strong>Transaction ID:</strong> <span><?php echo htmlspecialchars($transaction['transaction_id']); ?></span></div>
                        <div class="details-item"><strong>Amount:</strong> <span>R <?php echo number_format($transaction['amount'], 2); ?></span></div>
                        <div class="details-item"><strong>Transaction Date:</strong> <span><?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></span></div>
                        <div class="details-item"><strong>Status:</strong> <span class="status-badge status-<?php echo htmlspecialchars($transaction['status']); ?>"><?php echo htmlspecialchars(ucfirst($transaction['status'])); ?></span></div>

                        <h3>Product Details</h3>
                        <div class="details-item"><strong>Product Name:</strong> <span><?php echo htmlspecialchars($transaction['ProductName']); ?></span></div>
                        <div class="details-item"><strong>Product ID:</strong> <span><?php echo htmlspecialchars($transaction['ProductID']); ?></span></div>
                        <div class="details-item"><strong>Listing Price:</strong> <span>R <?php echo number_format($transaction['ListingPrice'], 2); ?></span></div>
                        <div class="details-item"><strong>Condition:</strong> <span><?php echo htmlspecialchars($transaction['Condition']); ?></span></div>
                        <div class="details-item"><strong>Make:</strong> <span><?php echo htmlspecialchars($transaction['Make']); ?></span></div>
                        <div class="details-item"><strong>Model:</strong> <span><?php echo htmlspecialchars($transaction['Model']); ?></span></div>
                        <div class="details-item"><strong>Year:</strong> <span><?php echo htmlspecialchars($transaction['Year']); ?></span></div>
                        <div class="details-item"><strong>Mileage:</strong> <span><?php echo number_format($transaction['Mileage']); ?> km</span></div>
                        <div class="details-item"><strong>Fuel Type:</strong> <span><?php echo htmlspecialchars($transaction['FuelType']); ?></span></div>
                        <div class="details-item"><strong>Transmission:</strong> <span><?php echo htmlspecialchars($transaction['Transmission']); ?></span></div>
                        <div class="details-item"><strong>Product Location:</strong> <span><?php echo htmlspecialchars($transaction['ProductLocation']); ?></span></div>
                        <div class="details-item full-width"><strong>Description:</strong> <span><?php echo nl2br(htmlspecialchars($transaction['Description'])); ?></span></div>

                        <h3>Buyer Information</h3>
                        <div class="details-item"><strong>Buyer Username:</strong> <span><?php echo htmlspecialchars($transaction['BuyerUsername']); ?></span></div>
                        <div class="details-item"><strong>Buyer ID:</strong> <span><?php echo htmlspecialchars($transaction['BuyerID']); ?></span></div>
                        <div class="details-item"><strong>Buyer Email:</strong> <span><?php echo htmlspecialchars($transaction['BuyerEmail']); ?></span></div>
                        <div class="details-item"><strong>Buyer Phone:</strong> <span><?php echo htmlspecialchars($transaction['BuyerPhone'] ?: 'N/A'); ?></span></div>
                        <div class="details-item"><strong>Buyer Location:</strong> <span><?php echo htmlspecialchars($transaction['BuyerLocation'] ?: 'N/A'); ?></span></div>

                        <h3>Seller Information</h3>
                        <div class="details-item"><strong>Seller Username:</strong> <span><?php echo htmlspecialchars($transaction['SellerUsername']); ?></span></div>
                        <div class="details-item"><strong>Seller ID:</strong> <span><?php echo htmlspecialchars($transaction['SellerID']); ?></span></div>
                        <div class="details-item"><strong>Seller Email:</strong> <span><?php echo htmlspecialchars($transaction['SellerEmail']); ?></span></div>
                        <div class="details-item"><strong>Seller Phone:</strong> <span><?php echo htmlspecialchars($transaction['SellerPhone'] ?: 'N/A'); ?></span></div>
                        <div class="details-item"><strong>Seller Location:</strong> <span><?php echo htmlspecialchars($transaction['SellerLocation'] ?: 'N/A'); ?></span></div>
                    </div>

                    <div class="action-buttons-details">
                        <?php if ($transaction['status'] == 'pending'): ?>
                            <button class="btn btn-success" onclick="updateTransactionStatus(<?php echo $transaction['transaction_id']; ?>, 'completed')"><i class="fas fa-check-circle"></i> Mark as Completed</button>
                            <button class="btn btn-danger" onclick="updateTransactionStatus(<?php echo $transaction['transaction_id']; ?>, 'cancelled')"><i class="fas fa-times-circle"></i> Mark as Cancelled</button>
                        <?php endif; ?>
                        <button class="btn btn-info" onclick="printTransactionDetails()"><i class="fas fa-print"></i> Print</button>
                    </div>

                <?php endif; ?>
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

     
        function updateTransactionStatus(transactionId, newStatus) {
            if (confirm(`Are you sure you want to mark this transaction as ${newStatus}?`)) {
                fetch('update_transaction_status.php', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `transaction_id=${transactionId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Transaction status updated successfully!');
                        window.location.reload(); 
                    } else {
                        alert('Error updating transaction status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating transaction status.');
                });
            }
        }

        function printTransactionDetails() {
            window.print();
        }
    </script>
</body>
</html>