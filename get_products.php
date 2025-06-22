<?php



$host = 'localhost'; 
$dbname = 'ubuntutrade_database'; 
$username = 'root'; 
$password = ''; 


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {

    http_response_code(500);
    echo json_encode(['error' => 'Failed to connect to the database: ' . $e->getMessage()]);
    exit();
}


$sql = "SELECT ProductID, ProductName, Price, ImageURL FROM Products"; 
$stmt = $pdo->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');


echo json_encode($products);

?>