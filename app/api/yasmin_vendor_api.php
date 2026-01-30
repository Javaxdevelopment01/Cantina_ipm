<?php
/**
 * YASMIN Vendor API Wrapper
 * Integra o assistente YASMIN com contexto de vendedor (pedidos, clientes, vendas, estoque)
 * 
 * Uso:
 *   POST request com JSON: { "mensagem": "...", "audio": true }
 *   Retorna: { "success": bool, "mensagem": str, "recomendacoes": [...], ... }
 */

header('Content-Type: application/json; charset=utf-8');

// Obtém dados do request
$rawBody = file_get_contents('php://input');
if ($rawBody === false || $rawBody === null || $rawBody === '') {
    $fp = @fopen('php://input', 'r');
    if ($fp) {
        $streamAttempt = stream_get_contents($fp);
        @fclose($fp);
    }
    if (!empty($streamAttempt)) {
        $rawBody = $streamAttempt;
    }
}
$input = json_decode($rawBody, true);

// Recuperação robusta do input (mesmo que em dashboard_cliente)
if (!is_array($input) && !empty($rawBody)) {
    if (substr($rawBody, 0, 3) === "\xEF\xBB\xBF") {
        $rawBody = substr($rawBody, 3);
    }
    $rawBodyTrim = trim($rawBody);
    
    if ((substr($rawBodyTrim, 0, 1) === '"' && substr($rawBodyTrim, -1) === '"') || (substr($rawBodyTrim, 0, 1) === "'" && substr($rawBodyTrim, -1) === "'")) {
        $inner = substr($rawBodyTrim, 1, -1);
        $inner = stripslashes($inner);
        $try = json_decode($inner, true);
        if (is_array($try)) {
            $input = $try;
        }
    }
    
    if (!is_array($input)) {
        $unescaped = str_replace('\\"', '"', $rawBodyTrim);
        $unescaped = str_replace('\\\\', '\\', $unescaped);
        $try2 = json_decode($unescaped, true);
        if (is_array($try2)) {
            $input = $try2;
        }
    }
    
    if (!is_array($input)) {
        $start = strpos($rawBody, '{');
        $end = strrpos($rawBody, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $substr = substr($rawBody, $start, $end - $start + 1);
            $try3 = json_decode($substr, true);
            if (is_array($try3)) {
                $input = $try3;
            }
        }
    }
    
    if (!is_array($input)) {
        $parsed = [];
        @parse_str($rawBody, $parsed);
        if (!empty($parsed)) {
            $input = $parsed;
        } else {
            $input = $_POST;
        }
    }
    
    if ((!is_array($input) || empty($input)) && !empty($rawBody)) {
        $patternMsg = '/mensagem\s*:\s*"?([^",\}]+)"?/i';
        $patternAudio = '/audio\s*:\s*(true|false|1|0)/i';
        $foundMsg = preg_match($patternMsg, $rawBody, $mmsg);
        $foundAudio = preg_match($patternAudio, $rawBody, $ma);
        if ($foundMsg) {
            $input = [];
            $input['mensagem'] = trim($mmsg[1]);
            if ($foundAudio) {
                $aval = strtolower($ma[1]);
                $input['audio'] = ($aval === 'true' || $aval === '1');
            }
        }
    }
    
    if ((!is_array($input) || empty($input)) && !empty($_GET)) {
        $input = $_GET;
    }
}

if (empty($input['mensagem']) && isset($_REQUEST['mensagem'])) {
    $input['mensagem'] = $_REQUEST['mensagem'];
}

