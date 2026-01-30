#!/usr/bin/env php
<?php
/**
 * Script de teste para YASMIN API
 * Simula uma requisição HTTP POST
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular REQUEST POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [];
file_put_contents('php://input', json_encode(['mensagem' => 'Quero algo saudável']));

// Incluir a API
require_once __DIR__ . '/app/api/yasmin_api.php';
?>
