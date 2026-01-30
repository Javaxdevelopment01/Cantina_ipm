<?php
session_start();
if (!isset($_SESSION['vendedor_id'])) {
    header('Location: login_vendedor.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

// --- Funções Auxiliares ---
function safe($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// --- Processamento de Formulário ---
$vendedorId = (int)($_SESSION['vendedor_id'] ?? 0);
$erro = '';
$sucesso = '';
$activeTab = 'profile'; // Tab ativa por padrão

// Carrega dados atuais
try {
    // Tabela correta confirmada via login_vendedor.php: 'vendedor'
    $stmt = $conn->prepare("SELECT id, nome, email, imagem as foto_perfil, senha FROM vendedor WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $vendedorId]);
    $vendedor = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $vendedor = null;
    $erro = 'Erro ao carregar dados do perfil: ' . $e->getMessage();
}

// Processa POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $vendedor) {
    $activeTab = $_POST['active_tab'] ?? 'profile';
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        // --- Atualizar Perfil ---
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? ''); // Se permitir mudar email
        
        // Upload Foto
        $fotoPath = $vendedor['foto_perfil'];
        if (!empty($_FILES['foto']['name'])) {
            $uploadDirFs = __DIR__ . '/../../../uploads/vendedores/';
            $uploadDirUrl = '/uploads/vendedores/';
            if (!is_dir($uploadDirFs)) @mkdir($uploadDirFs, 0777, true);
            
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($ext, $permitidas)) {
                $erro = 'Formato de imagem inválido.';
            } else {
                $novoNome = time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDirFs . $novoNome)) {
                    $fotoPath = $uploadDirUrl . $novoNome;
                } else {
                    $erro = 'Erro ao salvar imagem.';
                }
            }
        }

        if (!$erro) {
            try {
                // Tabela 'vendedor'
                $stmt = $conn->prepare("SELECT id FROM vendedor WHERE email = :email AND id <> :id");
                $stmt->execute([':email' => $email, ':id' => $vendedorId]);
                if ($stmt->fetch()) {
                    $erro = 'Email já está em uso.';
                } else {
                    $stmt = $conn->prepare("UPDATE vendedor SET nome = :nome, email = :email, imagem = :foto WHERE id = :id");
                    $stmt->execute([':nome' => $nome, ':email' => $email, ':foto' => $fotoPath, ':id' => $vendedorId]);
                    
                    $_SESSION['vendedor_nome'] = $nome;
                    $_SESSION['vendedor_foto'] = $fotoPath;
                    $sucesso = 'Perfil atualizado com sucesso!';
                    
                    $vendedor['nome'] = $nome;
                    $vendedor['email'] = $email;
                    $vendedor['foto_perfil'] = $fotoPath;
                }
            } catch (Exception $e) {
                $erro = 'Erro no banco de dados: ' . $e->getMessage();
            }
        }

    } elseif ($action === 'update_password') {
        // --- Atualizar Senha ---
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $senhaNova = $_POST['senha_nova'] ?? '';
        $senhaConf = $_POST['senha_confirma'] ?? '';

        if (!password_verify($senhaAtual, $vendedor['senha'])) {
            $erro = 'A senha atual está incorreta.';
        } elseif (strlen($senhaNova) < 6) {
            $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
        } elseif ($senhaNova !== $senhaConf) {
            $erro = 'A confirmação da senha não coincide.';
        } else {
            $hash = password_hash($senhaNova, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE vendedor SET senha = :senha WHERE id = :id");
            $stmt->execute([':senha' => $hash, ':id' => $vendedorId]);
            
            $sucesso = 'Senha alterada com sucesso!';
            $vendedor['senha'] = $hash; 
        }

    } elseif ($action === 'update_preferences') {
        // --- Atualizar Preferências (JSON) ---
        $prefsDir = __DIR__ . '/../../../config/settings_vendedor_'. $vendedorId .'.json';
        
        // Se usar arquivo unico global settings_vendedor.json (legado), vamos migrar para por usuario ou manter compatibilidade? 
        // O arquivo anterior usava `config/settings_vendedor.json`. 
        // Idealmente cada vendedor tem o seu. Vou usar sufixo ID.
        
        $prefs = [
            'theme' => [
                'mode' => $_POST['theme_mode'] ?? 'light',
                'primary_color' => $_POST['primary_color'] ?? '#012E40',
                'secondary_color' => $_POST['secondary_color'] ?? '#D4AF37'
            ],
            'sound_orders' => isset($_POST['sound_orders']),
            'show_stock_alert' => isset($_POST['show_stock_alert']),
            'auto_print' => isset($_POST['auto_print'])
        ];
        
        file_put_contents($prefsDir, json_encode($prefs));
        $sucesso = 'Preferências salvas com sucesso.';
    }
    // Se for AJAX, retorna JSON
    if (isset($_POST['is_ajax'])) {
        header('Content-Type: application/json');
        if ($erro) {
            echo json_encode(['success' => false, 'error' => $erro]);
        } else {
            echo json_encode(['success' => true, 'message' => $sucesso]);
        }
        exit;
    }
}

