<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php'; 
session_start(); 


if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("UPDATE Users SET remember_token = NULL, remember_expiry = NULL WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error clearing remember token from DB during logout: " . $e->getMessage());
    }
}

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, [
        'path' => '/',
        'domain' => '', 
        'secure' => true, 
        'httponly' => true, 
        'samesite' => 'Lax' 
    ]);
}


$_SESSION = array(); 


session_destroy();


session_start(); 
$_SESSION['success_message'] = 'You have been successfully logged out.';


header('Location: login.php');
exit; 
?>