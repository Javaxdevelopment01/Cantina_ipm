<?php
session_start();
header('Content-Type: text/plain');

if(!isset($_SESSION['vendedor_id']) || empty($_POST['mensagem'])) {
    echo "Mensagem não recebida.";
    exit;
}

$mensagem = trim($_POST['mensagem']);

// Aqui você pode adicionar lógica real de IA ou respostas pré-definidas
// Por enquanto, vamos fazer uma resposta simples
echo "Recebido: " . htmlspecialchars($mensagem);
