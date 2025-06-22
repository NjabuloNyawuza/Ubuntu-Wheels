<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . '/../db_connection.php'; 


if (!isset($_SESSION['user_id'])) {
 
    header("Location: ../login.php"); 
    exit();
}


$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = :user_id LIMIT 1");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

   
    if (!$user || $user['is_admin'] != 1) {
        
        header("Location: ../login.php?error=unauthorized_access"); 
        exit();
    }
    
   
    
} catch (PDOException $e) {
   
    error_log("Admin check database error: " . $e->getMessage());
   
    header("Location: ../login.php?error=database_error"); 
    exit();
}
?>