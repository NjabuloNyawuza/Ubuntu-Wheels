<?php
session_start();
require_once 'db_connection.php'; 


if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'You must be logged in to view your messages.'];
    header('Location: login.php');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$selected_conversation_id = $_GET['conversation_id'] ?? null; 


$display_message = '';
if (isset($_SESSION['message'])) {
    $msg_type = $_SESSION['message']['type'];
    $msg_text = $_SESSION['message']['text'];
    $display_message = "<div class='alert alert-$msg_type'>$msg_text</div>";
    unset($_SESSION['message']); 
}


$conversations = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            m.message_id,
            m.sender_id,
            m.receiver_id,
            m.product_id,
            m.subject,
            m.message_text,  -- CORRECTED COLUMN NAME
            m.sent_at,
            m.is_read,
            m.parent_message_id, -- ADDED THIS COLUMN
            p.ProductName AS product_title,
            p.ImageURL AS product_image,
            CASE
                WHEN m.sender_id = ? THEN u_receiver.name
                ELSE u_sender.name
            END AS correspondent_name,
            CASE
                WHEN m.sender_id = ? THEN u_receiver.avatar
                ELSE u_sender.avatar
            END AS correspondent_avatar,
            CASE
                WHEN m.sender_id = ? THEN m.receiver_id
                ELSE m.sender_id
            END AS correspondent_id,
            (SELECT COUNT(*) FROM messages
             WHERE (sender_id = ? AND receiver_id = correspondent_id AND product_id = m.product_id AND is_read = 0)
                OR (receiver_id = ? AND sender_id = correspondent_id AND product_id = m.product_id AND is_read = 0)) AS unread_count_in_thread,
            (SELECT COUNT(*) FROM messages
             WHERE (sender_id = m.sender_id AND receiver_id = m.receiver_id AND product_id = m.product_id)
                OR (sender_id = m.receiver_id AND receiver_id = m.sender_id AND product_id = m.product_id)) AS total_messages_in_thread
        FROM messages m
        JOIN (
            SELECT
                GREATEST(sender_id, receiver_id) AS user1,
                LEAST(sender_id, receiver_id) AS user2,
                product_id,
                MAX(sent_at) AS last_message_time
            FROM messages
            WHERE sender_id = ? OR receiver_id = ?
            GROUP BY user1, user2, product_id
        ) AS latest_messages
        ON (
            (m.sender_id = latest_messages.user1 AND m.receiver_id = latest_messages.user2) OR
            (m.sender_id = latest_messages.user2 AND m.receiver_id = latest_messages.user1)
        )
        AND m.product_id = latest_messages.product_id
        AND m.sent_at = latest_messages.last_message_time
        JOIN users u_sender ON m.sender_id = u_sender.id
        JOIN users u_receiver ON m.receiver_id = u_receiver.id
        LEFT JOIN Products p ON m.product_id = p.ProductID
        WHERE m.sender_id = ? OR m.receiver_id = ?
        ORDER BY m.sent_at DESC
    ");
    $stmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching conversations: " . $e->getMessage());
    $display_message = "<div class='alert alert-error'>Error loading conversations. Please try again later.</div>";
}


$conversation_messages = [];
$correspondent_info = null;
$product_info = null;

