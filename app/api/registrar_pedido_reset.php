<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método inválido.';
    echo json_encode($response);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'E-mail inválido.';
    echo json_encode($response);
    exit;
}

if (empty($new_password) || strlen($new_password) < 6) {
    $response['message'] = 'A nova senha deve ter no mínimo 6 caracteres.';
    echo json_encode($response);
    exit;
}

if ($new_password !== $confirm_password) {
    $response['message'] = 'As senhas não coincidem.';
    echo json_encode($response);
    exit;
}

$tipo = $_POST['tipo'] ?? 'vendedor';
if (!in_array($tipo, ['vendedor', 'admin'])) {
    $tipo = 'vendedor';
}

try {
    // 1. Verificar se o Usuário existe da tabela correta
    $tabela = $tipo; // 'vendedor' ou 'admin'
    
    $stmt = $conn->prepare("SELECT id FROM $tabela WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    if (!$stmt->fetch()) {
        $response['message'] = 'E-mail não encontrado na base de ' . ucfirst($tabela) . 's.';
        echo json_encode($response);
        exit;
    }

    // 2. Hash da nova senha para guardar temporariamente
    $hash = password_hash($new_password, PASSWORD_DEFAULT);

    // 3. Inserir pedido na tabela
    // Limpa pedidos pendentes anteriores deste email
    $del = $conn->prepare("DELETE FROM password_reset_requests WHERE email = :email AND status = 'pending'");
    $del->execute([':email' => $email]);

    $ins = $conn->prepare("INSERT INTO password_reset_requests (email, new_password_hash, user_type, status) VALUES (:email, :hash, :tipo, 'pending')");
    $ins->execute([
        ':email' => $email,
        ':hash' => $hash,
        ':tipo' => $tipo
    ]);

    $response['success'] = true;
    $response['id'] = $conn->lastInsertId();
    $response['message'] = 'Sua solicitação foi registrada! Aguarde a aprovação do Administrador.';

} catch (Exception $e) {
    $response['message'] = 'Erro interno ao registrar pedido.';
    error_log($e->getMessage());
}

echo json_encode($response);
