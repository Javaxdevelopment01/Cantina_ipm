<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_adm.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

// --- Funções Auxiliares ---
function safe($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function getSystemInfo() {
    $path = __DIR__ . '/../../../'; // Raiz do projeto
    $totalSpace = @disk_total_space($path);
    $freeSpace = @disk_free_space($path);
    $usedSpace = $totalSpace - $freeSpace;
    
    return [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'],
        'database_size' => 'Unknown', // Difícil calcular exato sem query pesada
        'disk_total' => $totalSpace ? formatBytes($totalSpace) : 'N/A',
        'disk_free' => $freeSpace ? formatBytes($freeSpace) : 'N/A',
        'disk_used' => $totalSpace ? formatBytes($usedSpace) : 'N/A',
        'disk_percent' => $totalSpace ? round(($usedSpace / $totalSpace) * 100) : 0
    ];
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// --- Processamento de Formulário ---
$adminId = (int)($_SESSION['admin_id'] ?? 0);
$erro = '';
$sucesso = '';
$activeTab = 'profile'; // Tab ativa por padrão

// Carrega dados atuais
try {
    $stmt = $conn->prepare("SELECT id, nome, email, foto_perfil, senha FROM admin WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $admin = null;
    $erro = 'Erro ao carregar dados.';
}

// Processa POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin) {
    $activeTab = $_POST['active_tab'] ?? 'profile';
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        // --- Atualizar Perfil ---
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Upload Foto
        $fotoPath = $admin['foto_perfil'];
        if (!empty($_FILES['foto']['name'])) {
            $uploadDirFs = __DIR__ . '/../../../uploads/admin/';
            $uploadDirUrl = '/uploads/admin/';
            if (!is_dir($uploadDirFs)) @mkdir($uploadDirFs, 0777, true);
            
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($ext, $permitidas)) {
                $erro = 'Formato de imagem inválido.';
            } else {
                $novoNome = 'adm_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDirFs . $novoNome)) {
                    $fotoPath = $uploadDirUrl . $novoNome;
                } else {
                    $erro = 'Erro ao salvar imagem.';
                }
            }
        }

        if (!$erro) {
            try {
                // Verifica email duplicado
                $stmt = $conn->prepare("SELECT id FROM admin WHERE email = :email AND id <> :id");
                $stmt->execute([':email' => $email, ':id' => $adminId]);
                if ($stmt->fetch()) {
                    $erro = 'Email já está em uso.';
                } else {
                    $stmt = $conn->prepare("UPDATE admin SET nome = :nome, email = :email, foto_perfil = :foto WHERE id = :id");
                    $stmt->execute([':nome' => $nome, ':email' => $email, ':foto' => $fotoPath, ':id' => $adminId]);
                    
                    $_SESSION['admin_nome'] = $nome;
                    $_SESSION['admin_foto'] = $fotoPath;
                    $sucesso = 'Perfil atualizado com sucesso!';
                    
                    // Atualiza dados locais
                    $admin['nome'] = $nome;
                    $admin['email'] = $email;
                    $admin['foto_perfil'] = $fotoPath;
                }
            } catch (Exception $e) {
                $erro = 'Erro no banco de dados.';
            }
        }

    } elseif ($action === 'update_password') {
        // --- Atualizar Senha ---
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $senhaNova = $_POST['senha_nova'] ?? '';
        $senhaConf = $_POST['senha_confirma'] ?? '';

        if (!password_verify($senhaAtual, $admin['senha'])) {
            $erro = 'A senha atual está incorreta.';
        } elseif (strlen($senhaNova) < 6) {
            $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
        } elseif ($senhaNova !== $senhaConf) {
            $erro = 'A confirmação da senha não coincide.';
        } else {
            $hash = password_hash($senhaNova, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin SET senha = :senha WHERE id = :id");
            $stmt->execute([':senha' => $hash, ':id' => $adminId]);
            $sucesso = 'Senha alterada com sucesso!';
            // Atualiza hash local para evitar erro se tentar mudar de novo sem reload
            $admin['senha'] = $hash; 
        }

    } elseif ($action === 'update_notifications') {
        // --- Atualizar Notificações (Simulado em JSON num arquivo ou coluna extra) ---
        // Por simplificação, vamos salvar num ficheiro JSON por user ou uma coluna se existisse
        // Vamos criar um ficheiro preferences_admin_ID.json na pasta config/prefs/ se não existir DB support
        $prefsDir = __DIR__ . '/../../../config/prefs/';
        if (!is_dir($prefsDir)) @mkdir($prefsDir, 0777, true);
        
        $prefs = [
            'email_orders' => isset($_POST['email_orders']),
            'low_stock_alerts' => isset($_POST['low_stock_alerts']),
            'weekly_reports' => isset($_POST['weekly_reports']),
            'sound_effects' => isset($_POST['sound_effects'])
        ];
        
        file_put_contents($prefsDir . "admin_{$adminId}.json", json_encode($prefs));
        $sucesso = 'Preferências de notificação salvas.';
    }
}

