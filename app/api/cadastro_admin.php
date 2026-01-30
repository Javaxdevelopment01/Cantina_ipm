<?php
// STRICT JSON-ONLY OUTPUT - NO HTML, NO WARNINGS, NO NOTICES
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=UTF-8');

// ✅ PROTEÇÃO: Verifica autenticação de admin
session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado. Faça login como administrador.']);
    exit;
}

try {
    // Validar Input
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!$dados) {
        $dados = $_POST;
    }
    
    $nome = trim($dados['nome'] ?? '');
    $email = trim($dados['email'] ?? '');
    $senha = $dados['senha'] ?? '';
    $confirmSenha = $dados['confirmSenha'] ?? '';
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmSenha)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
        exit;
    }
    
    if (strlen($nome) < 3) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nome deve ter pelo menos 3 caracteres']);
        exit;
    }
    
    if (strlen($senha) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Senha deve ter pelo menos 6 caracteres']);
        exit;
    }
    
    if ($senha !== $confirmSenha) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'As senhas não coincidem']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        exit;
    }
    
    // Incluir BD
    require_once __DIR__ . '/../../config/database.php';
    
    // Verificar se email já existe
    $stmt = $conn->prepare('SELECT id FROM admin WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    if ($stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email já registrado']);
        exit;
    }
    
    // Processar imagem
    $foto_perfil = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        
        // Validar tipo
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Formato de imagem não permitido']);
            exit;
        }
        
        // Validar tamanho (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Imagem muito grande (máx 5MB)']);
            exit;
        }
        
        // Criar diretório
        $projectRoot = realpath(__DIR__ . '/../../');
        $upload_dir = $projectRoot . '/uploads/admin';
        
        if (!is_dir($upload_dir)) {
            if (!@mkdir($upload_dir, 0777, true)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao criar diretório de upload']);
                exit;
            }
        }
        
        if (!is_writable($upload_dir)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Pasta de upload sem permissão de escrita. Verifica ./uploads/admin/']);
            exit;
        }
        
        // Salvar com nome único
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'admin_' . time() . '_' . uniqid() . '.' . $ext;
        $filepath = $upload_dir . '/' . $filename;
        $foto_perfil = '/uploads/admin/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload da imagem']);
            exit;
        }
    }
    
    // Inserir admin
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('
        INSERT INTO admin (nome, email, senha, foto_perfil) 
        VALUES (:nome, :email, :senha, :foto)
    ');
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha' => $senha_hash,
        ':foto' => $foto_perfil
    ]);
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Admin registrado com sucesso']);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
    exit;
}
?>
