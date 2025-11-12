<?php
// Conexão com MySQL usando PDO
$host = '127.0.0.1';
$db   = 'ipm_cantina';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch(PDOException $e) {
    echo "Erro ao conectar: " . $e->getMessage();
    exit;
}
