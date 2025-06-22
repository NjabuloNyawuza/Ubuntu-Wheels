<?php
session_start();
require_once 'db_connection.php'; 


if (!isset($_SESSION['user_id'])) {
    
    $_SESSION['message'] = ['type' => 'error', 'text' => 'You must be logged in to send messages.'];
    header('Location: login.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'] ?? null;
    $product_id = $_POST['product_id'] ?? null;
    $subject = trim($_POST['subject'] ?? '');
 
    $message_text = trim($_POST['message_content'] ?? ''); 

 
    if (empty($receiver_id) || empty($product_id) || empty($subject) || empty($message_text)) { 
        $_SESSION['message'] = ['type' => 'error', 'text' => 'All fields are required to send a message.'];
        header('Location: message.php?seller=' . htmlspecialchars($receiver_id) . '&product=' . htmlspecialchars($product_id));
        exit();
    }

    if (!is_numeric($receiver_id) || !is_numeric($product_id)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid sender or product ID.'];
        header('Location: index.php'); 
        exit();
    }

   
    if ($sender_id == $receiver_id) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'You cannot send a message to yourself.'];
        header('Location: product-details.php?id=' . htmlspecialchars($product_id)); 
        exit();
    }

    try {
       
        $pdo->beginTransaction();

        
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, product_id, subject, message_text, sent_at, is_read) VALUES (?, ?, ?, ?, ?, NOW(), 0)");
        $stmt->execute([$sender_id, $receiver_id, $product_id, $subject, $message_text]); 

      
        $message_id = $pdo->lastInsertId();

       
        $notification_message = "You have a new message from " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'a user') . " regarding product ID " . htmlspecialchars($product_id) . ".";
        $notification_link = "messages.php?conversation_id=" . $message_id; 

        $stmtNotif = $pdo->prepare("INSERT INTO Notifications (UserID, Message, Type, IsRead, CreatedAt, Link) VALUES (?, ?, 'message', 0, NOW(), ?)");
        $stmtNotif->execute([$receiver_id, $notification_message, $notification_link]);

    
        $pdo->commit();

        $_SESSION['message'] = ['type' => 'success', 'text' => 'Your message has been sent successfully!'];
  
        header('Location: product-details.php?id=' . htmlspecialchars($product_id) . '&message_sent=true');
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack(); 
        error_log("Error sending message: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to send message. Please try again.'];
        header('Location: message.php?seller=' . htmlspecialchars($receiver_id) . '&product=' . htmlspecialchars($product_id)); 
        exit();
    }
} else {
  
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid request.'];
    header('Location: index.php');
    exit();
}
?>