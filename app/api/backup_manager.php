<?php
// backup_manager.php - Sistema de backup completo com opções seletivas
session_start();

// Evita que erros PHP saiam como HTML e quebrem o JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

set_exception_handler(function ($e) {
    error_log('BACKUP_MANAGER EXCEPTION: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erro interno no backup: ' . $e->getMessage(),
    ]);
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    error_log("BACKUP_MANAGER ERROR: [$severity] $message in $file:$line");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erro interno no backup (detalhes em error_log).',
    ]);
    exit;
});

// Permite backup apenas a partir de sessão de administrador
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Carrega configuração da BD
require_once __DIR__ . '/../../config/database.php';

// Recebe opções do POST
$includeDatabase = isset($_POST['include_database']) && $_POST['include_database'] === 'true';
$includeUploads = isset($_POST['include_uploads']) && $_POST['include_uploads'] === 'true';
$includeReceipts = isset($_POST['include_receipts']) && $_POST['include_receipts'] === 'true';
$includeProductImages = isset($_POST['include_product_images']) && $_POST['include_product_images'] === 'true';

// Se nada foi selecionado, retorna erro
if (!$includeDatabase && !$includeUploads && !$includeReceipts && !$includeProductImages) {
    echo json_encode(['success' => false, 'error' => 'Selecione pelo menos uma opção para o backup']);
    exit;
}

$backupRoot = realpath(__DIR__ . '/../..'); // raiz do projeto (cantina_ipm)
$backupDir  = realpath(__DIR__ . '/../..') . '/backups/';

if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true)) {
    echo json_encode(['success' => false, 'error' => 'Não foi possível criar a pasta de backups']);
    exit;
}

$timestamp = date('Ymd_His');
$tempDir = $backupDir . 'temp_' . $timestamp . '/';

// Cria diretório temporário
if (!mkdir($tempDir, 0755, true)) {
    echo json_encode(['success' => false, 'error' => 'Não foi possível criar diretório temporário']);
    exit;
}

$filesAdded = [];

// 1) BACKUP DA BASE DE DADOS
if ($includeDatabase) {
    $dbDumpFile = $tempDir . "database/ipm_cantina_{$timestamp}.sql";
    
    // Cria pasta database
    if (!is_dir($tempDir . 'database/')) {
        mkdir($tempDir . 'database/', 0755, true);
    }
    
    // Caminho do mysqldump
    $mysqldumpPath = null;
    
    if (stripos(PHP_OS, 'WIN') === 0) {
        $candidates = [
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.30\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.29\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.3.0\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.2.0\\bin\\mysqldump.exe',
        ];
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $mysqldumpPath = $path;
                break;
            }
        }
        if ($mysqldumpPath === null) {
            $mysqldumpPath = 'mysqldump';
        }
    } else {
        $mysqldumpPath = 'mysqldump';
    }
    
    $cmdParts = [];
    $cmdParts[] = $mysqldumpPath;
    $cmdParts[] = '-h' . escapeshellarg($host);
    $cmdParts[] = '-u' . escapeshellarg($user);
    if ($pass !== '') {
        $cmdParts[] = '-p' . $pass;
    }
    $cmdParts[] = escapeshellarg($db);
    
    $cmd = implode(' ', $cmdParts) . ' > ' . escapeshellarg($dbDumpFile);
    
    if (stripos(PHP_OS, 'WIN') === 0) {
        $cmd = 'cmd /c ' . $cmd;
    }
    
    $output = [];
    $ret = 0;
    @exec($cmd, $output, $ret);
    
    if ($ret !== 0 || !file_exists($dbDumpFile) || filesize($dbDumpFile) === 0) {
        // Limpa diretório temporário
        deleteDirectory($tempDir);
        echo json_encode([
            'success' => false,
            'error'   => 'Falha ao executar mysqldump. Verifica se o mysqldump está no PATH do sistema.'
        ]);
        exit;
    }
    
    $filesAdded[] = 'Base de Dados (SQL)';
}

// 2) BACKUP DE UPLOADS (admin, vendedor, etc)
if ($includeUploads) {
    $uploadsSource = $backupRoot . '/uploads/';
    $uploadsTarget = $tempDir . 'uploads/';
    
    if (is_dir($uploadsSource)) {
        copyDirectory($uploadsSource, $uploadsTarget);
        $filesAdded[] = 'Uploads (Admin/Vendedor)';
    }
}

