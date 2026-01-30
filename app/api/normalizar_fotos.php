<?php
header('Content-Type: application/json; charset=UTF-8');

try {
    require_once __DIR__ . '/../../config/database.php';
    
    // Buscar todos os admins com foto
    $stmt = $conn->prepare("SELECT id, foto_perfil FROM admin WHERE foto_perfil IS NOT NULL AND foto_perfil != ''");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $atualizados = 0;
    $detalhes = [];
    
    foreach ($admins as $admin) {
        $foto_antiga = $admin['foto_perfil'];
        
        // Extrair apenas o filename
        $filename = basename($foto_antiga);
        
        // Novo caminho padronizado
        $foto_nova = '/uploads/admin/' . $filename;
        
        if ($foto_antiga !== $foto_nova) {
            // Atualizar no BD
            $update = $conn->prepare("UPDATE admin SET foto_perfil = :nova WHERE id = :id");
            $update->execute([':nova' => $foto_nova, ':id' => $admin['id']]);
            $atualizados++;
            
            $detalhes[] = [
                'id' => $admin['id'],
                'de' => $foto_antiga,
                'para' => $foto_nova
            ];
        }
    }
    
    echo json_encode([
        'status' => 'Normalização concluída',
        'registros_atualizados' => $atualizados,
        'detalhes' => $detalhes
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
?>
