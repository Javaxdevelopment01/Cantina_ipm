<?php
/**
 * YASMIN API Wrapper - Canned Responses (Sem IA Externa)
 * 
 * Sistema de respostas pré-definidas para a assistente Yasmin (Cliente).
 * Removeu-se a dependência do Google Gemini para respostas mais rápidas e controladas.
 * 
 * Uso:
 *   POST request com JSON: { "mensagem": "..." }
 *   Retorna: { "success": bool, "mensagem": str, "recomendacoes": [], ... }
 */

header('Content-Type: application/json; charset=utf-8');

// CONFIGURAÇÃO
// =================================================================================
// Lista de perguntas sugeridas (aparecem como botões no frontend)
$sugestoesPadrao = [
    "O que tem para comer hoje?",
    "Quais são os preços?",
    "Horário de funcionamento",
    "Falar com o Vendedor"
];

// PROCESSAMENTO DO REQUEST
// =================================================================================

// 1. Obter input raw
$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody, true);

// Fallbacks para input malformado
if (!is_array($input)) {
    if (!empty($_POST['mensagem'])) {
        $input = $_POST;
    } else {
        $clean = trim($rawBody);
        if (substr($clean, 0, 1) === '"' && substr($clean, -1) === '"') {
            $input = json_decode(stripslashes(substr($clean, 1, -1)), true);
        }
    }
}

// 2. Validação básica
$mensagemUsuario = trim($input['mensagem'] ?? '');

if (empty($mensagemUsuario)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Mensagem vazia ou inválida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Normalização para busca de palavras-chave
$msgLower = mb_strtolower($mensagemUsuario, 'UTF-8');

// 4. Lógica de Respostas (Canned Responses)
$resposta = "";
$recomendacoes = $sugestoesPadrao; // Padrão: mostra as mesmas opções

// Palavras-chave simples
if (strpos($msgLower, 'olá') !== false || strpos($msgLower, 'oi') !== false || strpos($msgLower, 'bom dia') !== false || strpos($msgLower, 'boa tarde') !== false) {
    $resposta = "Olá! Sou a YASMIN, a tua assistente virtual da Cantina IPM. Como posso ajudar-te hoje?";
} 
elseif (strpos($msgLower, 'comer') !== false || strpos($msgLower, 'menu') !== false || strpos($msgLower, 'cardápio') !== false || strpos($msgLower, 'prato') !== false) {
    $resposta = "Temos várias opções deliciosas hoje! 😋 Podes ver o menu completo e atualizado no ecrã principal da cantina ou navegar pelas categorias aqui na app.";
} 
elseif (strpos($msgLower, 'preço') !== false || strpos($msgLower, 'quanto custa') !== false || strpos($msgLower, 'valor') !== false) {
    $resposta = "Os preços variam conforme o prato. Por favor, consulta a tabela de preços detalhada afixada na cantina ou seleciona um produto para ver o valor.";
} 
elseif (strpos($msgLower, 'horário') !== false || strpos($msgLower, 'aberto') !== false || strpos($msgLower, 'fecha') !== false) {
    $resposta = "A cantina está aberta nos dias úteis. Para horários específicos de hoje, por favor verifica o aviso na entrada.";
}
elseif (strpos($msgLower, 'vendedor') !== false || strpos($msgLower, 'humano') !== false || strpos($msgLower, 'ajuda') !== false || strpos($msgLower, 'suporte') !== false) {
    $resposta = "Se precisas de falar com um vendedor humano, por favor dirige-te ao balcão. Eles terão todo o gosto em ajudar!";
}
elseif ($mensagemUsuario === '/ping') {
    $resposta = "Pong! YASMIN (Modo Simplificado) está online.";
}
else {
    // Resposta Padrão para não entendimento
    $resposta = "Desculpa, não entendi bem a tua questão. Podes tentar uma das opções abaixo ou dirigir-te ao balcão.";
}

// 5. Retorno final para o Frontend
echo json_encode([
    'success' => true,
    'mensagem' => $resposta,
    'intencao' => 'chat',
    'recomendacoes' => $recomendacoes, 
    'audio_base64' => null 
], JSON_UNESCAPED_UNICODE);
