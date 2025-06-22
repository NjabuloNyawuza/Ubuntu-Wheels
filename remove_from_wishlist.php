<?php
session_start();
require_once 'db_connection.php'; 


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $productId = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
    $userId = $_SESSION['user_id']; 

    if ($productId > 0 && $userId > 0) {
        try {
            $pdo = connectDB(); 

        
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);

            if ($stmt->execute()) {
             
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true]);
                    exit();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Product not found in your wishlist.']);
                    exit();
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Error removing product from wishlist.']);
                exit();
            }

        } catch (PDOException $e) {
      
            error_log("Remove from Wishlist Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}
?>