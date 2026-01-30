<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método inválido.';
    echo json_encode($response);
    exit;
}

$token = $_POST['token'] ?? '';
$senha = $_POST['senha'] ?? '';
$confirm = $_POST['confirmSenha'] ?? '';

if (empty($token) || empty($senha) || strlen($senha) < 6) {
    $response['message'] = 'Dados inválidos. A senha deve ter no mínimo 6 caracteres.';
    echo json_encode($response);
    exit;
}

try {
    // 1. Validar Token
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW() LIMIT 1");
    $stmt->execute([':token' => $token]);
    $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resetRequest) {
        $response['message'] = 'Token inválido ou expirado.';
        echo json_encode($response);
        exit;
    }

    $email = $resetRequest['email'];
    $tipo = $resetRequest['user_type'];
    $tabela = ($tipo === 'admin') ? 'admin' : 'vendedor';

    // 2. Hash da Nova Senha
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // 3. Atualizar Tabela de Usuário
    // Nota: 'senha' é o campo usual. Ajuste se for diferente (ex: password)
    $upd = $conn->prepare("UPDATE $tabela SET senha = :senha WHERE email = :email");
    $upd->execute([':senha' => $senhaHash, ':email' => $email]);

    // 4. Deletar Token usado
    $del = $conn->prepare("DELETE FROM password_resets WHERE email = :email");
    $del->execute([':email' => $email]);

    $response['success'] = true;
    $response['message'] = 'Sua senha foi atualizada com sucesso! Redirecionando...';

} catch (Exception $e) {
    $response['message'] = 'Erro ao atualizar senha.';
    error_log($e->getMessage());
}

echo json_encode($response);
