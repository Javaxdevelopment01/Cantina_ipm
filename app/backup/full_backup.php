<?php
// full_backup.php - Backup completo do sistema + base de dados
session_start();

// Evita que erros PHP saiam como HTML e quebrem o JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

set_exception_handler(function ($e) {
    error_log('FULL_BACKUP EXCEPTION: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'ok'      => false,
        'error'   => 'Erro interno no backup: ' . $e->getMessage(),
    ]);
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    // Converte qualquer erro em JSON
    error_log("FULL_BACKUP ERROR: [$severity] $message in $file:$line");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'ok'      => false,
        'error'   => 'Erro interno no backup (detalhes em error_log).',
    ]);
    exit;
});

// Permite backup apenas a partir de sessão de administrador
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'ok' => false, 'error' => 'Não autorizado']);
    exit;
}

// Carrega configuração da BD
require_once __DIR__ . '/../../config/database.php';

$backupRoot = __DIR__ . '/../../..'; // raiz do projeto (cantina_ipm)
$backupDir  = __DIR__ . '/../../backups/';

if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true)) {
    echo json_encode(['success' => false, 'ok' => false, 'error' => 'Não foi possível criar a pasta de backups']);
    exit;
}

$timestamp = date('Ymd_His');
$zipName   = "full_backup_{$timestamp}.zip";
$zipPath   = $backupDir . $zipName;

// 1) Cria dump da base de dados via mysqldump
$dbDumpFile = $backupDir . "db_{$db}_{$timestamp}.sql";

// Caminho do mysqldump
// Em Windows (Wamp), normalmente fica em:
// C:\wamp64\bin\mysql\mysqlX.Y.Z\bin\mysqldump.exe
$mysqldumpPath = null;

if (stripos(PHP_OS, 'WIN') === 0) {
    $candidates = [
        'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe',
        'C:\\wamp64\\bin\\mysql\\mysql8.0.30\\bin\\mysqldump.exe',
        'C:\\wamp64\\bin\\mysql\\mysql8.0.29\\bin\\mysqldump.exe',
    ];
    foreach ($candidates as $path) {
        if (file_exists($path)) {
            $mysqldumpPath = $path;
            break;
        }
    }
    // Se não encontrar nessas pastas, tenta usar apenas "mysqldump" e confiar no PATH
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
    // em Windows, -pPASSWORD (sem espaço) funciona melhor
    $cmdParts[] = '-p' . $pass;
}
$cmdParts[] = escapeshellarg($db);

$cmd = implode(' ', $cmdParts) . ' > ' . escapeshellarg($dbDumpFile);

$output = [];
$ret    = 0;
// Em alguns ambientes Windows, é necessário usar cmd /c
if (stripos(PHP_OS, 'WIN') === 0) {
    $cmd = 'cmd /c ' . $cmd;
}
@exec($cmd, $output, $ret);

if ($ret !== 0 || !file_exists($dbDumpFile) || filesize($dbDumpFile) === 0) {
    // Se falhar, apaga ficheiro vazio e devolve erro amigável
    if (file_exists($dbDumpFile)) {
        @unlink($dbDumpFile);
    }
    echo json_encode([
        'success' => false,
        'ok'      => false,
        'error'   => 'Falha ao executar mysqldump. Verifica se o mysqldump está no PATH do sistema ou ajusta o caminho em full_backup.php.'
    ]);
    exit;
}

// 2) Cria ZIP com dump + ficheiros principais do projeto
$zip = new ZipArchive();
if (!class_exists('ZipArchive')) {
    echo json_encode(['success' => false, 'ok' => false, 'error' => 'Extensão ZIP do PHP não está habilitada.']);
    exit;
}
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    @unlink($dbDumpFile);
    echo json_encode(['success' => false, 'ok' => false, 'error' => 'Não foi possível criar o ficheiro ZIP de backup']);
    exit;
}

// Adiciona dump da base de dados
$zip->addFile($dbDumpFile, 'database/' . basename($dbDumpFile));

// Adiciona ficheiros do projeto (sem recursão ao próprio diretório de backups)
$rootReal = realpath($backupRoot);

$excludeDirs = [
    DIRECTORY_SEPARATOR . 'backups',
    DIRECTORY_SEPARATOR . '.git',
    DIRECTORY_SEPARATOR . 'node_modules',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootReal, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $fileInfo) {
    $pathName = $fileInfo->getPathname();
    $relative = substr($pathName, strlen($rootReal) + 1);

    // Ignora diretórios específicos
    $skip = false;
    foreach ($excludeDirs as $ex) {
        if (strpos($pathName, $ex) !== false) {
            $skip = true;
            break;
        }
    }
    if ($skip) {
        if ($fileInfo->isDir()) {
            // Pula recursão neste diretório
            $iterator->next();
        }
        continue;
    }

    if ($fileInfo->isFile()) {
        // Evita incluir o próprio ZIP em construção ou dumps antigos
        if ($pathName === $zipPath) {
            continue;
        }
        $zip->addFile($pathName, $relative);
    }
}

$zip->close();

// Já podemos apagar o ficheiro de dump solto (fica apenas dentro do ZIP)
@unlink($dbDumpFile);

echo json_encode([
    'success' => true,
    'ok'      => true,
    'file'    => '/backups/' . $zipName
]);
exit;