// Carregar Preferências
$prefsFile = __DIR__ . '/../../../config/settings_vendedor_'. $vendedorId .'.json';
// Fallback para arquivo global se o individual não existir (migração)
if (!file_exists($prefsFile)) {
    $globalPrefs = __DIR__ . '/../../../config/settings_vendedor.json';
    if(file_exists($globalPrefs)) copy($globalPrefs, $prefsFile);
}

$prefs = [];
if (file_exists($prefsFile)) {
    $prefs = json_decode(file_get_contents($prefsFile), true);
}

// Defaults
$themeMode = $prefs['theme']['mode'] ?? 'light';
$primaryColor = $prefs['theme']['primary_color'] ?? '#012E40';
$secondaryColor = $prefs['theme']['secondary_color'] ?? '#D4AF37';
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Definições - Vendedor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: <?= $primaryColor ?>;
            --primary-hover: <?= $primaryColor ?>cc;
            --secondary-color: <?= $secondaryColor ?>;
            --bg-color: <?= ($themeMode == 'light' ? '#f3f4f6' : '#1e1e1e') ?>;
            --card-bg: <?= ($themeMode == 'light' ? '#ffffff' : '#2c2c2c') ?>;
            --text-main: <?= ($themeMode == 'light' ? '#0f172a' : '#f1f1f1') ?>;
            --text-muted: <?= ($themeMode == 'light' ? '#64748b' : '#a0a0a0') ?>;
            --border-color: <?= ($themeMode == 'light' ? '#e2e8f0' : '#444') ?>;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--text-main);
            transition: background 0.3s, color 0.3s;
        }

        .main-page {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .main-page {
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
            background-color: var(--bg-color);
            color: var(--primary-color);
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        /* Cards */
        .settings-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            width: 100%;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-header-custom {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: var(--text-main);
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
            border: 4px solid var(--card-bg);
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
            border: 2px solid var(--card-bg);
            transition: transform 0.2s;
        }
        .profile-photo-edit:hover {
            transform: scale(1.1);
        }

        /* Forms & Inputs */
        input, select {
            background-color: var(--bg-color);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }
        input:focus, select:focus {
            background-color: var(--card-bg);
            color: var(--text-main);
        }

        .btn-primary-custom {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
        }
        .btn-primary-custom:hover {
            opacity: 0.9;
            color: white;
        }

        /* Password Strength */
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.5rem;
            background: var(--border-color);
            overflow: hidden;
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

<?php include __DIR__ . '/includes/menu_vendedor.php'; ?>

<main class="main-page">
    <div class="page-header">
        <h1 class="page-title">Definições</h1>
        <p class="page-subtitle">Personalize sua experiência de venda e gerencie sua conta.</p>
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
            <div class="nav flex-column nav-pills" id="settings-tab" role="tablist">
                <button class="nav-link <?= $activeTab === 'profile' ? 'active' : '' ?>" 
                        data-bs-toggle="pill" data-bs-target="#profile" 
                        type="button" onclick="setActiveTab('profile')">
                    <i class="fa-regular fa-user"></i> Meu Perfil
                </button>
                <button class="nav-link <?= $activeTab === 'security' ? 'active' : '' ?>" 
                        data-bs-toggle="pill" data-bs-target="#security" 
                        type="button" onclick="setActiveTab('security')">
                    <i class="fa-solid fa-shield-halved"></i> Segurança
                </button>
                <button class="nav-link <?= $activeTab === 'preferences' ? 'active' : '' ?>" 
                        data-bs-toggle="pill" data-bs-target="#preferences" 
                        type="button" onclick="setActiveTab('preferences')">
                    <i class="fa-solid fa-sliders"></i> Preferências
                </button>
            </div>
            
            <div class="mt-4 pt-4 border-top border-secondary text-center">
                <small class="text-muted d-block mb-2">Cantina IPM v2.1</small>
                <a href="../logout_vendedor.php" class="btn btn-outline-danger btn-sm w-100">
                    <i class="fa-solid fa-right-from-bracket me-2"></i>Sair
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="settings-content tab-content w-100">
            
            <!-- Tab: Perfil -->
            <div class="tab-pane fade <?= $activeTab === 'profile' ? 'show active' : '' ?>" id="profile">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h2 class="card-title">Informações Pessoais</h2>
                        <p class="text-muted small m-0">Atualize sua foto e dados de contato</p>
                    </div>

                    <?php if ($vendedor): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="active_tab" value="profile">

                        <div class="profile-photo-container">
                            <?php 
                                $fotoDB = $vendedor['foto_perfil'] ?? $vendedor['imagem'] ?? null;
                                $fotoUrl = 'https://ui-avatars.com/api/?name='.urlencode($vendedor['nome'] ?? 'Vendedor').'&background=random&color=fff';
                                
                                if ($fotoDB) {
                                    if (strpos($fotoDB, 'http') === 0) {
                                        $fotoUrl = $fotoDB;
                                    } else {
                                        // Limpa barras iniciais
                                        $cleanPath = ltrim($fotoDB, '/');
                                        
                                        // Caminhos físicos para verificar
                                        $baseDir = __DIR__ . '/../../../';
                                        
                                        // Cenário 1: É apenas o nome do arquivo (ex: "img.jpg")
                                        if (basename($cleanPath) === $cleanPath) {
                                            if (file_exists($baseDir . 'app/uploads/vendedores/' . $cleanPath)) {
                                                $fotoUrl = '../../uploads/vendedores/' . $cleanPath;
                                            } elseif (file_exists($baseDir . 'uploads/vendedores/' . $cleanPath)) {
                                                $fotoUrl = '../../../uploads/vendedores/' . $cleanPath;
                                            }
                                        } 
                                        // Cenário 2: Caminho relativo (ex: "uploads/vendedores/img.jpg")
                                        elseif (file_exists($baseDir . $cleanPath)) {
                                            // Se começa com uploads/
                                            if (strpos($cleanPath, 'uploads/') === 0) {
                                                $fotoUrl = '../../../' . $cleanPath;
                                            } 
                                            // Se começa com app/uploads/
                                            elseif (strpos($cleanPath, 'app/uploads/') === 0) {
                                                $fotoUrl = '../../' . substr($cleanPath, 4); // remove app/
                                            }
                                            // Outros casos: tenta relativo à raiz
                                            else {
                                                $fotoUrl = '../../../' . $cleanPath;
                                            }
                                        }
                                        // Cenário 3: Assume caminho absoluto do site se não encontrado arquivo físico
                                        else {
                                            $fotoUrl = '/' . $cleanPath;
                                            // Tenta corrigir se for caminho que precisa de cantina_ipm
                                            if (strpos($_SERVER['REQUEST_URI'], '/cantina_ipm/') !== false && strpos($fotoUrl, '/cantina_ipm/') === false) {
                                                $fotoUrl = '/cantina_ipm' . $fotoUrl;
                                            }
                                        }
                                    }
                                }
                            ?>
                            <img src="<?= safe($fotoUrl) ?>" alt="Foto de Perfil" class="profile-photo" id="preview-img">
                            <label for="foto-upload" class="profile-photo-edit" title="Alterar foto">
                                <i class="fa-solid fa-camera"></i>
                            </label>
                            <input type="file" id="foto-upload" name="foto" hidden accept="image/*" onchange="previewImage(this)">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" name="nome" value="<?= safe($vendedor['nome'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?= safe($vendedor['email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Salvar Alterações
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-warning">Não foi possível carregar os dados do vendedor.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab: Segurança -->
            <div class="tab-pane fade <?= $activeTab === 'security' ? 'show active' : '' ?>" id="security">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h2 class="card-title">Alterar Senha</h2>
                        <p class="text-muted small m-0">Mantenha sua conta segura</p>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_password">
                        <input type="hidden" name="active_tab" value="security">

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" name="senha_atual" required>
                            </div>
                        </div>

                        <hr class="my-4 text-muted opacity-25">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" name="senha_nova" required oninput="checkStrength(this.value)">
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="strength-bar"></div>
                                </div>
                                <div class="strength-text text-muted" id="strength-text">Força</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" name="senha_confirma" required>
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

            <!-- Tab: Preferências -->
            <div class="tab-pane fade <?= $activeTab === 'preferences' ? 'show active' : '' ?>" id="preferences">
                <div class="settings-card">
                    <div class="card-header-custom">
                        <h2 class="card-title">Preferências do Sistema</h2>
                        <p class="text-muted small m-0">Personalize o visual e alertas da sua loja</p>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_preferences">
                        <input type="hidden" name="active_tab" value="preferences">

                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Modo Visual</label>
                                <select class="form-select" name="theme_mode">
                                    <option value="light" <?= ($prefs['theme']['mode'] ?? '') === 'light' ? 'selected' : '' ?>>Claro (Light)</option>
                                    <option value="dark" <?= ($prefs['theme']['mode'] ?? '') === 'dark' ? 'selected' : '' ?>>Escuro (Dark)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cor Primária</label>
                                <input type="color" class="form-control form-control-color w-100" name="primary_color" 
                                       value="<?= $primaryColor ?>" title="Escolha a cor principal">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cor Secundária</label>
                                <input type="color" class="form-control form-control-color w-100" name="secondary_color" 
                                       value="<?= $secondaryColor ?>" title="Escolha a cor secundária">
                            </div>
                        </div>

                        <div class="list-group list-group-flush border rounded-3">
                            <div class="list-group-item d-flex justify-content-between align-items-center p-3 bg-transparent">
                                <div>
                                    <h6 class="mb-1 text-main">Sons de Pedido</h6>
                                    <p class="text-muted small m-0">Tocar alerta sonoro ao receber novos pedidos</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="sound_orders" <?= !empty($prefs['sound_orders']) ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <div class="list-group-item d-flex justify-content-between align-items-center p-3 bg-transparent">
                                <div>
                                    <h6 class="mb-1 text-main">Alertas de Stock</h6>
                                    <p class="text-muted small m-0">Mostrar aviso visual quando produtos estiverem acabando</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="show_stock_alert" <?= !empty($prefs['show_stock_alert']) ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <div class="list-group-item d-flex justify-content-between align-items-center p-3 bg-transparent">
                                <div>
                                    <h6 class="mb-1 text-main">Impressão Automática</h6>
                                    <p class="text-muted small m-0">Abrir janela de impressão automaticamente ao confirmar pedido</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="auto_print" <?= !empty($prefs['auto_print']) ? 'checked' : '' ?>>
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
            
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Preview Imagem
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-img').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Set Active Tab
    function setActiveTab(tabName) {
        const inputs = document.querySelectorAll('input[name="active_tab"]');
        inputs.forEach(input => input.value = tabName);
    }

    function checkStrength(password) {
        const bar = document.getElementById('strength-bar');
        const text = document.getElementById('strength-text');
        
        let strength = 0;
        if (password.length > 5) strength += 20;
        if (password.length > 8) strength += 20;
        if (/[A-Z]/.test(password)) strength += 20;
        if (/[0-9]/.test(password)) strength += 20;
        if (/[^A-Za-z0-9]/.test(password)) strength += 20;

        if (bar) bar.style.width = strength + '%';
        
        if (strength < 40) {
            if (bar) bar.style.backgroundColor = '#ef4444';
            if (text) { text.textContent = 'Fraca'; text.style.color = '#ef4444'; }
        } else if (strength < 80) {
            if (bar) bar.style.backgroundColor = '#f59e0b';
            if (text) { text.textContent = 'Média'; text.style.color = '#f59e0b'; }
        } else {
            if (bar) bar.style.backgroundColor = '#10b981';
            if (text) { text.textContent = 'Forte'; text.style.color = '#10b981'; }
        }
    }

    // AJAX Form Submission
    document.querySelectorAll('form').forEach(form => {
        form.onsubmit = async function(e) {
            // Se o form contiver arquvivo, mas quisermos AJAX puro, usamos FormData
            e.preventDefault();
            
            const btn = this.querySelector('button[type="submit"]');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Processando...';
            
            const formData = new FormData(this);
            formData.append('is_ajax', '1');
            
            try {
                const resp = await fetch('definicoes_vendedor.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await resp.json();
                
                if (res.success) {
                    showToast(res.message);
                    // Se houver troca de foto, atualizar no menu (se existir)
                    if (formData.get('action') === 'update_profile') {
                        // Opcional: recarregar página após delay para atualizar menu
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    showToast(res.error, 'danger');
                }
            } catch (err) {
                showToast('Erro de conexão.', 'danger');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        };
    });

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white; padding: 12px 24px; border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            font-weight: 600; animation: slideIn 0.3s ease-out;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
    `;
    document.head.appendChild(style);
</script>

</body>
</html>
