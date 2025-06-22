<?php
session_start();
header('Content-Type: application/json'); 

$response = ['success' => false, 'message' => ''];


if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to report a listing.';
    echo json_encode($response);
    exit();
}


$listing_id = isset($_POST['listing_id']) ? intval($_POST['listing_id']) : 0;
$reporter_user_id = intval($_SESSION['user_id']); 

if ($listing_id > 0 && $reporter_user_id > 0) {
    require_once 'db_connection.php'; 

    try {
     
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE reporter_user_id = :reporter_user_id AND listing_id = :listing_id");
        $stmtCheck->bindParam(':reporter_user_id', $reporter_user_id, PDO::PARAM_INT);
        $stmtCheck->bindParam(':listing_id', $listing_id, PDO::PARAM_INT);
        $stmtCheck->execute();
        $count = $stmtCheck->fetchColumn();

        if ($count == 0) {
           

            $stmtInsert = $pdo->prepare("INSERT INTO reports (listing_id, reporter_user_id, reported_at) VALUES (:listing_id, :reporter_user_id, NOW())");
            $stmtInsert->bindParam(':listing_id', $listing_id, PDO::PARAM_INT);
            $stmtInsert->bindParam(':reporter_user_id', $reporter_user_id, PDO::PARAM_INT);

            if ($stmtInsert->execute()) {
                $response['success'] = true;
                $response['message'] = 'Listing reported successfully. Thank you for your feedback!';
            } else {
                $response['message'] = 'Error reporting listing.';
            }
        } else {
            $response['success'] = true; 
            $response['message'] = 'You have already reported this listing.';
        }
    } catch (PDOException $e) {
      
        error_log("Report Listing Error: " . $e->getMessage()); 
        $response['message'] = 'Database error while reporting: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid listing ID provided.';
}

echo json_encode($response);
exit();
?>