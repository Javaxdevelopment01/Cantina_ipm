<?php
// backup_manual.php
session_start();
if(!isset($_SESSION['vendedor_id'])) {
    http_response_code(403); echo json_encode(['erro'=>'Não autorizado']); exit;
}

header('Content-Type: application/json');

$backupDir = __DIR__ . '/../../../backups/';
if (!is_dir($backupDir)) @mkdir($backupDir,0755,true);

$timestamp = date('Ymd_His');
$zipName = "backup_{$timestamp}.zip";
$zipPath = $backupDir . $zipName;

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
    echo json_encode(['erro'=>'Falha ao criar backup']); exit;
}

// Adicionar config
$settingsPath = __DIR__ . '/../../../config/settings_vendedor.json';
if (file_exists($settingsPath)) $zip->addFile($settingsPath, 'config/settings_vendedor.json');

// Adicionar IA responses
$iaDir = __DIR__ . '/../../../app/ia/';
if (is_dir($iaDir)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($iaDir));
    foreach ($it as $file) {
        if ($file->isFile()) {
            $filePath = $file->getRealPath();
            $local = 'app/ia/' . substr($filePath, strlen(realpath(__DIR__ . '/../../../app/ia/'))+1);
            $zip->addFile($filePath, $local);
        }
    }
}

$zip->close();

// Atualizar settings com ultimo backup
if (file_exists($settingsPath)) {
    $s = json_decode(file_get_contents($settingsPath), true) ?: [];
    $s['backup']['ultimo_backup'] = date('c');
    file_put_contents($settingsPath, json_encode($s, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}

$public = '/backups/' . $zipName;
echo json_encode(['ok'=>true,'file'=>$public]);
exit;