if ($selected_conversation_id) {
    try {
      
        $stmt_conv_info = $pdo->prepare("
            SELECT sender_id, receiver_id, product_id, subject
            FROM messages
            WHERE message_id = ?
            AND (sender_id = ? OR receiver_id = ?)
        ");
        $stmt_conv_info->execute([$selected_conversation_id, $current_user_id, $current_user_id]);
        $conv_info = $stmt_conv_info->fetch(PDO::FETCH_ASSOC);

        if ($conv_info) {
            $participant1 = $conv_info['sender_id'];
            $participant2 = $conv_info['receiver_id'];
            $conv_product_id = $conv_info['product_id'];
            $conv_subject = $conv_info['subject']; 

           
            $correspondent_id_for_thread = ($participant1 == $current_user_id) ? $participant2 : $participant1;

            
            $stmt_thread_messages = $pdo->prepare("
                SELECT
                    m.message_id,
                    m.sender_id,
                    m.receiver_id,
                    m.subject,
                    m.message_text, -- CORRECTED COLUMN NAME
                    m.sent_at,
                    m.is_read,
                    m.parent_message_id, -- ADDED THIS COLUMN
                    u_sender.name AS sender_name,
                    u_sender.avatar AS sender_avatar,
                    u_receiver.name AS receiver_name,
                    u_receiver.avatar AS receiver_avatar
                FROM messages m
                JOIN users u_sender ON m.sender_id = u_sender.id
                JOIN users u_receiver ON m.receiver_id = u_receiver.id
                WHERE ( (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) )
                AND m.product_id = ?
                ORDER BY m.sent_at ASC
            ");
            $stmt_thread_messages->execute([
                $current_user_id, $correspondent_id_for_thread,
                $correspondent_id_for_thread, $current_user_id,
                $conv_product_id
            ]);
            $conversation_messages = $stmt_thread_messages->fetchAll(PDO::FETCH_ASSOC);

          
            $stmt_mark_read = $pdo->prepare("
                UPDATE messages
                SET is_read = 1
                WHERE receiver_id = ?
                AND product_id = ?
                AND sender_id = ?
                AND is_read = 0
            ");
            $stmt_mark_read->execute([$current_user_id, $conv_product_id, $correspondent_id_for_thread]);

            $stmt_unread_count = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
            $stmt_unread_count->execute([$current_user_id]);
            $unreadCount = $stmt_unread_count->fetchColumn();


         
            $stmt_correspondent_info = $pdo->prepare("SELECT id, name, avatar FROM users WHERE id = ?");
            $stmt_correspondent_info->execute([$correspondent_id_for_thread]);
            $correspondent_info = $stmt_correspondent_info->fetch(PDO::FETCH_ASSOC);

            $stmt_product_info = $pdo->prepare("SELECT ProductID, ProductName, ImageURL FROM Products WHERE ProductID = ?");
            $stmt_product_info->execute([$conv_product_id]);
            $product_info = $stmt_product_info->fetch(PDO::FETCH_ASSOC);

        } else {
             $display_message = "<div class='alert alert-error'>Conversation not found or you don't have access.</div>";
        }

    } catch (PDOException $e) {
        error_log("Error fetching specific conversation: " . $e->getMessage());
        $display_message = "<div class='alert alert-error'>Error loading conversation messages. Please try again later.</div>";
    }
}


$headerUnreadCount = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $headerUnreadCount = $stmt->fetchColumn();
}

$notifCount = 0;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $notifQuery = $pdo->prepare("SELECT COUNT(*) FROM Notifications WHERE UserID = ? AND IsRead = 0");
    $notifQuery->execute([$userId]);
    $notifCount = $notifQuery->fetchColumn();
}

$cartItemCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Messages - UbuntuWheels</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="browse.css">
    <style>
       
        main.message-page-layout {
            display: flex;
            padding: 20px;
            gap: 20px;
            max-width: 1400px;
            margin: 20px auto;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            min-height: 70vh; 
        }

        .conversation-list-sidebar {
            flex: 0 0 300px; 
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow-y: auto; 
            max-height: calc(100vh - 150px); 
        }

        .conversation-list-sidebar h2 {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .conversation-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            background-color: #f0f0f0;
            transition: background-color 0.3s ease;
            text-decoration: none;
            color: #333;
            position: relative;
            cursor: pointer;
        }
        .conversation-item:hover {
            background-color: #e6e6e6;
        }
        .conversation-item.active {
            background-color: #cce7d0; 
            border: 1px solid #088178;
        }
        .conversation-item.unread {
            font-weight: bold;
            background-color: #fff3cd; 
        }
        .conversation-item .unread-indicator {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: #dc3545;
            color: #fff;
            font-size: 0.7em;
            padding: 3px 7px;
            border-radius: 12px;
            min-width: 15px;
            text-align: center;
        }
        .conversation-item-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 1px solid #ddd;
        }
        .conversation-item-details {
            flex-grow: 1;
            min-width: 0;
        }
        .conversation-item-details h4 {
            font-size: 1em;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .conversation-item-details p {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .conversation-item-details span {
            font-size: 0.75em;
            color: #888;
        }
        .no-conversations {
            text-align: center;
            color: #777;
            padding: 20px;
        }

        .message-view-area {
            flex-grow: 1; 
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
        }
        .message-view-area h2 {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .message-view-area .product-context {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .message-view-area .product-context img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
            border: 1px solid #ddd;
        }
        .message-view-area .product-context div {
            flex-grow: 1;
        }
        .message-view-area .product-context h3 {
            font-size: 1.1em;
            color: #333;
            margin-bottom: 5px;
        }
        .message-view-area .product-context a {
            color: #088178;
            text-decoration: none;
            font-size: 0.9em;
        }
        .message-view-area .product-context a:hover {
            text-decoration: underline;
        }

        .messages-display {
            flex-grow: 1;
            overflow-y: auto; 
            padding-right: 10px; 
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .message-bubble {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-end;
        }
        .message-bubble.sent {
            justify-content: flex-end;
        }
        .message-bubble.received {
            justify-content: flex-start;
        }
        .message-bubble .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 10px;
            border: 1px solid #ddd;
        }
        .message-bubble.sent .avatar {
            order: 2; 
            margin-left: 10px;
            margin-right: 0;
        }
        .message-bubble.received .avatar {
            order: 1; 
            margin-right: 10px;
            margin-left: 0;
        }
        .message-content-box {
            max-width: 70%;
            padding: 12px 18px;
            border-radius: 18px;
            line-height: 1.5;
            word-wrap: break-word;
            font-size: 0.95em;
        }
        .message-bubble.sent .message-content-box {
            background-color: #088178;
            color: #fff;
            border-bottom-right-radius: 4px; 
            order: 1;
        }
        .message-bubble.received .message-content-box {
            background-color: #f0f0f0;
            color: #333;
            border-bottom-left-radius: 4px; 
            order: 2;
        }
        .message-timestamp {
            font-size: 0.7em;
            color: #999;
            margin-top: 5px;
            text-align: right;
        }
        .message-bubble.received .message-timestamp {
            text-align: left;
        }

   
        .reply-form {
            padding-top: 20px;
        }
        .reply-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            min-height: 80px;
            resize: vertical;
            font-size: 1em;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
        .reply-form button {
            background-color: #088178;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .reply-form button:hover {
            background-color: #06675e;
        }

   
        @media (max-width: 768px) {
            main.message-page-layout {
                flex-direction: column;
                padding: 10px;
            }
            .conversation-list-sidebar {
                flex: 0 0 auto; 
                max-height: 300px; 
                margin-bottom: 20px;
            }
            .message-view-area {
                padding: 15px;
            }
            .message-bubble .message-content-box {
                max-width: 85%;
            }
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            text-align: center;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-error {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="message-page-layout">
        <section class="conversation-list-sidebar">
            <h2>Your Conversations</h2>
            <?php echo $display_message;  ?>
            <?php if (!empty($conversations)): ?>
                <?php foreach ($conversations as $conv):
                    $is_active = ($selected_conversation_id == $conv['message_id']) ? 'active' : '';
                    $is_unread = ($conv['correspondent_id'] == $conv['sender_id'] && $conv['is_read'] == 0 && $conv['receiver_id'] == $current_user_id) ? 'unread' : ''; // Only mark as unread if the LAST message was received and unread
               
                    $unread_badge = '';
                    if ($conv['unread_count_in_thread'] > 0) {
                         $unread_badge = '<span class="unread-indicator">' . htmlspecialchars($conv['unread_count_in_thread']) . '</span>';
                         $is_unread = 'unread'; 
                    }
                ?>
                    <a href="message.php?conversation_id=<?php echo htmlspecialchars($conv['message_id']); ?>"
                       class="conversation-item <?php echo $is_active; ?> <?php echo $is_unread; ?>">
                        <img src="<?php echo htmlspecialchars($conv['correspondent_avatar'] ?? 'images/default-avatar.png'); ?>" alt="Avatar" class="conversation-item-avatar">
                        <div class="conversation-item-details">
                            <h4><?php echo htmlspecialchars($conv['correspondent_name']); ?></h4>
                            <p><strong><?php echo htmlspecialchars($conv['subject']); ?></strong> - <?php echo htmlspecialchars(substr($conv['message_text'], 0, 40)); ?>...</p> <span><?php echo date('M j, Y h:i A', strtotime($conv['sent_at'])); ?></span>
                            <?php echo $unread_badge; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-conversations">You have no messages yet.</p>
            <?php endif; ?>
        </section>

        <section class="message-view-area">
            <?php if ($selected_conversation_id && !empty($conversation_messages)):
             
                $first_message = $conversation_messages[0] ?? ['subject' => 'No Subject']; 
                $correspondent_avatar = $correspondent_info['avatar'] ?? 'images/default-avatar.png';
                $correspondent_name_display = $correspondent_info['name'] ?? 'Unknown';
                $product_image_display = $product_info['ImageURL'] ?? 'images/default_car.png';
                $product_title_display = $product_info['ProductName'] ?? 'N/A';
            ?>
                <h2>Conversation with <?php echo htmlspecialchars($correspondent_name_display); ?></h2>
                <div class="product-context">
                    <img src="<?php echo htmlspecialchars($product_image_display); ?>" alt="Product Image">
                    <div>
                        <h3>Regarding: <?php echo htmlspecialchars($product_title_display); ?></h3>
                        <a href="product-details.php?id=<?php echo htmlspecialchars($product_info['ProductID']); ?>">View Product Details</a>
                    </div>
                </div>

                <div class="messages-display">
                    <?php foreach ($conversation_messages as $msg):
                        $is_sent_by_me = ($msg['sender_id'] == $current_user_id);
                        $message_class = $is_sent_by_me ? 'sent' : 'received';
                  
                        $avatar_src = ($msg['sender_id'] == $current_user_id) ? ($_SESSION['avatar'] ?? 'images/default-avatar.png') : ($msg['sender_avatar'] ?? 'images/default-avatar.png');
                    ?>
                        <div class="message-bubble <?php echo $message_class; ?>">
                            <img src="<?php echo htmlspecialchars($avatar_src); ?>" alt="Avatar" class="avatar">
                            <div class="message-content-box">
                                <?php echo nl2br(htmlspecialchars($msg['message_text'])); ?> <div class="message-timestamp"><?php echo date('M j, Y h:i A', strtotime($msg['sent_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="reply-form">
                    <h3>Reply to <?php echo htmlspecialchars($correspondent_name_display); ?></h3>
                    <form action="send_message.php" method="POST">
                        <input type="hidden" name="receiver_id" value="<?php echo htmlspecialchars($correspondent_info['id']); ?>">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_info['ProductID']); ?>">
                        <input type="hidden" name="subject" value="<?php echo htmlspecialchars($first_message['subject']); ?>">
                        <textarea name="message_content" placeholder="Type your reply here..." required></textarea>
                        <button type="submit">Send Reply</button>
                    </form>
                </div>

            <?php else: ?>
                <p class="no-conversations">Select a conversation from the left to view messages, or you have no messages in this conversation.</p>
            <?php endif; ?>
        </section>
    </main>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
    <script>
       
        document.addEventListener('DOMContentLoaded', function() {
            const messagesDisplay = document.querySelector('.messages-display');
            if (messagesDisplay) {
                messagesDisplay.scrollTop = messagesDisplay.scrollHeight;
            }
        });
    </script>
</body>
</html>