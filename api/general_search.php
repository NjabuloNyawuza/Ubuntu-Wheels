<?php


require_once '../db_connection.php'; 

header('Content-Type: application/json'); 

$searchTerm = $_GET['q'] ?? ''; 

$results = [
    'users' => [],
    'listings' => [],
    'transactions' => [],
    'reports' => [],
   
];

if (empty($searchTerm)) {
    echo json_encode($results); 
    exit();
}


$searchLikeTerm = '%' . $searchTerm . '%';

try {
  
    $stmt = $pdo->prepare("
        SELECT id, name, email, status, created_at
        FROM users
        WHERE name LIKE :searchTerm OR email LIKE :searchTerm
        ORDER BY created_at DESC LIMIT 5
    ");

    $stmt->execute([':searchTerm' => $searchLikeTerm]);
    $results['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $stmt = $pdo->prepare("
        SELECT ProductID AS id, ProductName AS title, Price, status, DateListed AS date
        FROM Products
        WHERE ProductName LIKE :searchTerm OR Description LIKE :searchTerm
        ORDER BY DateListed DESC LIMIT 5
    ");

    $stmt->execute([':searchTerm' => $searchLikeTerm]);
    $results['listings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $stmt = $pdo->prepare("
        SELECT
            t.transaction_id AS id,
            t.amount,
            t.status,
            t.transaction_date AS date,
            p.ProductName AS item_name,
            ub.name AS buyer_name,
            us.name AS seller_name
        FROM transactions t
        LEFT JOIN Products p ON t.listing_id = p.ProductID
        LEFT JOIN users ub ON t.buyer_id = ub.id
        LEFT JOIN users us ON t.seller_id = us.id
        WHERE t.transaction_id LIKE :searchTerm
        OR p.ProductName LIKE :searchTerm
        OR ub.name LIKE :searchTerm
        OR us.name LIKE :searchTerm
        ORDER BY t.transaction_date DESC LIMIT 5
    ");

    $stmt->execute([':searchTerm' => $searchLikeTerm]);
    $results['transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT
            r.report_id AS id,
            r.report_reason AS reason,
            r.status,
            r.reported_at AS date,
            p.ProductName AS reported_item_name,
            u.name AS reporter_name
        FROM reports r
        LEFT JOIN Products p ON r.listing_id = p.ProductID
        LEFT JOIN users u ON r.reporter_user_id = u.id
        WHERE r.report_reason LIKE :searchTerm
        OR p.ProductName LIKE :searchTerm
        OR u.name LIKE :searchTerm
        ORDER BY r.reported_at DESC LIMIT 5
    ");

    $stmt->execute([':searchTerm' => $searchLikeTerm]);
    $results['reports'] = $stmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode($results);

} catch (PDOException $e) {
    error_log("General Search API Error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to perform search.', 'details' => $e->getMessage()]);
}
?>