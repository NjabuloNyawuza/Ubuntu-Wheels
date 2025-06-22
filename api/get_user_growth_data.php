<?php


require_once '../db_connection.php'; 

header('Content-Type: application/json'); 

$period = $_GET['period'] ?? 'month'; 

$labels = [];
$values = [];

try {
    switch ($period) {
        case 'week':
       
            $stmt = $pdo->query("
                SELECT 
                    DATE(created_at) as date, 
                    COUNT(id) as new_users_count
                FROM users 
                WHERE created_at >= CURDATE() - INTERVAL 6 DAY 
                GROUP BY date 
                ORDER BY date ASC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

           
            $temp_data = [];
            foreach ($data as $row) {
                $temp_data[$row['date']] = (int)$row['new_users_count'];
            }

            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i day"));
                $day_label = date('D', strtotime($date)); 
                $labels[] = $day_label;
                $values[] = $temp_data[$date] ?? 0;
            }
            break;

        case 'month':
      
            $stmt = $pdo->query("
                SELECT 
                    YEARWEEK(created_at, 1) as week_num, 
                    COUNT(id) as new_users_count
                FROM users 
                WHERE created_at >= CURDATE() - INTERVAL 4 WEEK 
                GROUP BY week_num 
                ORDER BY week_num ASC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
            $weekly_values = array_fill(0, 4, 0);

            if (count($data) > 0) {
                $start_week_num = intval(date('YW', strtotime("-3 week", strtotime(date('Y-m-d')))));
                foreach ($data as $row) {
                    $week_diff = intval(substr($row['week_num'], 4)) - intval(substr(strval($start_week_num), 4));
                    if ($week_diff >= 0 && $week_diff < 4) {
                        $weekly_values[$week_diff] = (int)$row['new_users_count'];
                    }
                }
            }
            $values = $weekly_values;
            break;

        case 'year':
        
            $stmt = $pdo->query("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month, 
                    COUNT(id) as new_users_count
                FROM users 
                WHERE created_at >= CURDATE() - INTERVAL 11 MONTH 
                GROUP BY month 
                ORDER BY month ASC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

         
            $temp_data = [];
            foreach ($data as $row) {
                $temp_data[$row['month']] = (int)$row['new_users_count'];
            }

            for ($i = 11; $i >= 0; $i--) {
                $month_year = date('Y-m', strtotime("-$i month"));
                $month_label = date('M', strtotime($month_year)); 
                $labels[] = $month_label;
                $values[] = $temp_data[$month_year] ?? 0;
            }
            break;

        default:
      
            $stmt = $pdo->query("
                SELECT 
                    YEARWEEK(created_at, 1) as week_num, 
                    COUNT(id) as new_users_count
                FROM users 
                WHERE created_at >= CURDATE() - INTERVAL 4 WEEK 
                GROUP BY week_num 
                ORDER BY week_num ASC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
            $weekly_values = array_fill(0, 4, 0);
            if (count($data) > 0) {
                $start_week_num = intval(date('YW', strtotime("-3 week", strtotime(date('Y-m-d')))));
                foreach ($data as $row) {
                    $week_diff = intval(substr($row['week_num'], 4)) - intval(substr(strval($start_week_num), 4));
                    if ($week_diff >= 0 && $week_diff < 4) {
                        $weekly_values[$week_diff] = (int)$row['new_users_count'];
                    }
                }
            }
            $values = $weekly_values;
            break;
    }

    echo json_encode(['labels' => $labels, 'values' => $values]);

} catch (PDOException $e) {
    error_log("User Growth API Error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch user growth data.', 'details' => $e->getMessage()]);
}
?>