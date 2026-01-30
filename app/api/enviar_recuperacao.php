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
$tipo = $_POST['tipo'] ?? 'vendedor';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'E-mail inválido.';
    echo json_encode($response);
    exit;
}

// 1. Verificar se usuário existe
$tabela = ($tipo === 'admin') ? 'admin' : 'vendedor';
try {
    $stmt = $conn->prepare("SELECT id, nome FROM $tabela WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Por segurança, não confirmamos se o email existe ou não,
        // mas em sistemas internos pode ser útil informar.
        // Vamos retornar sucesso genérico.
        $response['success'] = true;
        $response['message'] = 'Se o e-mail estiver cadastrado, você receberá um link em breve.';
        echo json_encode($response);
        exit;
    }

    // 2. Gerar Token Seguro
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // 3. Salvar token no BD
    // Remove tokens antigos desse email
    $del = $conn->prepare("DELETE FROM password_resets WHERE email = :email");
    $del->execute([':email' => $email]);

    $ins = $conn->prepare("INSERT INTO password_resets (email, token, user_type, expires_at) VALUES (:email, :token, :tipo, :expires)");
    $ins->execute([
        ':email' => $email,
        ':token' => $token,
        ':tipo' => $tipo,
        ':expires' => $expires
    ]);

    // 4. Enviar E-mail (Basic mail() - requer SMTP configurado no php.ini ou sendmail local)
    // Em WAMP, sem Sendmail, isso vai falhar se não configurado.
    // É importante avisar o usuário disso.
    
    $link = "http://" . $_SERVER['HTTP_HOST'] . "/app/views/redefinir_senha.php?token=" . $token;
    
    $subject = "Recuperar Senha - Cantina IPM";
    $message = "Olá " . $user['nome'] . ",\n\n";
    $message .= "Você solicitou a redefinição de sua senha.\n";
    $message .= "Clique no link abaixo para criar uma nova senha:\n\n";
    $message .= $link . "\n\n";
    $message .= "Este link expira em 1 hora.\n\n";
    $message .= "Se não foi você, ignore este e-mail.";
    
    $headers = "From: no-reply@cantinaipm.com\r\n" .
               "Reply-To: suporte@cantinaipm.com\r\n" .
               "X-Mailer: PHP/" . phpversion();

    if (mail($email, $subject, $message, $headers)) {
        $response['success'] = true;
        $response['message'] = 'Link de recuperação enviado para seu e-mail.';
    } else {
        // Fallback para ambiente de desenvolvimento sem email
        // Logar o link para o desenvolvedor ver
        error_log("[Recuperação de Senha] Link para $email: $link");
        
        $response['success'] = true; // Fingimos sucesso para o usuario nao ficar travado se for erro de config server
        $response['message'] = 'Solicitação recebida. (Ambiente Dev: Verifique o arquivo de log PHP para o link)';
        $response['dev_link'] = $link; // APENAS PARA DEBUG - REMOVER EM PROD
    }

} catch (Exception $e) {
    $response['message'] = 'Erro interno ao processar solicitação.';
    error_log($e->getMessage());
}

echo json_encode($response);
