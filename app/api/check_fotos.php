<?php
header('Content-Type: application/json; charset=UTF-8');

try {
    require_once __DIR__ . '/../../config/database.php';
    
    $stmt = $conn->prepare("SELECT id, nome, foto_perfil FROM admin WHERE foto_perfil IS NOT NULL ORDER BY id DESC");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $resultado = [];
    $base_path = dirname(__DIR__, 2) . '/';
    
    foreach ($admins as $admin) {
        $foto = $admin['foto_perfil'];
        $filename = basename($foto);
        $full_path = $base_path . 'uploads/admin/' . $filename;
        
        $resultado[] = [
            'id' => $admin['id'],
            'nome' => $admin['nome'],
            'foto_bd' => $foto,
            'arquivo' => $filename,
            'caminho_completo' => $full_path,
            'existe' => file_exists($full_path) ? true : false,
            'url_web' => '/uploads/admin/' . $filename
        ];
    }
    
    echo json_encode($resultado, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
?>
