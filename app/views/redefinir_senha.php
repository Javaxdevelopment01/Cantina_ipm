<?php
require_once __DIR__ . '/../../config/database.php';

$token = $_GET['token'] ?? '';
$erro = '';
$validToken = false;

if (empty($token)) {
    $erro = 'Token inválido ou não fornecido.';
} else {
    // Validar token
    try {
        $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW() LIMIT 1");
        $stmt->execute([':token' => $token]);
        $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resetRequest) {
            $validToken = true;
        } else {
            $erro = 'Este link de recuperação é inválido ou expirou.';
        }
    } catch (Exception $e) {
        $erro = 'Erro ao validar token.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: linear-gradient(135deg, #012E40 0%, #1a4d66 100%); margin: 0; }
        .card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; max-width: 400px; text-align: center; }
        h2 { color: #012E40; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #D4AF37; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        button:hover { background: #c49621; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 0.9em; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Criar Nova Senha</h2>
        
        <?php if ($erro): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
            <a href="recuperar_senha.php" style="color: #012E40; text-decoration: none;">Solicitar novo link</a>
        <?php elseif ($validToken): ?>
            <div id="message" class="alert" style="display: none;"></div>
            
            <form id="newPasswordForm">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="password" name="senha" placeholder="Nova Senha (min. 6 caracteres)" required minlength="6">
                <input type="password" name="confirmSenha" placeholder="Confirmar Senha" required>
                <button type="submit">Atualizar Senha</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($validToken): ?>
    <script>
        document.getElementById('newPasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            const msg = document.getElementById('message');
            const senha = this.querySelector('[name=senha]').value;
            const confirm = this.querySelector('[name=confirmSenha]').value;

            if (senha !== confirm) {
                msg.textContent = 'As senhas não coincidem.';
                msg.className = 'alert alert-error';
                msg.style.display = 'block';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Atualizando...';

            try {
                const formData = new FormData(this);
                const response = await fetch('../api/processar_redefinicao.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                msg.textContent = data.message;
                msg.className = 'alert ' + (data.success ? 'alert-success' : 'alert-error');
                msg.style.display = 'block';

                if (data.success) {
                    this.style.display = 'none';
                    setTimeout(() => {
                        // Redireciona baseado no tipo (poderia vir do backend, mas vamos para login vendedor por padrao ou admin se deduzido)
                        // Como não sabemos o tipo aqui facilmente sem consultar, mandamos para uma pagina neutra ou home
                        window.location.href = '../Vendedor/login_vendedor.php'; 
                    }, 2000);
                }
            } catch (err) {
                msg.textContent = 'Erro ao processar solicitação.';
                msg.className = 'alert alert-error';
                msg.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Atualizar Senha';
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
