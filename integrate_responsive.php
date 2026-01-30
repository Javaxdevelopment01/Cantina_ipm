<?php
/**
 * Script para integrar responsividade em todas as views
 * Execute via CLI: php integrate_responsive.php
 */

$projectRoot = __DIR__;
$viewsPath = $projectRoot . '/app/views';

// Diretórios onde procurar por views PHP
$directories = [
    $viewsPath . '/cliente',
    $viewsPath . '/Vendedor',
    $viewsPath . '/adm'
];

$cssLink = '<link rel="stylesheet" href="../../assets/css/responsive.css">';
$jsScript = '<script src="../../assets/js/responsive.js"><\/script>';

$updated = 0;
$skipped = 0;
$errors = [];

echo "=== INTEGRAÇÃO DE RESPONSIVIDADE ===\n\n";

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        echo "❌ Diretório não encontrado: $dir\n";
        continue;
    }

    echo "📁 Processando: $dir\n";
    
    $files = glob($dir . '/*.php');
    
    foreach ($files as $file) {
        $filename = basename($file);
        
        // Ignora arquivos que já contêm responsive
        $content = file_get_contents($file);
        
        if (strpos($content, 'responsive.css') !== false) {
            echo "  ✓ $filename (já tem CSS)\n";
            $skipped++;
            continue;
        }

        // Adiciona CSS após viewport meta tag
        if (preg_match('/<meta name="viewport"[^>]*>/', $content)) {
            $content = preg_replace(
                '/(<meta name="viewport"[^>]*>)/',
                "$1\n    $cssLink",
                $content
            );
            echo "  ✓ $filename (CSS adicionado)\n";
            $updated++;
        } else {
            echo "  ⚠ $filename (meta viewport não encontrado)\n";
            continue;
        }

        // Adiciona JS antes de </body>
        if (strpos($content, '</body>') !== false) {
            $content = str_replace(
                '</body>',
                "<!-- JavaScript Responsivo Global -->\n$jsScript\n</body>",
                $content
            );
            echo "  ✓ $filename (JS adicionado)\n";
        } else {
            $errors[] = "$filename: tag </body> não encontrada";
        }

        // Salva arquivo atualizado
        if (file_put_contents($file, $content)) {
            // echo "  ✅ $filename salvo com sucesso\n";
        } else {
            $errors[] = "$filename: erro ao salvar";
        }
    }
    echo "\n";
}

echo "=== RESUMO ===\n";
echo "✅ Atualizados: $updated\n";
echo "⏭️  Já tinham: $skipped\n";

if (!empty($errors)) {
    echo "\n⚠️  Erros encontrados:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n✨ Integração concluída!\n";
?>
