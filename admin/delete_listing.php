<?php


session_start();
require_once '../db_connection.php'; 

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];


if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$productId = $input['product_id'] ?? null;

if (!$productId) {
    $response['message'] = 'Product ID not provided.';
    echo json_encode($response);
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM Products WHERE ProductID = :product_id");
    $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
    $stmt->execute();

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Listing deleted successfully.';

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error deleting listing: " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error deleting listing (general): " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
?>