<?php
$host = 'localhost';
$dbname = 'book';
$user = 'root';
$password = '';

try {
    $pdo = new PDO(
        'mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', 
        $user, 
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );    
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET NAMES utf8mb4");
    
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>