// Carregar Preferências
$prefs = [];
$prefsFile = __DIR__ . "/../../../config/prefs/admin_{$adminId}.json";
if (file_exists($prefsFile)) {
    $prefs = json_decode(file_get_contents($prefsFile), true);
}
// Defaults
$prefs = array_merge([
    'email_orders' => true,
    'low_stock_alerts' => true,
    'weekly_reports' => false,
    'sound_effects' => true
], $prefs ?? []);

$sysInfo = getSystemInfo();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Definições - Adm | Cantina IPM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #012E40;
            --primary-hover: #02435B;
            --secondary-color: #0FB134;
            --bg-color: #f3f4f6;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--text-main);
        }

        .main-adm-page {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .main-adm-page {
                margin-left: 0;
                padding: 1rem;
            }
        }

        /* Header */
        .page-header {
            margin-bottom: 2rem;
        }
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }
        .page-subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        /* Settings Container */
        .settings-container {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }

        @media (max-width: 992px) {
            .settings-container {
                flex-direction: column;
            }
        }

        /* Sidebar Tabs */
        .settings-sidebar {
            width: 280px;
            flex-shrink: 0;
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .nav-pills .nav-link {
            color: var(--text-muted);
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
        }

        .nav-pills .nav-link:hover {
            background-color: #f1f5f9;
            color: var(--primary-color);
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(1, 46, 64, 0.2);
        }

        .nav-pills .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* Content Area */
        .settings-content {
            flex-grow: 1;
            width: 100%;
        }

        .settings-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-header-custom {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        /* Profile Photo */
        .profile-photo-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .profile-photo-edit {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--primary-color);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: transform 0.2s;
            border: 2px solid white;
        }

        .profile-photo-edit:hover {
            transform: scale(1.1);
            background: var(--primary-hover);
        }

        /* Forms */
        .form-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-main);
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border-color: #e2e8f0;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(1, 46, 64, 0.1);
        }

        .btn-primary-custom {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-primary-custom:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        /* Toggles */
        .form-check-input {
            width: 3em;
            height: 1.5em;
            cursor: pointer;
        }
        .form-check-input:checked {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        /* System Info */
        .system-stat {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            height: 100%;
            transition: transform 0.2s;
        }
        .system-stat:hover {
            transform: translateY(-2px);
            background: #f1f5f9;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0.5rem 0 0.25rem;
        }
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        .stat-icon {
            font-size: 1.25rem;
            color: #94a3b8;
        }

        /* Password Strength */
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.5rem;
            background: #e2e8f0;
            overflow: hidden;
            transition: all 0.3s;
        }
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background-color 0.3s;
        }
        .strength-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            text-align: right;
            font-weight: 500;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/menu_adm.php'; ?>

