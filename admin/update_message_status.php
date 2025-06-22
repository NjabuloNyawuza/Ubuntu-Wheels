<?php
session_start();
require_once '../db_connection.php';
require_once 'check_admin.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_ids_json = $_POST['message_ids'] ?? null;
    $is_read = $_POST['is_read'] ?? null; 

    $ids_to_update = [];
    if ($message_ids_json) {
        $decoded_ids = json_decode($message_ids_json, true);
        if (is_array($decoded_ids)) {
            $ids_to_update = array_filter($decoded_ids, 'is_numeric');
        }
    }

    if (empty($ids_to_update)) {
        $response['message'] = "No valid message ID(s) provided for update.";
        echo json_encode($response);
        exit();
    }

    if (!is_numeric($is_read) || ($is_read != 0 && $is_read != 1)) {
        $response['message'] = "Invalid read status provided.";
        echo json_encode($response);
        exit();
    }

    try {
        $placeholders = implode(',', array_fill(0, count($ids_to_update), '?'));
        $stmt = $pdo->prepare("UPDATE messages SET is_read = :is_read WHERE message_id IN ($placeholders)");
        $stmt->bindParam(':is_read', $is_read, PDO::PARAM_INT);

        foreach ($ids_to_update as $i => $id) {
            $stmt->bindValue(($i + 1), $id, PDO::PARAM_INT);
        }

        if ($stmt->execute()) {
            $updatedCount = $stmt->rowCount();
            $response['success'] = true;
            $response['message'] = "Successfully updated status for {$updatedCount} message(s).";
          
        } else {
            $response['message'] = "Failed to execute update query.";
        }
    } catch (PDOException $e) {
        error_log("Database error updating message status: " . $e->getMessage());
        $response['message'] = "Database error: " . $e->getMessage();
    }

} else {
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
?>