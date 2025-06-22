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

$message_id = $_GET['id'] ?? null;
$conversation = [];
$error_message = '';
$current_subject = 'Conversation'; 

if (!$message_id || !is_numeric($message_id)) {
    $error_message = "Invalid message ID provided.";
} else {
    try {
       
        $stmt = $pdo->prepare("
            WITH RECURSIVE MessagePath AS (
                SELECT message_id, parent_message_id, 0 AS depth
                FROM messages
                WHERE message_id = :start_message_id
                UNION ALL
                SELECT m.message_id, m.parent_message_id, mp.depth + 1
                FROM messages m
                JOIN MessagePath mp ON m.message_id = mp.parent_message_id
            )
            SELECT message_id
            FROM MessagePath
            ORDER BY depth DESC
            LIMIT 1;
        ");
        $stmt->bindParam(':start_message_id', $message_id, PDO::PARAM_INT);
        $stmt->execute();
        $root_message_id = $stmt->fetchColumn();

     
        if (!$root_message_id) {
            $root_message_id = $message_id; 
        }

    
        $stmt = $pdo->prepare("
            SELECT
                m.message_id, m.sender_id, m.receiver_id, m.subject, m.message_text, m.sent_at, m.is_read, m.product_id, m.parent_message_id,
                s.name AS SenderName, s.profile_picture AS SenderAvatar,
                r.name AS ReceiverName, r.profile_picture AS ReceiverAvatar,
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
                (m.message_id = :root_id OR m.parent_message_id = :root_id_parent_match)
                AND (m.sender_id = :admin_id OR m.receiver_id = :admin_id OR s.id = :admin_id OR r.id = :admin_id) -- ensure admin is part of the conversation
            ORDER BY m.sent_at ASC
        ");
        $stmt->bindParam(':root_id', $root_message_id, PDO::PARAM_INT);
        $stmt->bindParam(':root_id_parent_match', $root_message_id, PDO::PARAM_INT); 
        $stmt->bindParam(':admin_id', $admin_user_id, PDO::PARAM_INT);
        $stmt->execute();
        $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($conversation)) {
            $error_message = "Conversation not found or you don't have access.";
        } else {
            $current_subject = $conversation[0]['subject'] ?: 'Conversation';

        
            $unread_message_ids = [];
            foreach ($conversation as $msg) {
                if ($msg['receiver_id'] == $admin_user_id && $msg['is_read'] == 0) {
                    $unread_message_ids[] = $msg['message_id'];
                }
            }

            if (!empty($unread_message_ids)) {
                $placeholders = implode(',', array_fill(0, count($unread_message_ids), '?'));
                $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE message_id IN ($placeholders)");
                foreach ($unread_message_ids as $i => $id) {
                    $stmt->bindValue(($i + 1), $id, PDO::PARAM_INT);
                }
                $stmt->execute();
            }
        }

    } catch (PDOException $e) {
        error_log("Error fetching conversation details: " . $e->getMessage());
        $error_message = "Failed to load conversation details due to a database error.";
    }
}


$adminName = "Admin User";
$adminAvatar = "../images/avatars/default.jpg";
if (isset($_SESSION['user_id'])) {
    $current_admin_id_for_avatar = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT name, profile_picture FROM users WHERE id = :admin_id AND is_admin = 1");
        $stmt->bindParam(':admin_id', $current_admin_id_for_avatar, PDO::PARAM_INT);
        $stmt->execute();
        $adminInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($adminInfo) {
            $adminName = $adminInfo['name'] ?? "Admin User";
            $adminAvatar = !empty($adminInfo['profile_picture']) ? $adminInfo['profile_picture'] : "../images/avatars/default.jpg";
        }
    } catch (PDOException $e) {
        error_log("Database error fetching admin name/avatar for message_details.php: " . $e->getMessage());
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
        error_log("Error fetching unread counts in message_details.php: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Details - UbuntuTrade Admin</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin.css">
    <style>
  
        .conversation-container {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
        }
        .message-thread {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 15px; 
            margin-bottom: 20px;
        }
        .message-bubble {
            display: flex;
            margin-bottom: 20px;
            align-items: flex-start;
        }
        .message-bubble.admin {
            justify-content: flex-end;
        }
        .message-bubble.other {
            justify-content: flex-start;
        }
        .message-bubble .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 1px solid #eee;
        }
        .message-bubble.admin .avatar {
            margin-left: 10px;
            margin-right: 0;
            order: 2; 
        }
        .message-content {
            background-color: #f0f2f5;
            padding: 12px 18px;
            border-radius: 20px;
            max-width: 70%;
            word-wrap: break-word;
            font-size: 0.95em;
        }
        .message-bubble.admin .message-content {
            background-color: #007bff;
            color: white;
            border-bottom-right-radius: 5px; 
            border-bottom-left-radius: 20px;
        }
        .message-bubble.other .message-content {
            background-color: #e4e6eb;
            color: #333;
            border-bottom-left-radius: 5px; 
            border-bottom-right-radius: 20px;
        }
        .message-info {
            font-size: 0.75em;
            color: #888;
            margin-top: 5px;
        }
        .message-bubble.admin .message-info {
            text-align: right;
            margin-right: 10px; 
        }
        .message-bubble.other .message-info {
            text-align: left;
            margin-left: 10px;
        }
        .message-header-info {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .message-header-info h3 {
            margin-top: 0;
            margin-bottom: 5px;
            color: #333;
        }
        .message-header-info p {
            margin: 0;
            color: #555;
            font-size: 0.9em;
        }

        .reply-form {
            padding: 20px;
            border-top: 1px solid #eee;
            background-color: #f9f9f9;
            border-radius: 0 0 8px 8px;
        }
        .reply-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 80px;
            resize: vertical;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
        .reply-form button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            float: right; 
        }
        .reply-form button:hover {
            background-color: #218838;
        }
        .message-product-info {
            font-size: 0.85em;
            color: #555;
            margin-bottom: 15px;
        }
        .message-product-info a {
            color: #007bff;
            text-decoration: none;
        }
        .message-product-info a:hover {
            text-decoration: underline;
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
                    <h2><?php echo htmlspecialchars($current_subject); ?></h2>
                    <div class="section-actions">
                        <a href="messages.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Messages</a>
                    </div>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert error-alert"><?php echo htmlspecialchars($error_message); ?></div>
                <?php else: ?>
                    <div class="conversation-container">
                        <div class="message-header-info">
                            <h3>Subject: <?php echo htmlspecialchars($current_subject); ?></h3>
                            <?php if (!empty($conversation) && $conversation[0]['ProductName']): ?>
                                <p class="message-product-info">Regarding Listing:
                                    <a href="listing_details.php?id=<?php echo htmlspecialchars($conversation[0]['product_id']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($conversation[0]['ProductName']); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="message-thread">
                            <?php foreach ($conversation as $msg):
                                $is_admin_sender = ($msg['sender_id'] == $admin_user_id);
                                $message_class = $is_admin_sender ? 'admin' : 'other';
                                $sender_name = $is_admin_sender ? 'You' : htmlspecialchars($msg['SenderName'] ?: 'Unknown User');
                                $sender_avatar = $is_admin_sender ? htmlspecialchars($adminAvatar) : htmlspecialchars($msg['SenderAvatar'] ?: '../images/avatars/default.jpg');
                            ?>
                                <div class="message-bubble <?php echo $message_class; ?>">
                                    <img src="<?php echo $sender_avatar; ?>" alt="<?php echo $sender_name; ?> Avatar" class="avatar">
                                    <div class="message-content">
                                        <p><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></p>
                                        <div class="message-info">
                                            <span><?php echo $sender_name; ?></span> &bullet; <span><?php echo date('Y-m-d H:i', strtotime($msg['sent_at'])); ?></span>
                                            <?php if ($is_admin_sender && $msg['is_read'] !== null): ?>
                                                &bullet; <i class="fas fa-eye<?php echo $msg['is_read'] ? '' : '-slash'; ?>" title="<?php echo $msg['is_read'] ? 'Read by recipient' : 'Unread by recipient'; ?>"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="reply-form">
                            <form action="send_admin_message.php" method="POST">
                                <input type="hidden" name="parent_message_id" value="<?php echo htmlspecialchars($conversation[array_key_last($conversation)]['message_id']); ?>">
                                <input type="hidden" name="recipient_id" value="<?php
                                    
                                    $last_message = $conversation[array_key_last($conversation)];
                                    echo htmlspecialchars($last_message['sender_id'] == $admin_user_id ? $last_message['receiver_id'] : $last_message['sender_id']);
                                ?>">
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($conversation[0]['product_id'] ?? ''); ?>">
                                <input type="hidden" name="subject" value="<?php echo htmlspecialchars($conversation[0]['subject'] ?? 'RE: Conversation'); ?>">

                                <textarea name="message_text" placeholder="Type your reply here..." required></textarea>
                                <button type="submit"><i class="fas fa-paper-plane"></i> Send Reply</button>
                                <div style="clear:both;"></div> </form>
                        </div>
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
    </script>
</body>
</html>