<main class="main-adm-page">
    <div class="page-header">
        <h1 class="page-title">Definições</h1>
        <p class="page-subtitle">Gerencie suas preferências, perfil e configurações do sistema.</p>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?= safe($erro); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= safe($sucesso); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="settings-container">
        <!-- Sidebar Navigation -->
        <div class="settings-sidebar">
            <div class="nav flex-column nav-pills me-3" id="settings-tab" role="tablist">
                <button class="nav-link <?= $activeTab === 'profile' ? 'active' : '' ?>" 
                        id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" 
                        type="button" role="tab" onclick="setActiveTab('profile')">
                    <i class="fa-regular fa-user"></i> Meu Perfil
                </button>
                <button class="nav-link <?= $activeTab === 'security' ? 'active' : '' ?>" 
                        id="security-tab" data-bs-toggle="pill" data-bs-target="#security" 
                        type="button" role="tab" onclick="setActiveTab('security')">
                    <i class="fa-solid fa-shield-halved"></i> Segurança
                </button>
                <button class="nav-link <?= $activeTab === 'notifications' ? 'active' : '' ?>" 
                        id="notifications-tab" data-bs-toggle="pill" data-bs-target="#notifications" 
                        type="button" role="tab" onclick="setActiveTab('notifications')">
                    <i class="fa-regular fa-bell"></i> Notificações
                </button>
                <button class="nav-link <?= $activeTab === 'system' ? 'active' : '' ?>" 
                        id="system-tab" data-bs-toggle="pill" data-bs-target="#system" 
                        type="button" role="tab" onclick="setActiveTab('system')">
                    <i class="fa-solid fa-server"></i> Sistema
                </button>
            </div>
            
            <div class="mt-4 pt-4 border-top text-center">
                <small class="text-muted d-block mb-2">Versão 2.1.0</small>
                <a href="backup_adm.php" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="fa-solid fa-database me-2"></i>Gerir Backups
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="settings-content tab-content" id="settings-tabContent">
            
            <!-- Tab: Perfil -->
            <div class="tab-pane fade <?= $activeTab === 'profile' ? 'show active' : '' ?>" 
                 id="profile" role="tabpanel">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h2 class="card-title">Informações Pessoais</h2>
                        <p class="text-muted small m-0">Atualize sua foto e dados de identificação</p>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="active_tab" value="profile">

                        <div class="profile-photo-container">
                            <?php 
                                $fotoUrl = !empty($admin['foto_perfil']) ? safe($admin['foto_perfil']) : 'https://ui-avatars.com/api/?name='.urlencode($admin['nome']).'&background=012E40&color=fff';
                            ?>
                            <img src="<?= $fotoUrl ?>" alt="Foto de Perfil" class="profile-photo" id="preview-img">
                            <label for="foto-upload" class="profile-photo-edit" title="Alterar foto">
                                <i class="fa-solid fa-camera"></i>
                            </label>
                            <input type="file" id="foto-upload" name="foto" hidden accept="image/*" onchange="previewImage(this)">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" name="nome" value="<?= safe($admin['nome']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?= safe($admin['email']) ?>" required>
                            </div>
                            <!-- (Opcional) Telefone, cargo, etc podem ser adicionados aqui -->
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab: Segurança -->
            <div class="tab-pane fade <?= $activeTab === 'security' ? 'show active' : '' ?>" 
                 id="security" role="tabpanel">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h2 class="card-title">Segurança da Conta</h2>
                        <p class="text-muted small m-0">Mantenha sua conta segura alterando a senha regularmente</p>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_password">
                        <input type="hidden" name="active_tab" value="security">

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" name="senha_atual" required placeholder="Digite sua senha atual para confirmar">
                            </div>
                        </div>

                        <hr class="my-4 text-muted opacity-25">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" name="senha_nova" id="new-passwd" required 
                                       oninput="checkStrength(this.value)" placeholder="Mínimo 6 caracteres">
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="strength-bar"></div>
                                </div>
                                <div class="strength-text text-muted" id="strength-text">Força da senha</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" name="senha_confirma" required placeholder="Repita a nova senha">
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fa-solid fa-key me-2"></i>Atualizar Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab: Notificações -->
            <div class="tab-pane fade <?= $activeTab === 'notifications' ? 'show active' : '' ?>" 
                 id="notifications" role="tabpanel">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h2 class="card-title">Preferências de Notificação</h2>
                        <p class="text-muted small m-0">Escolha como e quando você quer ser notificado</p>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_notifications">
                        <input type="hidden" name="active_tab" value="notifications">

                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Novos Pedidos</h6>
                                    <p class="text-muted small m-0">Receber alertas quando um novo pedido chegar.</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="email_orders" <?= $prefs['email_orders'] ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Alerta de Stock Baixo</h6>
                                    <p class="text-muted small m-0">Notificar quando produtos estiverem acabando.</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="low_stock_alerts" <?= $prefs['low_stock_alerts'] ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Relatórios Semanais</h6>
                                    <p class="text-muted small m-0">Receber resumo de vendas por email toda segunda-feira.</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="weekly_reports" <?= $prefs['weekly_reports'] ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Efeitos Sonoros</h6>
                                    <p class="text-muted small m-0">Tocar som ao receber notificações no painel.</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="sound_effects" <?= $prefs['sound_effects'] ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fa-solid fa-check me-2"></i>Salvar Preferências
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab: Sistema -->
            <div class="tab-pane fade <?= $activeTab === 'system' ? 'show active' : '' ?>" 
                 id="system" role="tabpanel">
                <div class="settings-card mb-4">
                    <div class="card-header-custom">
                        <h2 class="card-title">Saúde do Sistema</h2>
                        <p class="text-muted small m-0">Status e informações técnicas do servidor</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="system-stat">
                                <i class="fa-brands fa-php stat-icon"></i>
                                <div class="stat-value"><?= safe($sysInfo['php_version']) ?></div>
                                <div class="stat-label">Versão do PHP</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="system-stat">
                                <i class="fa-solid fa-hard-drive stat-icon"></i>
                                <div class="stat-value"><?= safe($sysInfo['disk_free']) ?></div>
                                <div class="stat-label">Espaço Livre</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="system-stat">
                                <i class="fa-solid fa-server stat-icon"></i>
                                <div class="stat-value text-truncate" title="<?= safe($sysInfo['server_software']) ?>">Apache/WAMP</div>
                                <div class="stat-label">Servidor Web</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label mb-2">Uso do Disco (Projeto)</label>
                        <div class="progress" style="height: 10px; border-radius: 5px;">
                            <div class="progress-bar bg-info" role="progressbar" 
                                 style="width: <?= $sysInfo['disk_percent'] ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mt-1">
                            <span>Usado: <?= $sysInfo['disk_used'] ?></span>
                            <span>Total: <?= $sysInfo['disk_total'] ?></span>
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <h3 class="card-title mb-3 text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Zona de Perigo</h3>
                    <p class="text-muted small">Ações irreversíveis ou que afetam o funcionamento do sistema.</p>
                    
                    <div class="d-flex gap-3 flex-wrap">
                        <button class="btn btn-outline-danger" onclick="confirmAction('limpar cache')">
                            <i class="fa-solid fa-broom me-2"></i>Limpar Cache
                        </button>
                        <button class="btn btn-outline-danger" onclick="confirmAction('resetar logs')">
                            <i class="fa-solid fa-file-circle-xmark me-2"></i>Apagar Logs
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Preview de Imagem
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-img').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Persistir Tab Ativa no Form
    function setActiveTab(tabName) {
        const inputs = document.querySelectorAll('input[name="active_tab"]');
        inputs.forEach(input => input.value = tabName);
    }

    // Confirmar Ações
    function confirmAction(action) {
        if(confirm(`Tem certeza que deseja ${action}?`)) {
            alert('Ação simulada com sucesso!');
        }
    }

    // Indicador de Força de Senha
    function checkStrength(password) {
        const bar = document.getElementById('strength-bar');
        const text = document.getElementById('strength-text');
        
        let strength = 0;
        if (password.length > 5) strength += 20;
        if (password.length > 8) strength += 20;
        if (/[A-Z]/.test(password)) strength += 20;
        if (/[0-9]/.test(password)) strength += 20;
        if (/[^A-Za-z0-9]/.test(password)) strength += 20;

        bar.style.width = strength + '%';
        
        if (strength < 40) {
            bar.style.backgroundColor = '#ef4444'; // Red
            text.textContent = 'Senha Fraca';
            text.style.color = '#ef4444';
        } else if (strength < 80) {
            bar.style.backgroundColor = '#f59e0b'; // Orange
            text.textContent = 'Senha Média';
            text.style.color = '#f59e0b';
        } else {
            bar.style.backgroundColor = '#10b981'; // Green
            text.textContent = 'Senha Forte';
            text.style.color = '#10b981';
        }
    }
</script>

</body>
</html>
