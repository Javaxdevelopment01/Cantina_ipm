<?php
// ia/responder.php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

$mensagem = $_POST['mensagem'] ?? '';
$resposta = '';

// Exemplo de lógica simples (a IA pode ser ligada ao OpenAI depois)
if (!empty($mensagem)) {
    $mensagemLower = strtolower($mensagem);

    if (strpos($mensagemLower, 'venda') !== false) {
        $resposta = 'Deseja criar uma nova venda ou consultar vendas existentes?';
    } elseif (strpos($mensagemLower, 'produto') !== false) {
        $resposta = 'Você quer consultar produtos ou adicionar um novo?';
    } elseif (strpos($mensagemLower, 'estoque') !== false) {
        $resposta = 'Quer ver o estoque atual ou adicionar entradas?';
    } else {
        $resposta = 'Olá! Posso ajudá-lo a gerir vendas, produtos, clientes e estoque.';
    }

    // Salvar conversa no banco
    $stmt = $pdo->prepare("INSERT INTO conversas_ia (sessao_usuario, origem_mensagem, texto_mensagem) VALUES (?, ?, ?)");
    $stmt->execute([session_id(), 'cliente', $mensagem]);
    $stmt = $pdo->prepare("INSERT INTO conversas_ia (sessao_usuario, origem_mensagem, texto_mensagem) VALUES (?, ?, ?)");
    $stmt->execute([session_id(), 'ia', $resposta]);
}

echo json_encode(['resposta' => $resposta]);
