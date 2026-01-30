<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (!empty($email) && !empty($senha)) {
        try {
            $stmt = $conn->prepare("SELECT * FROM vendedor WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $vendedor = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($vendedor && password_verify($senha, $vendedor['senha'])) {
                $_SESSION['vendedor_id'] = $vendedor['id'];
                $_SESSION['vendedor_nome'] = $vendedor['nome'] ?? 'Vendedor';
                $_SESSION['vendedor_foto'] = $vendedor['imagem'] ?? null;

                // Log Login History
                $ip = $_SERVER['REMOTE_ADDR'];
                $log = $conn->prepare("INSERT INTO login_history (user_id, user_type, ip_address, status) VALUES (:uid, 'vendedor', :ip, 'success')");
                $log->execute([':uid' => $vendedor['id'], ':ip' => $ip]);

                header('Location: dashboard_vendedor.php');
                exit;
            } else {
                $erro = 'Email ou senha inválidos.';
            }
        } catch (Throwable $e) {
            $erro = 'Erro ao conectar. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Vendedor</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #012E40 0%, #1a4d66 100%);
            min-height: 100vh;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 90%;
            max-width: 600px;
            display: flex;
        }

        .card-left {
            background: linear-gradient(135deg, #D4AF37 0%, #c49621 100%);
            padding: 40px 30px;
            color: #012E40;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            flex: 0 0 40%;
            clip-path: polygon(0 0, 100% 20%, 80% 100%, 0 100%);
        }

        .card-left::before {
            content: '';
            position: absolute;
            top: -50px;
            left: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .card-left h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
            font-weight: 600;
        }

        .card-left p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .btn-signin {
            background: white;
            color: #D4AF37;
            border: none;
            padding: 10px 25px;
            border-radius: 20px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            z-index: 1;
            font-size: 0.9rem;
        }

        .btn-signin:hover {
            transform: translateY(-2px);
        }

        .card-right {
            flex: 1;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .card-header-right {
            text-align: center;
            margin-bottom: 25px;
        }

        .avatar {
            width: 60px;
            height: 60px;
            background: transparent;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .card-header-right h2 {
            color: #012E40;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .vendor-badge {
            display: inline-block;
            background: #D4AF37;
            color: #012E40;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 8px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            background: #f9f9f9;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #D4AF37;
            background: white;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }

        .form-group input::placeholder {
            color: #999;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: #D4AF37;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #fef2f2;
            border-left: 4px solid #e74c3c;
            color: #c0392b;
            padding: 10px 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #012E40 0%, #1a4d66 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(1, 46, 64, 0.3);
        }

        .social-login {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
            margin-top: 20px;
        }

        .social-login p {
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 12px;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            border: 1px solid #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #D4AF37;
        }

        .social-icon:hover {
            border-color: #D4AF37;
            background: #f5f5f5;
        }

        @media (max-width: 768px) {
            .login-card {
                flex-direction: column;
            }

            .card-left {
                flex: 0 0 auto;
                clip-path: none;
                padding: 30px;
            }

            .card-right {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="card-left">
            <h3>ACESSO</h3>
            <p>Portal de Vendedores</p>
            <p style="font-size: 0.8rem; opacity: 0.8; margin-top: 10px;">Faça login com suas credenciais</p>
            <!-- <button class="btn-signin" type="button" onclick="document.querySelector('.card-right').scrollIntoView({behavior: 'smooth'})">ENTRAR</button> -->
        </div>

        <div class="card-right">
            <div class="card-header-right">
                <div class="avatar">
                    <img src="../../../assets/images/ipm_logo.png" alt="IPM" style="width:60px;height:60px;border-radius:50%;object-fit:cover;display:block">
                </div>
                <h2>VENDEDOR</h2>
                <div class="vendor-badge">Painel de Vendas</div>
            </div>

            <?php if ($erro): ?>
                <div class="error-message"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <input type="email" name="email" placeholder="E-mail" required>
                </div>

                <div class="form-group">
                    <input type="password" name="senha" placeholder="Senha" required>
                </div>

                <div class="forgot-password">
                    <a href="solicitar_reset.php">Esqueceu a senha?</a>
                </div>

                <button type="submit" class="btn-login">ENTRAR</button>
            </form>
        </div>
    </div>

</body>
</html>
