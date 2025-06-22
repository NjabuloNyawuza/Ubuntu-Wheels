<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$host = 'sql208.infinityfree.com'; 
$dbname = 'if0_39257078_ubuntutrade_database'; 
$username = 'if0_39257078'; 


$password = 'RNUNZwJGgya'; 

try {

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);


    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); 



} catch (PDOException $e) {

    die("Database connection failed: " . $e->getMessage());
}
?>