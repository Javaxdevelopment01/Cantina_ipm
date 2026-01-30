<?php
/**
 * Guia interativo para resolver o problema "Desconhecido"
 */
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix - Desconhecido em Pedidos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #012E40; 
            color: #333; 
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .header {
            background: #D4AF37;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .header h1 { color: #012E40; font-size: 28px; }
        .header p { color: #012E40; opacity: 0.8; margin-top: 5px; }
        
        .step {
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            border-left: 5px solid #D4AF37;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .step h2 { color: #012E40; margin-bottom: 10px; font-size: 20px; }
        .step p { color: #666; line-height: 1.6; margin-bottom: 10px; }
        .step a { 
            display: inline-block;
            background: #012E40;
            color: #D4AF37;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin: 5px 5px 5px 0;
            transition: all 0.3s;
        }
        .step a:hover { background: #D4AF37; color: #012E40; }
        
        .info-box {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #D4AF37;
        }
        .success { background: #e8f5e9; border-left-color: #4caf50; }
        .warning { background: #fff3e0; border-left-color: #ff9800; }
        .error { background: #ffebee; border-left-color: #f44336; }
        
        .status {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-ok { background: #4caf50; color: white; }
        .badge-pending { background: #ff9800; color: white; }
        .badge-error { background: #f44336; color: white; }
        
        .footer { 
            text-align: center; 
            padding: 20px; 
            color: #D4AF37;
            margin-top: 30px;
            border-top: 2px solid #D4AF37;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🔧 RESOLVER: "Desconhecido" em Pedidos</h1>
        <p>Guia passo a passo para corrigir o bug após gerar factura</p>
    </div>
    
    <div class="step">
        <h2>📋 Passo 1: Diagnosticar o Problema</h2>
        <p>Primeiro, vamos verificar qual é exatamente o problema:</p>
        <a href="diagnose_desconhecido.php" target="_blank">🔍 Executar Diagnóstico</a>
        <div class="info-box warning">
            <strong>O que vai ver:</strong> O estado real que está sendo salvo no banco e se o ENUM está correto.
        </div>
    </div>
    
    <div class="step">
        <h2>⚙️ Passo 2: Corrigir o ENUM (IMPORTANTE!)</h2>
        <p>O ENUM da tabela <strong>pedido</strong> deve ter 4 valores: pendente, atendido, finalizado, cancelado</p>
        <a href="fix_enum_force.php" target="_blank">✅ FORÇAR CORREÇÃO DO ENUM</a>
        <div class="info-box error">
            <strong>⚠️ CRÍTICO:</strong> Se não rodar este passo, NUNCA vai funcionar!
        </div>
    </div>
    
    <div class="step">
        <h2>✔️ Passo 3: Verificar se Funcionou</h2>
        <p>Execute este script para verificar se tudo está correto:</p>
        <a href="test_fluxo_pedidos.php" target="_blank">🧪 Testar Fluxo</a>
        <div class="info-box success">
            <strong>Sucesso quando:</strong> Aparecer os 4 estados: pendente, atendido, finalizado, cancelado
        </div>
    </div>
    
    <div class="step">
        <h2>🎯 Passo 4: Testar na Prática</h2>
        <p>Agora teste o fluxo completo:</p>
        <ol style="margin-left: 20px; line-height: 2;">
            <li>Cria um novo pedido (vai aparecer como <span class="badge badge-pending">Pendente 🟡</span>)</li>
            <li>Marca "Atender" (vai aparecer como <span class="badge badge-pending">Atendido 🔵</span>)</li>
            <li>Vai em VENDAS e abre "Gerar Factura"</li>
            <li>Preenche dados de pagamento e clica "Gerar"</li>
            <li>Volta em PEDIDOS e verifica o estado (deve ser <span class="badge badge-ok">Finalizado 🟢</span>)</li>
        </ol>
    </div>
    
    <div class="footer">
        <p><strong>Se ainda assim não funcionar:</strong></p>
        <p>Mande a captura de tela da <a href="diagnose_desconhecido.php" style="color: #D4AF37; text-decoration: underline;">página de diagnóstico</a> para análise.</p>
    </div>
</div>
</body>
</html>
