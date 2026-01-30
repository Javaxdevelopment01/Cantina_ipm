<?php
header('Content-Type: application/json; charset=UTF-8');

try {
    require_once __DIR__ . '/../../config/database.php';
    
    $sourceDir = __DIR__ . '/../uploads/vendedores/';
    $destDir = __DIR__ . '/../../uploads/vendedores/';
    
    // Garantir que a pasta de destino existe
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    
    $copiados = 0;
    $detalhes = [];
    $erros = [];
    
    // Listar ficheiros da pasta de origem
    if (is_dir($sourceDir) && ($files = scandir($sourceDir))) {
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $sourcePath = $sourceDir . $file;
            $destPath = $destDir . $file;
            
            // Se não existir no destino, copia
            if (!file_exists($destPath) && is_file($sourcePath)) {
                if (copy($sourcePath, $destPath)) {
                    $copiados++;
                    $detalhes[] = "Copiado: $file";
                } else {
                    $erros[] = "Erro ao copiar $file";
                }
            }
        }
    }
    
    // Buscar vendedores com foto e atualizar caminho se necessário
    $stmt = $conn->prepare("SELECT id, imagem FROM vendedor WHERE imagem IS NOT NULL AND imagem != ''");
    $stmt->execute();
    $vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $atualizados = 0;
    $updates = [];
    
    foreach ($vendedores as $vend) {
        $img_antiga = $vend['imagem'];
        
        // Se está com caminho "app/uploads/vendedores/", normaliza para "uploads/vendedores/"
        if (strpos($img_antiga, 'app/uploads/vendedores/') === 0) {
            $img_nova = str_replace('app/uploads/vendedores/', 'uploads/vendedores/', $img_antiga);
            
            if ($img_antiga !== $img_nova) {
                $update = $conn->prepare("UPDATE vendedor SET imagem = :nova WHERE id = :id");
                $update->execute([':nova' => $img_nova, ':id' => $vend['id']]);
                $atualizados++;
                $updates[] = [
                    'id' => $vend['id'],
                    'de' => $img_antiga,
                    'para' => $img_nova
                ];
            }
        }
    }
    
    echo json_encode([
        'status' => 'Normalização concluída',
        'ficheiros_copiados' => $copiados,
        'registros_bd_atualizados' => $atualizados,
        'copia_detalhes' => $detalhes,
        'copia_erros' => $erros,
        'bd_atualizados' => $updates
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
