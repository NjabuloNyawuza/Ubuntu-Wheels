<?php
session_start();
require_once 'db_connection.php'; 

 if (!isset($_SESSION['user_id'])) {
     echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
 }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $productId = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
  
    $userId = 3; 

    if ($productId > 0 && $userId > 0) {
        try {
          

         
            $stmtCheck = $pdo->prepare("SELECT * FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
            $stmtCheck->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtCheck->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmtCheck->execute();

            if ($stmtCheck->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Product is already in your wishlist.']);
                exit();
            }

            $stmtInsert = $pdo->prepare("INSERT INTO wishlist (user_id, product_id, added_at) VALUES (:user_id, :product_id, NOW())");
            $stmtInsert->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtInsert->bindParam(':product_id', $productId, PDO::PARAM_INT);

            if ($stmtInsert->execute()) {
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding product to wishlist.']);
                exit();
            }

        } catch (PDOException $e) {
        
            error_log("Wishlist Error: " . $e->getMessage());
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