// 3) BACKUP DE IMAGENS DE PRODUTOS
if ($includeProductImages) {
    $productsSource = $backupRoot . '/uploads/produtos/';
    $productsTarget = $tempDir . 'uploads/produtos/';
    
    if (is_dir($productsSource)) {
        $result = copyDirectory($productsSource, $productsTarget);
        if ($result) {
            $filesAdded[] = 'Imagens de Produtos';
        }
    } else {
        error_log("BACKUP: Diretório de produtos não encontrado: $productsSource");
    }
}

// 4) BACKUP DE RECIBOS PDF
if ($includeReceipts) {
    $receiptsSource = $backupRoot . '/app/recibos/';
    $receiptsTarget = $tempDir . 'recibos/';
    
    // Tenta vários locais possíveis para recibos
    $possiblePaths = [
        $backupRoot . '/app/recibos/',
        $backupRoot . '/public/recibos/',
        $backupRoot . '/recibos/',
    ];
    
    $found = false;
    foreach ($possiblePaths as $path) {
        if (is_dir($path)) {
            $result = copyDirectory($path, $receiptsTarget);
            if ($result) {
                $filesAdded[] = 'Recibos PDF';
                $found = true;
            }
            break;
        }
    }
    
    if (!$found) {
        error_log("BACKUP: Diretório de recibos não encontrado em nenhum local");
    }
}

// 5) CRIAR ZIP COM TODO O CONTEÚDO
$zipName = "backup_{$timestamp}.zip";
$zipPath = $backupDir . $zipName;

if (!class_exists('ZipArchive')) {
    deleteDirectory($tempDir);
    echo json_encode(['success' => false, 'error' => 'Extensão ZIP do PHP não está habilitada.']);
    exit;
}

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    deleteDirectory($tempDir);
    echo json_encode(['success' => false, 'error' => 'Não foi possível criar o ficheiro ZIP de backup']);
    exit;
}

// Adiciona todos os ficheiros do diretório temporário ao ZIP
addDirectoryToZip($zip, $tempDir, '');

$zip->close();

// Limpa diretório temporário
deleteDirectory($tempDir);

// Verifica se o ZIP foi criado com sucesso
if (!file_exists($zipPath)) {
    echo json_encode([
        'success' => false,
        'error'   => 'O arquivo ZIP não foi criado corretamente.'
    ]);
    exit;
}

// Calcula tamanho do backup
$backupSize = filesize($zipPath);
$backupSizeMB = number_format($backupSize / 1024 / 1024, 2, ',', '.');

echo json_encode([
    'success' => true,
    'file'    => '/backups/' . $zipName,
    'size'    => $backupSizeMB . ' MB',
    'items'   => $filesAdded,
    'timestamp' => date('d/m/Y H:i:s')
]);
exit;

// ============ FUNÇÕES AUXILIARES ============

/**
 * Copia um diretório recursivamente
 */
function copyDirectory($source, $target) {
    // Verifica se o diretório de origem existe
    if (!is_dir($source)) {
        error_log("BACKUP: Diretório de origem não existe: $source");
        return false;
    }
    
    // Cria diretório de destino se não existir
    if (!is_dir($target)) {
        if (!mkdir($target, 0755, true)) {
            error_log("BACKUP: Não foi possível criar diretório de destino: $target");
            return false;
        }
    }
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $targetPath = $target . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                if (!copy($item, $targetPath)) {
                    error_log("BACKUP: Falha ao copiar arquivo: {$item} -> {$targetPath}");
                }
            }
        }
        return true;
    } catch (Exception $e) {
        error_log("BACKUP: Erro ao copiar diretório $source: " . $e->getMessage());
        return false;
    }
}

/**
 * Adiciona diretório ao ZIP recursivamente
 */
function addDirectoryToZip($zip, $source, $zipPath) {
    $source = rtrim($source, '/\\') . DIRECTORY_SEPARATOR;
    
    if (is_dir($source)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $filePath = $item->getPathname();
            $relativePath = $zipPath . substr($filePath, strlen($source));
            
            if ($item->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
}

/**
 * Deleta um diretório recursivamente
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }
    
    rmdir($dir);
}
