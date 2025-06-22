<?php


require_once '../db_connection.php'; 

header('Content-Type: application/json'); 

$period = $_GET['period'] ?? 'all_time'; 

$stats = [
    'total_users' => 0,
    'new_users_today' => 0, 
    'active_listings' => 0,
    'pending_listings' => 0,
    'total_transactions' => 0,
    'transactions_today' => 0, 
    'total_revenue' => 0.00,
    'revenue_today' => 0.00, 
    'unread_admin_messages' => 0,
    'pending_seller_applications' => 0,
    'pending_reports' => 0,
    'unread_notifications' => 0,
];


$date_condition = "";
$start_date = null;
$end_date = null;

switch ($period) {
    case 'today':
        $date_condition = "AND DATE(created_at) = CURDATE()"; 
        $transaction_date_condition = "AND DATE(transaction_date) = CURDATE()"; 
        $listing_date_condition = "AND DATE(DateListed) = CURDATE()"; 
        $report_date_condition = "AND DATE(reported_at) = CURDATE()"; 
        break;
    case 'yesterday':
        $date_condition = "AND DATE(created_at) = CURDATE() - INTERVAL 1 DAY";
        $transaction_date_condition = "AND DATE(transaction_date) = CURDATE() - INTERVAL 1 DAY";
        $listing_date_condition = "AND DATE(DateListed) = CURDATE() - INTERVAL 1 DAY";
        $report_date_condition = "AND DATE(reported_at) = CURDATE() - INTERVAL 1 DAY";
        break;
    case 'this_week':
        $date_condition = "AND created_at >= CURDATE() - INTERVAL (WEEKDAY(CURDATE()) + 0) DAY"; 
        $transaction_date_condition = "AND transaction_date >= CURDATE() - INTERVAL (WEEKDAY(CURDATE()) + 0) DAY";
        $listing_date_condition = "AND DateListed >= CURDATE() - INTERVAL (WEEKDAY(CURDATE()) + 0) DAY";
        $report_date_condition = "AND reported_at >= CURDATE() - INTERVAL (WEEKDAY(CURDATE()) + 0) DAY";
        break;
    case 'this_month':
        $date_condition = "AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
        $transaction_date_condition = "AND transaction_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
        $listing_date_condition = "AND DateListed >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
        $report_date_condition = "AND reported_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
        break;
    case 'this_year':
        $date_condition = "AND created_at >= DATE_FORMAT(CURDATE(), '%Y-01-01')";
        $transaction_date_condition = "AND transaction_date >= DATE_FORMAT(CURDATE(), '%Y-01-01')";
        $listing_date_condition = "AND DateListed >= DATE_FORMAT(CURDATE(), '%Y-01-01')";
        $report_date_condition = "AND reported_at >= DATE_FORMAT(CURDATE(), '%Y-01-01')";
        break;
    case 'custom_range':
      
        $start_date = $_GET['start_date'] ?? null;
        $end_date = $_GET['end_date'] ?? null;

        if ($start_date && $end_date) {
            $date_condition = "AND created_at BETWEEN :start_date AND :end_date + INTERVAL 1 DAY";
            $transaction_date_condition = "AND transaction_date BETWEEN :start_date AND :end_date + INTERVAL 1 DAY";
            $listing_date_condition = "AND DateListed BETWEEN :start_date AND :end_date + INTERVAL 1 DAY";
            $report_date_condition = "AND reported_at BETWEEN :start_date AND :end_date + INTERVAL 1 DAY";
        } else {
          
            $period = 'all_time';
            $date_condition = "";
            $transaction_date_condition = "";
            $listing_date_condition = "";
            $report_date_condition = "";
        }
        break;
    case 'all_time': 
    default:
        $date_condition = "";
        $transaction_date_condition = "";
        $listing_date_condition = "";
        $report_date_condition = "";
        break;
}

try {

    $stmt = $pdo->query("SELECT COUNT(id) FROM users");
    $stats['total_users'] = $stmt->fetchColumn();


    $query = "SELECT COUNT(id) FROM users WHERE 1=1 " . $date_condition;
    $stmt = $pdo->prepare($query);
    if ($period === 'custom_range' && $start_date && $end_date) {
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
    }
    $stmt->execute();
    $stats['new_users_today'] = $stmt->fetchColumn(); 

    
    $stmt = $pdo->query("SELECT COUNT(ProductID) FROM Products WHERE status = 'active'");
    $stats['active_listings'] = $stmt->fetchColumn();


    $stmt = $pdo->query("SELECT COUNT(ProductID) FROM Products WHERE status = 'pending'");
    $stats['pending_listings'] = $stmt->fetchColumn();


    $query = "SELECT COUNT(transaction_id) FROM transactions WHERE 1=1 " . $transaction_date_condition;
    $stmt = $pdo->prepare($query);
    if ($period === 'custom_range' && $start_date && $end_date) {
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
    }
    $stmt->execute();
    $stats['total_transactions'] = $stmt->fetchColumn();


    $query = "SELECT COUNT(transaction_id) FROM transactions WHERE 1=1 AND status = 'completed' " . $transaction_date_condition;
    $stmt = $pdo->prepare($query);
    if ($period === 'custom_range' && $start_date && $end_date) {
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
    }
    $stmt->execute();
    $stats['transactions_today'] = $stmt->fetchColumn(); 

    $query = "SELECT SUM(amount) FROM transactions WHERE status = 'completed' " . $transaction_date_condition;
    $stmt = $pdo->prepare($query);
    if ($period === 'custom_range' && $start_date && $end_date) {
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
    }
    $stmt->execute();
    $stats['total_revenue'] = $stmt->fetchColumn() ?? 0.00;

  
    $query = "SELECT SUM(amount) FROM transactions WHERE status = 'completed' " . $transaction_date_condition;
    $stmt = $pdo->prepare($query);
    if ($period === 'custom_range' && $start_date && $end_date) {
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
    }
    $stmt->execute();
    $stats['revenue_today'] = $stmt->fetchColumn() ?? 0.00;


    $stmt = $pdo->query("SELECT COUNT(message_id) FROM messages WHERE is_read = 0"); 
    $stats['unread_admin_messages'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(user_id) FROM sellers WHERE status = 'pending'");
    $stats['pending_seller_applications'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(report_id) FROM reports WHERE status = 'pending'");
    $stats['pending_reports'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(NotificationID) FROM Notifications WHERE IsRead = 0"); 
    $stats['unread_notifications'] = $stmt->fetchColumn();

    echo json_encode($stats);

} catch (PDOException $e) {
    error_log("Dashboard Stats API Error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch dashboard stats.', 'details' => $e->getMessage()]);
}
?>