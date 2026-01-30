<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// ✅ PROTEÇÃO: Verifica se admin está autenticado
if (empty($_SESSION['admin_id'])) {
    header('Location: login_adm.php');
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $senha     = trim($_POST['senha'] ?? '');
    $senha2    = trim($_POST['senha2'] ?? '');
    $fotoPath  = null;

    if ($nome === '' || $email === '' || $senha === '' || $senha2 === '') {
        $erro = 'Preenche todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido.';
    } elseif ($senha !== $senha2) {
        $erro = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        // Processa upload da foto (opcional)
        if (!empty($_FILES['foto']['name'])) {
            // Determinar a raíz do projeto
            $projectRoot = realpath(__DIR__ . '/../../../');
            $uploadDirFs = $projectRoot . '/uploads/admin/'; // caminho físico
            $uploadDirUrl = '/uploads/admin/';                 // caminho web

            if (!is_dir($uploadDirFs)) {
                if (!@mkdir($uploadDirFs, 0777, true)) {
                    $erro = 'Erro ao criar diretório de upload. Verifica as permissões.';
                }
            }
            
            if (empty($erro) && !is_writable($uploadDirFs)) {
                $erro = 'Pasta de upload sem permissão de escrita. Verifica as permissões de ./uploads/admin/';
            }

            if (empty($erro)) {
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array($ext, $permitidas, true)) {
                    $erro = 'Formato de imagem inválido. Usa JPG, PNG, GIF ou WEBP.';
                } else {
                    $novoNome = 'adm_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                    $destino  = $uploadDirFs . $novoNome;

                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                        $fotoPath = $uploadDirUrl . $novoNome;
                    } else {
                        $erro = 'Não foi possível guardar a imagem de perfil. Verifica as permissões da pasta.';
                    }
                }
            }
        }
    }

    if ($erro === '') {
        try {
            // Verificar se já existe administrador com este email
            $stmt = $conn->prepare("SELECT id FROM admin WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $erro = 'Já existe um administrador com este email.';
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO admin (nome, email, senha, foto_perfil) VALUES (:nome, :email, :senha, :foto)");
                $stmt->execute([
                    ':nome'  => $nome,
                    ':email' => $email,
                    ':senha' => $hash,
                    ':foto'  => $fotoPath,
                ]);

                $sucesso = 'Conta de administrador criada com sucesso. Já podes fazer login.';
                // Limpa os valores do formulário após sucesso
                $_POST = [];
            }
        } catch (Throwable $e) {
            $erro = 'Erro ao criar administrador. Verifica a tabela "admin" na base de dados.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta Administrador | Cantina IPM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top left, #013a63 0, #012E40 40%, #020b1f 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            color: #012E40;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 980px;
            padding: 1.5rem;
        }

        .auth-card {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 48px rgba(0, 0, 0, 0.45);
            background: #ffffff;
            border: none;
        }

        .auth-illustration {
            background: linear-gradient(145deg, rgba(1, 58, 99, 0.2), rgba(212, 175, 55, 0.16));
            backdrop-filter: blur(14px);
            padding: 2.75rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            color: #012E40;
        }

        .auth-illustration::before {
            content: "";
            position: absolute;
            inset: 13% 8%;
            border-radius: 22px;
            border: 1px dashed rgba(255, 255, 255, 0.65);
            opacity: 0.7;
            pointer-events: none;
        }

        .brand-mark {
            width: 60px;
            height: 60px;
            border-radius: 20px;
            background: radial-gradient(circle at 30% 15%, #ffeaa7, #D4AF37);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #012E40;
            font-weight: 800;
            font-size: 1.35rem;
            box-shadow: 0 12px 26px rgba(0, 0, 0, 0.2);
            margin-bottom: 1.75rem;
        }

        .brand-mark span {
            letter-spacing: 0.03em;
        }

        .auth-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
        }

        .auth-subtitle {
            color: #f8fafc;
            font-size: 0.98rem;
            opacity: 0.9;
        }

        .auth-highlight {
            margin-top: 1.75rem;
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.45rem 0.7rem;
            padding: 0.45rem 0.9rem;
            background: rgba(250, 250, 250, 0.06);
            border-radius: 999px;
            font-size: 0.82rem;
            color: #e5e7eb;
        }

        .auth-highlight strong {
            color: #facc15;
        }

        .auth-form-container {
            padding: 2.6rem 2.4rem 2.2rem;
        }

        .auth-form-header {
            margin-bottom: 1.75rem;
        }

        .auth-form-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #012E40;
            margin-bottom: 0.35rem;
        }

        .auth-form-subtitle {
            font-size: 0.92rem;
            color: #7a8699;
        }

        label {
            font-size: 0.88rem;
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 0.2rem;
        }

        .form-control {
            border-radius: 10px;
            border-color: #d0d7e2;
            padding: 0.72rem 0.9rem;
            font-size: 0.93rem;
            box-shadow: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .form-control:focus {
            border-color: #D4AF37;
            box-shadow: 0 0 0 0.12rem rgba(212, 175, 55, 0.4);
            background-color: #ffffff;
        }

        .btn-dourado {
            background: linear-gradient(135deg, #D4AF37, #f3d26a);
            color: #012E40;
            font-weight: 600;
            border: none;
            width: 100%;
            padding: 0.8rem 1.1rem;
            border-radius: 999px;
            font-size: 0.98rem;
            letter-spacing: 0.04em;
            box-shadow: 0 14px 26px rgba(0, 0, 0, 0.18);
            transition: transform 0.12s ease-out, box-shadow 0.12s ease-out, filter 0.12s ease-out;
        }

        .btn-dourado:hover {
            filter: brightness(1.04);
            transform: translateY(-1px);
            box-shadow: 0 18px 32px rgba(0, 0, 0, 0.22);
        }

        .btn-dourado:active {
            transform: translateY(0);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.18);
        }

        .auth-footer-text {
            font-size: 0.78rem;
            color: #9da5b3;
            margin-top: 1.6rem;
            text-align: center;
        }

        .auth-footer-text strong {
            color: #7c848f;
        }

        .alert {
            border-radius: 999px;
            font-size: 0.85rem;
            padding: 0.45rem 1rem;
        }

        .link-voltar {
            font-size: 0.85rem;
            margin-top: 0.9rem;
        }

        @media (max-width: 767.98px) {
            body {
                align-items: flex-start;
            }

            .auth-wrapper {
                padding-top: 2.4rem;
                padding-bottom: 2.4rem;
            }

            .auth-illustration {
                display: none;
            }

            .auth-form-container {
                padding: 2.1rem 1.8rem 1.9rem;
            }

            .auth-card {
                box-shadow: 0 14px 40px rgba(0, 0, 0, 0.5);
            }
        }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="card auth-card">
        <div class="row g-0">
            <div class="col-md-6 d-none d-md-flex auth-illustration">
                <div class="brand-mark">
                    <span>ADM</span>
                </div>
                <div>
                    <h1 class="auth-title">Primeiro Administrador</h1>
                    <p class="auth-subtitle mb-0">
                        Cria a conta principal de administração da Cantina IPM. Esta conta terá
                        permissões elevadas para gerir vendedores, clientes, produtos e relatórios.
                    </p>
                    <div class="auth-highlight mt-3">
                        <span><strong>Dica:</strong></span>
                        <span>Escolhe um email institucional e uma palavra-passe forte.</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="auth-form-container">
                    <div class="auth-form-header text-md-start text-center">
                        <h2 class="auth-form-title mb-0">Criar conta de administrador</h2>
                        <p class="auth-form-subtitle mb-0">
                            Esta conta será usada depois para criar e gerir vendedores.
                        </p>
                    </div>

                    <?php if ($erro): ?>
                        <div class="alert alert-danger text-center" role="alert">
                            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sucesso): ?>
                        <div class="alert alert-success text-center" role="alert">
                            <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" autocomplete="off" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="nome">Nome completo</label>
                            <input
                                type="text"
                                class="form-control"
                                id="nome"
                                name="nome"
                                placeholder="Ex.: Coordenação da Cantina"
                                required
                                value="<?= htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>
                        <div class="mb-3">
                            <label for="email">Email institucional</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                placeholder="admin@cantina.com"
                                required
                                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>
                        <div class="mb-3">
                            <label for="senha">Senha</label>
                            <input
                                type="password"
                                class="form-control"
                                id="senha"
                                name="senha"
                                placeholder="Mínimo 6 caracteres"
                                required
                            >
                        </div>
                        <div class="mb-3">
                            <label for="senha2">Confirmar senha</label>
                            <input
                                type="password"
                                class="form-control"
                                id="senha2"
                                name="senha2"
                                placeholder="Repete a senha"
                                required
                            >
                        </div>
                        <div class="mb-3">
                            <label for="foto">Foto de perfil (opcional)</label>
                            <input
                                type="file"
                                class="form-control"
                                id="foto"
                                name="foto"
                                accept="image/*"
                            >
                            <small class="text-muted">Formatos permitidos: JPG, PNG, GIF, WEBP.</small>
                        </div>
                        <button type="submit" class="btn btn-dourado mt-2">
                            Criar conta ADM
                        </button>
                    </form>

                    <p class="link-voltar text-center mb-0">
                        Já tens conta? <a href="login_adm.php" class="link-secondary">Voltar ao login</a>
                    </p>

                    <p class="auth-footer-text mb-0">
                        © <?php echo date('Y'); ?> <strong>Cantina IPM</strong>. Área Administrativa.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>


