<?php
session_start();
if(!isset($_SESSION['vendedor_id'])) {
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['erro' => 'Dados inválidos']);
    exit;
}

// Define o caminho base e específico do vendedor
$baseSettingsPath = __DIR__ . '/../../../config/settings_vendedor.json';
$vendedorSettingsPath = __DIR__ . '/../../../config/settings_vendedor_' . $_SESSION['vendedor_id'] . '.json';

// Sanitize: ensure it's an array/object
if (!is_array($data) && !is_object($data)) {
    echo json_encode(['erro' => 'Formato inválido']);
    exit;
}

// Carregar configurações padrão
$defaultSettings = [];
if (file_exists($baseSettingsPath)) {
    $defaultSettings = json_decode(file_get_contents($baseSettingsPath), true) ?: [];
}

// Carregar configurações específicas do vendedor
$existing = [];
if (file_exists($vendedorSettingsPath)) {
    $existing = json_decode(file_get_contents($vendedorSettingsPath), true) ?: [];
}

// Server-side validation and sanitization
// Helper para obter valor enviado ou existente
function getv($arr, $k, $default=null){ return isset($arr[$k]) ? $arr[$k] : $default; }

// Valida email administrativo
if (isset($data['notificacoes']['email_admin'])) {
    if (!filter_var($data['notificacoes']['email_admin'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['erro'=>'E-mail administrati vo inválido']);
        exit;
    }
}

// Valida porta SMTP
if (isset($data['notificacoes']['smtp']['porta'])) {
    $p = $data['notificacoes']['smtp']['porta'];
    if (!is_numeric($p) || intval($p) <= 0) {
        echo json_encode(['erro'=>'Porta SMTP inválida']);
        exit;
    }
    $data['notificacoes']['smtp']['porta'] = intval($p);
}

// Validação simples de cores hex
if (isset($data['theme']['primary_color'])) {
    if (!preg_match('/^#([A-Fa-f0-9]{6})$/', $data['theme']['primary_color'])) {
        // aceitar e limpar qualquer outro valor
        $data['theme']['primary_color'] = '#012E40';
    }
}

// Se senha SMTP não foi enviada (campo vazio) mantém a existente
if (isset($data['notificacoes']['smtp']['senha']) && $data['notificacoes']['smtp']['senha'] === '') {
    if (isset($existing['notificacoes']['smtp']['senha'])) {
        $data['notificacoes']['smtp']['senha'] = $existing['notificacoes']['smtp']['senha'];
    }
}

// Normalizar booleans/flags (strings '0'/'1' -> booleans)
array_walk_recursive($data, function(&$v,$k){ if ($v==='0') $v=false; if ($v==='1') $v=true; });

$merged = array_replace_recursive($existing, $data);

// Salva apenas as configurações específicas do vendedor
$vendorSpecificSettings = [
    'theme' => $merged['theme'] ?? [],
    'store' => [
        'logo' => $merged['store']['logo'] ?? '',
        'favicon' => $merged['store']['favicon'] ?? ''
    ],
    'idiomas' => $merged['idiomas'] ?? []
];

$ok = @file_put_contents($vendedorSettingsPath, json_encode($vendorSpecificSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if ($ok === false) {
    echo json_encode(['erro' => 'Falha ao gravar ficheiro']);
    exit;
}

echo json_encode(['ok' => true, 'dados' => $merged]);
exit;
