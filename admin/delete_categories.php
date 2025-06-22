<?php
session_start();
require_once '../db_connection.php';
require_once 'check_admin.php';

header('Content-Type: application/json'); 

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = json_decode($_POST['ids'] ?? '[]'); 

    if (empty($ids) || !is_array($ids)) {
        $response['message'] = "No valid category IDs provided for deletion.";
        echo json_encode($response);
        exit();
    }

   
    $cleanIds = array_filter($ids, 'is_numeric');
    $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));

    if (empty($cleanIds)) {
        $response['message'] = "Invalid category IDs provided.";
        echo json_encode($response);
        exit();
    }

    try {
        
        $stmt = $pdo->prepare("DELETE FROM car_body_types WHERE id IN ($placeholders)");
        $stmt->execute($cleanIds);

        $deletedCount = $stmt->rowCount();

        if ($deletedCount > 0) {
            $response['success'] = true;
            $_SESSION['success_message'] = "Successfully deleted {$deletedCount} car body type(s).";
            $response['message'] = "Successfully deleted {$deletedCount} car body type(s).";
        } else {
            $response['message'] = "No categories found or deleted.";
        }

    } catch (PDOException $e) {
        error_log("Database error deleting categories: " . $e->getMessage());

        if ($e->getCode() == '23000') { 
             $response['message'] = "Cannot delete category(ies) because they are linked to existing products. Please reassign or delete linked products first.";
        } else {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    }

} else {
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
?>