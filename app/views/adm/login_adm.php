<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (!empty($email) && !empty($senha)) {
        try {
            $stmt = $conn->prepare("SELECT * FROM admin WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($senha, $admin['senha'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_nome'] = $admin['nome'] ?? 'Administrador';
                $_SESSION['admin_foto'] = $admin['foto_perfil'] ?? null;
                
                // Log Login History
                $ip = $_SERVER['REMOTE_ADDR'];
                $log = $conn->prepare("INSERT INTO login_history (user_id, user_type, ip_address, status) VALUES (:uid, 'admin', :ip, 'success')");
                $log->execute([':uid' => $admin['id'], ':ip' => $ip]);

                header('Location: dashboard_adm.php');
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
    <title>Login Administrador</title>
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
            color: white;
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
            color: #012E40;
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
            background: linear-gradient(135deg, #D4AF37 0%, #c49621 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            margin: 0 auto 15px;
        }

        .card-header-right h2 {
            color: #012E40;
            font-size: 1.8rem;
            font-weight: 700;
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

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-login {
            flex: 1;
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

        .btn-signup {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #D4AF37 0%, #c49621 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.3);
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

        /* Modal Cadastro */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 40px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .modal-header h2 {
            color: #012E40;
            font-size: 1.6rem;
            font-weight: 700;
        }

        .close-modal {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
            border: none;
            background: none;
        }

        .close-modal:hover {
            color: #D4AF37;
        }

        .school-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .school-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .admin-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 8px;
        }

        .avatar-upload {
            position: relative;
        }

        .avatar-upload #uploadPlaceholder:hover {
            opacity: 0.8;
            transform: scale(1.05);
            transition: all 0.3s;
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

            .modal-content {
                padding: 30px 20px;
                max-height: 90vh;
                overflow-y: auto;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="card-left">
            <h3>ACESSO</h3>
            <p>Administrador do Sistema</p>
            <p style="font-size: 0.8rem; opacity: 0.8; margin-top: 10px;">Faça login com suas credenciais</p>
            <!-- <button class="btn-signin" type="button" onclick="document.querySelector('.card-right').scrollIntoView({behavior: 'smooth'})">ENTRAR</button> -->
        </div>

        <div class="card-right">
            <div class="card-header-right">
                <div class="school-logo">
                    <img src="/assets/images/ipm_logo.png" alt="Logo IPM" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="display:none; width:100%; height:100%; align-items:center; justify-content:center; background: linear-gradient(135deg, #D4AF37 0%, #c49621 100%); border-radius:50%; color:#012E40; font-size:2rem; font-weight:700;">ADM</div>
                </div>
                <h2>ADMINISTRADOR</h2>
                <div class="admin-badge">Painel de Controle</div>
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
                    <a href="../Vendedor/solicitar_reset.php?tipo=admin">Esqueceu a senha?</a>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-login">ENTRAR</button>
                    <!-- ❌ REMOVIDO: Cadastro de admin NÃO pode ser feito na tela de login. 
                         Apenas admins autenticados podem cadastrar novos admins no painel. -->
                    <!-- <button type="button" class="btn-signup" onclick="openSignup()">CADASTRO</button> -->
                </div>
            </form>

        </div>
    </div>

    <!-- Modal Cadastro -->
    <div class="modal" id="signupModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeSignup()">×</button>
            
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px;">
                <div style="flex: 1;">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <div class="avatar-upload">
                            <img id="previewImg" src="" alt="Preview" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; display: none; margin: 0 auto;">
                            <div id="uploadPlaceholder" style="width: 60px; height: 60px; background: linear-gradient(135deg, #D4AF37 0%, #c49621 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #012E40; font-size: 1.8rem; margin: 0 auto; cursor: pointer;" onclick="document.getElementById('fotoPerfil').click()">
                                <i class="fas fa-camera"></i>
                            </div>
                            <input type="file" id="fotoPerfil" name="foto_perfil" accept="image/*" style="display: none;">
                        </div>
                        <p style="font-size: 0.75rem; color: #999; margin-top: 8px;">Clique para adicionar foto</p>
                    </div>
                    <h2 style="color: #012E40; text-align: center; font-size: 1.4rem;">NOVO ADM</h2>
                </div>
            </div>

            <form id="signupForm">
                <div id="signupMessage" style="display: none; padding: 10px 12px; margin-bottom: 15px; border-radius: 4px; font-size: 0.85rem;"></div>

                <div class="form-group">
                    <input type="text" name="nome" id="nomeSignup" placeholder="Nome Completo" required>
                </div>

                <div class="form-group">
                    <input type="email" name="email" id="emailSignup" placeholder="E-mail" required>
                </div>

                <div class="form-group">
                    <input type="password" name="senha" id="senhaSignup" placeholder="Senha (mín. 6 caracteres)" required>
                </div>

                <div class="form-group">
                    <input type="password" name="confirmSenha" id="confirmSenhaSignup" placeholder="Confirmar Senha" required>
                </div>

                <button type="submit" class="btn-signup" style="width: 100%;">CADASTRAR</button>
            </form>
        </div>
    </div>

    <script>
        function openSignup() {
            document.getElementById('signupModal').classList.add('active');
            document.getElementById('signupForm').reset();
            document.getElementById('signupMessage').style.display = 'none';
            document.getElementById('previewImg').style.display = 'none';
            document.getElementById('uploadPlaceholder').style.display = 'flex';
        }

        function closeSignup() {
            document.getElementById('signupModal').classList.remove('active');
        }

        document.getElementById('signupModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSignup();
            }
        });

        // Preview de imagem
        document.getElementById('fotoPerfil').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const previewImg = document.getElementById('previewImg');
                    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
                    previewImg.src = event.target.result;
                    previewImg.style.display = 'block';
                    uploadPlaceholder.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });

        // Processar cadastro
        document.getElementById('signupForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const nome = document.getElementById('nomeSignup').value.trim();
            const email = document.getElementById('emailSignup').value.trim();
            const senha = document.getElementById('senhaSignup').value;
            const confirmSenha = document.getElementById('confirmSenhaSignup').value;
            const messageDiv = document.getElementById('signupMessage');

            // Validações básicas no frontend
            if (nome.length < 3) {
                showMessage('O nome deve ter pelo menos 3 caracteres.', 'error');
                return;
            }

            if (senha.length < 6) {
                showMessage('A senha deve ter pelo menos 6 caracteres.', 'error');
                return;
            }

            if (senha !== confirmSenha) {
                showMessage('As senhas não conferem.', 'error');
                return;
            }

            // Prepara FormData para envio
            const formData = new FormData();
            formData.append('nome', nome);
            formData.append('email', email);
            formData.append('senha', senha);
            formData.append('confirmSenha', confirmSenha);

            // Adiciona imagem se selecionada
            const fileInput = document.getElementById('fotoPerfil');
            if (fileInput.files.length > 0) {
                formData.append('foto', fileInput.files[0]);
            }

            try {
                const response = await fetch('/app/api/cadastro_admin.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('✓ Cadastro realizado com sucesso! Redirecionando...', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('Erro ao processar cadastro. Tente novamente.', 'error');
                console.error('Erro:', error);
            }
        });

        function showMessage(message, type) {
            const messageDiv = document.getElementById('signupMessage');
            messageDiv.textContent = message;
            messageDiv.style.display = 'block';
            
            if (type === 'success') {
                messageDiv.style.background = '#d4edda';
                messageDiv.style.color = '#155724';
                messageDiv.style.borderLeft = '4px solid #28a745';
            } else {
                messageDiv.style.background = '#fef2f2';
                messageDiv.style.color = '#c0392b';
                messageDiv.style.borderLeft = '4px solid #e74c3c';
            }
        }
    </script>
</body>
</html>


