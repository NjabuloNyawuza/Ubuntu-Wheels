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
$userId = $input['user_id'] ?? null;

if (!$userId) {
    $response['message'] = 'User ID not provided.';
    echo json_encode($response);
    exit();
}

try {

    if ($userId == $_SESSION['user_id']) {
        $response['message'] = 'You cannot delete your own admin account!';
        echo json_encode($response);
        exit();
    }

    $pdo->beginTransaction();

    $tablesToDeleteFrom = [
       
        'notifications' => 'UserID',
        'reports' => 'reporter_user_id',
       
    ];

    foreach ($tablesToDeleteFrom as $table => $column) {
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$column} = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }


    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'User deleted successfully.';

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error deleting user: " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error deleting user (general): " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
?>