// Bypass mode para teste
if ((isset($_GET['bypass']) && $_GET['bypass'] == '1') || (!empty($input['bypass']))) {
    $sample = [
        'success' => true,
        'mensagem' => 'Resposta de teste do YASMIN (bypass de vendedor). Estou aqui para ajudar com pedidos, clientes e estoque!',
        'recomendacoes' => [],
        'audio_base64' => null,
        'audio_mime' => null
    ];
    echo json_encode($sample, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Debug
@file_put_contents(__DIR__ . '/yasmin_vendor_debug_request.json', json_encode([
    'raw_body_preview' => substr($rawBody,0,2000),
    'raw_body_length' => is_null($rawBody) ? 0 : strlen($rawBody),
    '_POST' => $_POST,
    '_GET' => $_GET,
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

// Valida mensagem
if (!isset($input['mensagem']) || empty(trim($input['mensagem']))) {
    error_log('[YASMIN Vendor] Empty mensagem. Raw body: ' . substr($rawBody,0,1000));
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Mensagem vazia',
        'raw_body' => $rawBody,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$mensagem = trim($input['mensagem']);

// Carrega dados do BD para contexto do vendedor
require_once __DIR__ . '/../../config/database.php';

$vendorContext = [
    'totalPedidos' => 0,
    'pedidosPendentes' => 0,
    'totalClientes' => 0,
    'quantidadeEstoque' => 0,
    'produtosAlerta' => [],
    'produtosMaisVendidos' => [],
];

try {
    // Total de pedidos
    $vendorContext['totalPedidos'] = $conn->query("SELECT COUNT(*) FROM pedido")->fetchColumn() ?? 0;
    
    // Pedidos pendentes
    $vendorContext['pedidosPendentes'] = $conn->query("SELECT COUNT(*) FROM pedido WHERE estado = 'pendente'")->fetchColumn() ?? 0;
    
    // Total de clientes
    $vendorContext['totalClientes'] = $conn->query("SELECT COUNT(*) FROM cliente")->fetchColumn() ?? 0;
    
    // Quantidade total em estoque
    $vendorContext['quantidadeEstoque'] = $conn->query("SELECT SUM(quantidade) FROM produto")->fetchColumn() ?? 0;
    
    // Produtos com alerta (quantidade <= 5)
    $stmt = $conn->prepare("SELECT id, nome, quantidade FROM produto WHERE quantidade <= 5 ORDER BY quantidade ASC");
    $stmt->execute();
    $vendorContext['produtosAlerta'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Produto com menor estoque absoluto (para responder "qual tem menos?")
    $stmt = $conn->prepare("SELECT nome, quantidade FROM produto ORDER BY quantidade ASC LIMIT 1");
    $stmt->execute();
    $vendorContext['produtoMenorEstoque'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Top 5 produtos mais vendidos
    $stmt = $conn->prepare("
        SELECT p.id, p.nome, SUM(pi.quantidade) AS total_vendido, SUM(pi.quantidade * pi.preco) AS receita
        FROM pedido_itens pi
        JOIN produto p ON pi.id_produto = p.id
        JOIN pedido pd ON pi.id_pedido = pd.id
        WHERE pd.estado = 'atendido'
        GROUP BY pi.id_produto
        ORDER BY total_vendido DESC
        LIMIT 5
    ");
    $stmt->execute();
    $vendorContext['produtosMaisVendidos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('[YASMIN Vendor] BD Error: ' . $e->getMessage());
}

// Caminho para o script Python YASMIN
$yasminPythonPath = __DIR__ . '/../../ia/yasmin_assistant.py';

if (!file_exists($yasminPythonPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'YASMIN não está configurado']);
    exit;
}

// Python detection (mesmo que em yasmin_api.php)
$forcePython = 'C:\\Users\\Leonel JAVAX\\AppData\\Local\\Programs\\Python\\Python314\\python.exe';
$pythonCmd = file_exists($forcePython) ? $forcePython : 'python';

// Monta comando: passa mensagem + contexto de vendedor via argumento
$mensagemEscapada = escapeshellarg($mensagem);
$contextJson = json_encode($vendorContext, JSON_UNESCAPED_UNICODE);
$contextArg = escapeshellarg($contextJson);

// Build command (Windows)
$pythonCmdEscaped = '"' . $pythonCmd . '"';
$yasminPathEscaped = '"' . $yasminPythonPath . '"';

// Passa --vendor para indicar modo vendedor
$cmd = $pythonCmdEscaped . ' ' . $yasminPathEscaped . ' ' . $mensagemEscapada . ' --vendor --vendor-context ' . $contextArg . ' --audio 2>&1';

// Debug pré-execução
@file_put_contents(__DIR__ . '/yasmin_vendor_debug_preexec.json', json_encode([
    'pythonCmd' => $pythonCmd,
    'cmd' => $cmd,
    'disable_functions' => ini_get('disable_functions'),
    'vendor_context' => $vendorContext,
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

// Executa Python com proc_open (captura stdout/stderr separadamente)
$output = [];
$returnVar = 0;

$descriptorspec = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
];
$pipes = [];
$process = @proc_open($cmd, $descriptorspec, $pipes, __DIR__);

if (is_resource($process)) {
    @fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    @fclose($pipes[1]);
    @fclose($pipes[2]);
    $returnVar = proc_close($process);
    $stdoutLines = array_filter(array_map('trim', explode("\n", trim($stdout))));
    $stderrLines = array_filter(array_map('trim', explode("\n", trim($stderr))));
    $output = $stdoutLines;
} else {
    if (function_exists('exec') && stripos(ini_get('disable_functions'), 'exec') === false) {
        @exec($cmd . ' 2>&1', $output, $returnVar);
    } else {
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>'Execução desabilitada no PHP'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        exit;
    }
}

// Valida execução
if ($returnVar !== 0 || empty($output)) {
    http_response_code(500);
    $errorMsg = implode("\n", $output) ?: 'Erro ao executar YASMIN (sem output)';
    error_log('[YASMIN Vendor] Exec failed: ' . $cmd . "\n" . $errorMsg);
    @file_put_contents(__DIR__ . '/yasmin_vendor_debug_exec_failed.json', json_encode([
        'cmd'=>$cmd,
        'stdout'=>isset($stdoutLines)?$stdoutLines:[],
        'stderr'=>isset($stderrLines)?$stderrLines:$output,
        'returnVar'=>$returnVar
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    echo json_encode([
        'success' => false,
        'error' => $errorMsg,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Decodifica resposta JSON
$jsonOutput = implode('', $output);
$resultado = json_decode($jsonOutput, true);

if (!is_array($resultado)) {
    http_response_code(500);
    @file_put_contents(__DIR__ . '/yasmin_vendor_debug_raw_output.json', json_encode([
        'cmd' => $cmd,
        'raw_output' => $jsonOutput,
        'output_lines' => $output,
        'returnVar' => $returnVar,
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    echo json_encode([
        'success' => false,
        'error' => 'Resposta inválida de YASMIN',
        'raw_output_file' => 'yasmin_vendor_debug_raw_output.json'
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

// Log de sucesso
if (is_array($resultado)) {
    error_log('[YASMIN Vendor] success=true mensagem:' . substr(($resultado['mensagem'] ?? ''), 0, 100));
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
