<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php'; 

session_start(); 


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $errors = [];

  
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }

 
    if (empty($errors)) {
        try {
   
            $stmt = $pdo->prepare("SELECT id, name, email, password_hash FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

       
            if ($user) {
           
                if (password_verify($password, $user['password_hash'])) {
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];

                  
                    if ($remember) {
                   
                        $token = bin2hex(random_bytes(32));
                        $expiry = time() + (86400 * 30); 

                    
                        $stmt_token = $pdo->prepare("UPDATE users SET remember_token = :token, remember_expiry = :expiry WHERE id = :user_id");
                        $stmt_token->bindParam(':token', $token);
                        $stmt_token->bindParam(':expiry', $expiry, PDO::PARAM_INT);
                        $stmt_token->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                        $stmt_token->execute();

                    
                        setcookie('remember_token', $token, $expiry, '/', '', true, true); 
                    }

                    header('Location: index.php');
                    exit;

                } else {
                
                    $errors['general'] = 'Invalid email or password';
                }
            } else {
            
                $errors['general'] = 'Invalid email or password';
            }

        } catch (PDOException $e) {
         
            $errors['general'] = 'Database error: ' . $e->getMessage();
        }
    }


    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        $_SESSION['login_email'] = $email; 
        header('Location: signin.html'); 
        exit;
    }
} else {

    header('Location: signin.html'); 
    exit;
}
?>