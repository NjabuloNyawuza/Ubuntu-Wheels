<?php
session_start();
require_once '../db_connection.php';
require_once 'check_admin.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_ids_json = $_POST['message_ids'] ?? null;

    $ids_to_delete = [];
    if ($message_ids_json) {
        $decoded_ids = json_decode($message_ids_json, true);
        if (is_array($decoded_ids)) {
            $ids_to_delete = array_filter($decoded_ids, 'is_numeric');
        }
    }

    if (empty($ids_to_delete)) {
        $response['message'] = "No valid message ID(s) provided for deletion.";
        echo json_encode($response);
        exit();
    }

    try {
        $pdo->beginTransaction();

        $all_ids_to_affect = $ids_to_delete;
        $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));

        $stmt = $pdo->prepare("SELECT message_id FROM messages WHERE parent_message_id IN ($placeholders)");
        foreach ($ids_to_delete as $i => $id) {
            $stmt->bindValue(($i + 1), $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $child_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $all_ids_to_affect = array_unique(array_merge($all_ids_to_affect, $child_ids));

        if (empty($all_ids_to_affect)) {
            $pdo->rollBack();
            $response['message'] = "No messages found to delete after processing child messages.";
            echo json_encode($response);
            exit();
        }

        $delete_placeholders = implode(',', array_fill(0, count($all_ids_to_affect), '?'));

        $stmt = $pdo->prepare("DELETE FROM messages WHERE message_id IN ($delete_placeholders)");
        foreach ($all_ids_to_affect as $i => $id) {
            $stmt->bindValue(($i + 1), $id, PDO::PARAM_INT);
        }

        if ($stmt->execute()) {
            $pdo->commit();
            $deletedCount = $stmt->rowCount();
            $response['success'] = true;
            $response['message'] = "Successfully deleted {$deletedCount} message(s).";
        } else {
            $pdo->rollBack();
            $response['message'] = "Failed to execute delete query.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Database error deleting messages: " . $e->getMessage());
        $response['message'] = "Database error: " . $e->getMessage();
    }

} else {
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
?>