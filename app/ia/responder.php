<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

class AssistenteIA {
    private $respostas;
    private $respostas_dashboard;
    private $respostas_pagina;
    private $pagina;
    private $conn;
    
    public function __construct($conn, $pagina = '') {
        $this->conn = $conn;
        $this->respostas = json_decode(file_get_contents(__DIR__ . '/respostas.json'), true);
        $this->respostas_dashboard = json_decode(file_get_contents(__DIR__ . '/respostas_dashboard.json'), true);
        $this->respostas_pagina = null;
        $this->pagina = $pagina;

        // tenta carregar respostas específicas da página, ex: pagina='alerta_estoque' -> respostas_alerta_estoque.json
        if (!empty($pagina)) {
            // sanitizar nome da pagina para evitar path traversal
            $safe = preg_replace('/[^a-z0-9_\-]/i', '_', $pagina);
            $path = __DIR__ . '/respostas_' . $safe . '.json';
            if (file_exists($path)) {
                $this->respostas_pagina = json_decode(file_get_contents($path), true);
            }
        }
    }
    
    private function procurarPadroes($mensagem, $padroes) {
        // Normaliza mensagem (remove acentos e pontuação)
        $mensagemNorm = $this->normalizar($mensagem);
        foreach ($padroes as $chave => $alternativas) {
            $alternativas = explode('|', $chave);
            foreach ($alternativas as $alt) {
                $alt = trim($alt);
                $altNorm = $this->normalizar($alt);
                // Verifica correspondência por palavra completa ou por substring
                // Primeiro tenta palavra completa para reduzir falsos positivos
                if (preg_match('/\b' . preg_quote($altNorm, '/') . '\b/u', $mensagemNorm)) {
                    return true;
                }
                // Em último caso, verifica substring
                if (strpos($mensagemNorm, $altNorm) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    // Normaliza texto: minusculas, remove acentos e pontuação extra
    private function normalizar($str) {
        if (!is_string($str)) return '';
        $s = mb_strtolower($str, 'UTF-8');
        // remover acentos
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        // remover pontuação e caracteres extras, manter letras e números e espaços
        $s = preg_replace('/[^a-z0-9\s]/i', ' ', $s);
        // colapsar múltiplos espaços
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }
    
    private function obterRespostaAleatoria($respostas) {
        if (is_array($respostas) && count($respostas) > 0) {
            return $respostas[array_rand($respostas)];
        }
        return null;
    }
    
    public function processarMensagem($mensagem) {
        $mensagem = strtolower(trim($mensagem));
    $resposta = '';
    $tipoResposta = '';
        $contexto = isset($_SESSION['pagina_atual']) ? $_SESSION['pagina_atual'] : '';
        
        // Se existe JSON específico da página, tenta usá-lo primeiro
        $foundPagina = false;
        if (!empty($this->respostas_pagina) && is_array($this->respostas_pagina)) {
            foreach ($this->respostas_pagina as $categoria => $padroes) {
                foreach ($padroes as $padrao => $respostas) {
                    if ($this->procurarPadroes($mensagem, [$padrao => $respostas])) {
                        $resposta = $this->obterRespostaAleatoria($respostas);
                        $tipoResposta = 'resposta_pagina';
                        $foundPagina = true;
                        break;
                    }
                }
                if ($foundPagina) break;
            }
        }

        // Tenta encontrar resposta específica do dashboard (se não houve match na página)
        if (!$foundPagina) {
            $foundDashboard = false;
            foreach ($this->respostas_dashboard as $categoria => $padroes) {
                foreach ($padroes as $padrao => $respostas) {
                    if ($this->procurarPadroes($mensagem, [$padrao => $respostas])) {
                        $resposta = $this->obterRespostaAleatoria($respostas);
                        $tipoResposta = 'resposta_dashboard';
                        $foundDashboard = true;
                        break;
                    }
                }
                if ($foundDashboard) break;
            }
        }
        
        // Verifica saudações gerais (usa procurarPadroes para suportar alternativas como 'ola|oi')
        if (!empty($this->respostas['saudacoes'])) {
            foreach ($this->respostas['saudacoes'] as $padrao => $resps) {
                // reutiliza procurarPadroes passando apenas o par padrão=>resps
                if ($this->procurarPadroes($mensagem, [$padrao => $resps])) {
                    $resposta = $this->obterRespostaAleatoria($resps);
                    break;
                }
            }
        }
        
        // Verifica produtos com estoque baixo
        if (strpos($mensagem, 'produto') !== false || strpos($mensagem, 'estoque') !== false) {
            $stmt = $this->conn->prepare("SELECT p.*, c.nome as categoria_nome 
                                        FROM produto p 
                                        LEFT JOIN categoria c ON p.categoria_id = c.id 
                                        WHERE p.quantidade <= 5");
            $stmt->execute();
            $produtosBaixoEstoque = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($produtosBaixoEstoque) {
                $alertas = [];
                foreach ($produtosBaixoEstoque as $produto) {
                    $alerta = str_replace(
                        ['{nome}', '{quantidade}'],
                        [$produto['nome'], $produto['quantidade']],
                        $this->respostas['produtos']['estoque_baixo'][array_rand($this->respostas['produtos']['estoque_baixo'])]
                    );
                    $alertas[] = $alerta;
                }
                $resposta = implode("\n", $alertas);
            }
        }
        
        // Resposta padrão se nenhuma específica foi encontrada
        if (empty($resposta)) {
            // Prioriza resposta de ajuda do dashboard (aleatória)
            if (isset($this->respostas_dashboard['ajuda']['como usar|iniciar|comecar'])) {
                $resposta = $this->obterRespostaAleatoria($this->respostas_dashboard['ajuda']['como usar|iniciar|comecar']);
            } elseif (isset($this->respostas['geral']['ajuda'])) {
                // fallback para respostas gerais de ajuda
                $resposta = $this->obterRespostaAleatoria($this->respostas['geral']['ajuda']);
            } else {
                // resposta genérica final
                $resposta = 'Desculpe, não entendi. Pode reformular a pergunta?';
            }
        }
        
        // Define tipo padrão se não definido
        if (empty($tipoResposta)) $tipoResposta = 'resposta';

        // Logging leve para análise de correspondência (não quebra em caso de erro)
        $logLine = sprintf("%s | MSG: %s | RES: %s | TIPO: %s\n", date('Y-m-d H:i:s'), str_replace("\n"," ", $mensagem), str_replace("\n"," ", $resposta), $tipoResposta);
        $logPath = __DIR__ . '/../../logs/ia_queries.log';
        @file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);

        $resultado = [
            'texto' => $resposta,
            'audio' => true,
            'tipo' => $tipoResposta
        ];

        return $resultado;
    }
}

// Processa a requisição
$data = json_decode(file_get_contents('php://input'), true);
$paginaParaAssistente = isset($data['pagina']) ? $data['pagina'] : '';
$assistente = new AssistenteIA($conn, $paginaParaAssistente);

if (isset($data['mensagem'])) {
    $resposta = $assistente->processarMensagem($data['mensagem']);
    echo json_encode($resposta);
} else {
    echo json_encode(['erro' => 'Mensagem não fornecida']);
}