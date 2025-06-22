<?php
$host = 'localhost';
$dbname = 'book';
$user = 'root';
$password = '';
try {
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8', $user, $password);
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}