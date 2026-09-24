<?php
// config/database.php

$host = '127.0.0.1';
$dbname = 'erp_pupuseria';
$username = 'root'; 
$password = '!Y)m_yxsccxz0B3l';

try {
    // We use the declared variables directly to avoid socket resolution failures
    $pdo = new PDO("mysql:host=localhost;dbname=erp_pupuseria;charset=utf8mb4", $username, $password);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
