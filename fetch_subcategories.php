// fetch_subcategories.php
<?php
require_once 'db_connection.php'; 

header('Content-Type: application/json');

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$subcategories = [];
if ($category_id > 0) {
    try {
      
        $stmt = $pdo->prepare("SELECT SubcategoryID AS id, SubcategoryName AS name FROM Subcategories WHERE CategoryID = ? ORDER BY SubcategoryName");
        $stmt->execute([$category_id]);
        $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
     
    }
}
echo json_encode($subcategories);
?>