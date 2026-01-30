<?php
/**
 * Script para diagnosticar e corrigir permissões da pasta de upload de admin
 */

$uploadDir = __DIR__ . '/uploads/admin';

echo "=== DIAGNÓSTICO DE PERMISSÕES DE UPLOAD ===\n\n";

echo "Caminho: $uploadDir\n";
echo "Existe: " . (is_dir($uploadDir) ? 'SIM ✓' : 'NÃO ✗') . "\n";

if (is_dir($uploadDir)) {
    echo "É escrita possível: " . (is_writable($uploadDir) ? 'SIM ✓' : 'NÃO ✗') . "\n";
    echo "Permissões atuais: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "\n";
    
    // Tentar corrigir permissões
    echo "\nTentando corrigir permissões...\n";
    if (@chmod($uploadDir, 0777)) {
        echo "✓ Permissões corrigidas para 0777\n";
        echo "Novas permissões: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "\n";
    } else {
        echo "✗ Não foi possível alterar permissões com chmod(). Tentando outro método...\n";
    }
    
    // Testar upload
    echo "\nTentando criar arquivo de teste...\n";
    $testFile = $uploadDir . '/test_' . time() . '.txt';
    if (@file_put_contents($testFile, 'teste de permissão')) {
        echo "✓ Arquivo de teste criado com sucesso\n";
        @unlink($testFile);
        echo "✓ Arquivo de teste removido\n";
        echo "\n✓ A pasta está funcionando corretamente para upload!\n";
    } else {
        echo "✗ Não foi possível criar arquivo de teste\n";
        echo "   Possível causa: Permissões insuficientes ou pasta protegida\n";
        echo "\n   SOLUÇÃO:\n";
        echo "   1. Clique com botão direito em: " . str_replace('/', '\\', $uploadDir) . "\n";
        echo "   2. Propriedades → Segurança\n";
        echo "   3. Editar → Seleciona 'Users' ou usuário do WAMP\n";
        echo "   4. Marca: Modificar, Ler, Executar, Listar Conteúdo\n";
        echo "   5. Aplica e OK\n";
    }
} else {
    echo "✗ Pasta não existe. Tentando criar...\n";
    if (@mkdir($uploadDir, 0777, true)) {
        echo "✓ Pasta criada com sucesso\n";
        if (@chmod($uploadDir, 0777)) {
            echo "✓ Permissões definidas como 0777\n";
        }
    } else {
        echo "✗ Não foi possível criar a pasta\n";
    }
}

echo "\n=== VERIFICAÇÃO CONCLUÍDA ===\n";
echo "\nPróximos passos:\n";
echo "1. Se tudo está OK: Volta ao formulário e tenta cadastrar novamente\n";
echo "2. Se ainda há problemas: Verifica as permissões da pasta manualmente\n";
?>
