<?php
// upload_media.php
session_start();
if(!isset($_SESSION['vendedor_id'])) {
    http_response_code(403);
    echo json_encode(['erro'=>'Não autorizado']);
    exit;
}

header('Content-Type: application/json');

$targetDir = __DIR__ . '/../../../uploads/brand/';
if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);

if (empty($_FILES) || !isset($_FILES['file'])) {
    echo json_encode(['erro'=>'Ficheiro não enviado']);
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['erro'=>'Erro no upload']);
    exit;
}

$allowed = ['image/png','image/jpeg','image/x-icon','image/vnd.microsoft.icon'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!in_array($mime, $allowed)) {
    echo json_encode(['erro'=>'Tipo de ficheiro não permitido']);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$name = preg_replace('/[^a-z0-9_\-]/i','_', pathinfo($file['name'], PATHINFO_FILENAME));
$target = $targetDir . $name . '_' . time() . '.' . $ext;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['erro'=>'Falha ao mover ficheiro']);
    exit;
}

$publicPath = '/uploads/brand/' . basename($target);
echo json_encode(['ok'=>true,'path'=>$publicPath]);
exit;
