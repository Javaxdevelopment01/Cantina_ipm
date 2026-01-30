<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';

// Verifica se é admin logado
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método inválido.';
    echo json_encode($response);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? ''; // 'approve' ou 'reject'

if (!$id || !in_array($action, ['approve', 'reject'])) {
    $response['message'] = 'Dados inválidos.';
    echo json_encode($response);
    exit;
}

try {
    // 1. Busca o pedido
    $stmt = $conn->prepare("SELECT * FROM password_reset_requests WHERE id = :id AND status = 'pending'");
    $stmt->execute([':id' => $id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $response['message'] = 'Solicitação não encontrada ou já processada.';
        echo json_encode($response);
        exit;
    }

    if ($action === 'reject') {
        // Apenas marca como rejected
        $upd = $conn->prepare("UPDATE password_reset_requests SET status = 'rejected' WHERE id = :id");
        $upd->execute([':id' => $id]);
        $response['success'] = true;
        $response['message'] = 'Solicitação rejeitada com sucesso.';
    } elseif ($action === 'approve') {
        // 2. Atualiza a senha na tabela de origem
        $tabela = ($request['user_type'] === 'admin') ? 'admin' : 'vendedor';
        
        $updUser = $conn->prepare("UPDATE $tabela SET senha = :senha WHERE email = :email");
        $updUser->execute([
            ':senha' => $request['new_password_hash'], 
            ':email' => $request['email']
        ]);
        
        // 3. Marca como approved
        $updReq = $conn->prepare("UPDATE password_reset_requests SET status = 'approved' WHERE id = :id");
        $updReq->execute([':id' => $id]);
        
        $response['success'] = true;
        $response['message'] = 'Senha atualizada com sucesso! O usuário já pode logar.';
    }

} catch (Exception $e) {
    $response['message'] = 'Erro interno: ' . $e->getMessage();
}

echo json_encode($response);
