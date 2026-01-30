<?php
header('Content-Type: application/json; charset=UTF-8');

try {
    require_once __DIR__ . '/../../config/database.php';
    
    // Buscar todos os admins que têm foto_perfil mas sem /uploads/admin/ no início
    $stmt = $conn->prepare("
        SELECT id, nome, foto_perfil 
        FROM admin 
        WHERE foto_perfil IS NOT NULL 
        AND foto_perfil != '' 
        AND foto_perfil NOT LIKE '/uploads/admin/%'
    ");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $atualizados = 0;
    
    foreach ($admins as $admin) {
        // Corrigir o caminho
        $novo_caminho = '/uploads/admin/' . basename($admin['foto_perfil']);
        
        $update_stmt = $conn->prepare("UPDATE admin SET foto_perfil = :novo WHERE id = :id");
        $update_stmt->execute([
            ':novo' => $novo_caminho,
            ':id' => $admin['id']
        ]);
        $atualizados++;
    }
    
    echo json_encode([
        'status' => 'Atualização concluída',
        'admins_encontrados' => count($admins),
        'admins_atualizados' => $atualizados,
        'detalhes' => $admins
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
