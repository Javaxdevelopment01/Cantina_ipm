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

    // Obtém estatísticas reais da BD (vendas, produtos, clientes, mais vendido)
    private function obterEstatisticas() {
        $stats = [];
        try {
            // Total de vendas
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM venda");
            $stats['total_vendas'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Total de produtos
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM produto");
            $stats['total_produtos'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Total de clientes
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM cliente");
            $stats['total_clientes'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Produto mais vendido (por quantidade)
            $stmt = $this->conn->query("
                SELECT p.nome, SUM(pi.quantidade) as total_qtd, SUM(pi.quantidade * pi.preco) as receita
                FROM pedido_itens pi
                JOIN produto p ON pi.id_produto = p.id
                GROUP BY p.id, p.nome
                ORDER BY total_qtd DESC
                LIMIT 1
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $stats['produto_mais_vendido'] = $result['nome'];
                $stats['qtd_mais_vendido'] = (int)$result['total_qtd'];
                $stats['receita_mais_vendido'] = (float)$result['receita'] ?? 0;
            } else {
                $stats['produto_mais_vendido'] = 'N/A';
                $stats['qtd_mais_vendido'] = 0;
                $stats['receita_mais_vendido'] = 0;
            }
        } catch (Exception $e) {
            // se houver erro na BD, retorna valores neutros
            $stats = [
                'total_vendas' => 0,
                'total_produtos' => 0,
                'total_clientes' => 0,
                'produto_mais_vendido' => 'N/A',
                'qtd_mais_vendido' => 0,
                'receita_mais_vendido' => 0
            ];
        }
        return $stats;
    }

    // Tentativa de correspondência tolerante a erros (fuzzy) entre a mensagem e padrões
    private function fuzzyMatchPadroes($mensagem, $padroes) {
        $mensagemNorm = $this->normalizar($mensagem);
        foreach ($padroes as $padrao => $respostas) {
            $alternativas = explode('|', $padrao);
            foreach ($alternativas as $alt) {
                $alt = trim($alt);
                $altNorm = $this->normalizar($alt);
                if ($altNorm === '') continue;

                // Similaridade percentual
                similar_text($mensagemNorm, $altNorm, $percent);

                // Distância de Levenshtein
                $lev = levenshtein($mensagemNorm, $altNorm);
                $len = max(1, strlen($altNorm));
                $threshold = max(1, floor($len * 0.35));

                // Critério: alta similaridade percentual ou pequena distância relativa
                if ($percent >= 60 || $lev <= $threshold) {
                    return ['padrao' => $padrao, 'respostas' => $respostas, 'alt' => $alt];
                }
            }
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
                        $matchedCategory = $categoria;
                        break;
                    }
                }
                if ($foundPagina) break;
            }
        }

        // Tenta encontrar resposta específica do dashboard (se não houve match na página)
        if (!$foundPagina) {
            $foundDashboard = false;
            // Prioridade: categorias específicas primeiro para evitar matches genéricos (ex: 'como')
            $priority = ['produtos', 'pedidos', 'estoque', 'vendas', 'dashboard', 'ajuda'];
            foreach ($priority as $cat) {
                if (!isset($this->respostas_dashboard[$cat])) continue;
                $padroes = $this->respostas_dashboard[$cat];
                foreach ($padroes as $padrao => $respostas) {
                    if ($this->procurarPadroes($mensagem, [$padrao => $respostas])) {
                        $resposta = $this->obterRespostaAleatoria($respostas);
                        $tipoResposta = 'resposta_dashboard';
                        $foundDashboard = true;
                        $matchedCategory = $cat;
                            $matchedCategory = $cat;
                            break 2;
                    }
                }
            }
                // Se não encontrou por correspondência direta, tenta fuzzy match (typos/erros de digitação)
                if (!$foundDashboard) {
                    foreach ($priority as $cat) {
                        if (!isset($this->respostas_dashboard[$cat])) continue;
                        $padroes = $this->respostas_dashboard[$cat];
                        $f = $this->fuzzyMatchPadroes($mensagem, $padroes);
                        if ($f) {
                            $resposta = $this->obterRespostaAleatoria($f['respostas']);
                            $tipoResposta = 'resposta_dashboard_fuzzy';
                            $foundDashboard = true;
                            $matchedCategory = $cat;
                            break;
                        }
                    }
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
        
        // Verifica se deve devolver estatísticas (vendas totais, produtos mais vendidos, etc)
        $shouldShowStats = false;
        if (isset($matchedCategory) && ($matchedCategory === 'vendas' || $matchedCategory === 'produtos')) {
            $shouldShowStats = true;
        } else {
            // procura palavras-chave que indicam intenção de ver estatísticas
            if (preg_match('/total de vendas|vendas totais|total vendas|quantas vendas|total de produtos|quantos produtos|produto mais vendido|mais vendidos|resumo|estatisticas|resumo de vendas|resumo de produtos/i', $mensagem)) {
                $shouldShowStats = true;
            }
        }

        if ($shouldShowStats && empty($resposta)) {
            $stats = $this->obterEstatisticas();
            $resposta = sprintf(
                "📊 **Resumo de Negócio:**\n" .
                "• Total de Vendas: %d\n" .
                "• Total de Produtos: %d\n" .
                "• Total de Clientes: %d\n" .
                "• Produto Mais Vendido: %s (%d unidades, Kz %.2f em receita)\n",
                $stats['total_vendas'],
                $stats['total_produtos'],
                $stats['total_clientes'],
                $stats['produto_mais_vendido'],
                $stats['qtd_mais_vendido'],
                $stats['receita_mais_vendido']
            );
            $tipoResposta = 'estatisticas_reais';
        }
        
        // Verifica produtos com estoque baixo
        // Só executa a verificação de estoque quando a intenção indicar claramente estoque
        // ou quando a categoria reconhecida for 'estoque'. Evita sobrepor respostas quando
        // a intenção for editar/gerir produtos (por ex. 'como editar o produto X').
        $shouldCheckLowStock = false;
        if (isset($matchedCategory) && $matchedCategory === 'estoque') {
            $shouldCheckLowStock = true;
        } else {
            // procura palavras-chave que indicam intenção de verificar estoque
            if (preg_match('/estoque|estoque baixo|faltando|acabando|repor|quantidade|quantas unidades|quantas unidade|verificar estoque|verificar quantidade|estoque critico/i', $mensagem)) {
                $shouldCheckLowStock = true;
            }
        }

        if ($shouldCheckLowStock && empty($resposta)) {
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

        // Preparar ação específica se a categoria for reconhecida (para orientar navegação)
        $acao = null;
        if (!empty($matchedCategory)) {
            switch ($matchedCategory) {
                case 'pedidos':
                    $acao = [
                        'goto' => 'pedidos_vendedor.php',
                        'steps' => [
                            "No menu lateral clique em 'Pedidos'.",
                            "Na página 'Gerenciar Pedidos' verás a lista de pedidos pendentes.",
                            "Para ver os itens de um pedido, expande-o — os itens carregam automaticamente.",
                            "Para marcar como atendido clique em 'Atender Pedido' ou para cancelar clique em 'Cancelar'."
                        ]
                    ];
                    break;
                case 'estoque':
                    $acao = [
                        'goto' => 'alertas_estoque.php',
                        'steps' => [
                            "No menu lateral clique em 'Alertas de Estoque'.",
                            "A página mostra os produtos com quantidade crítica. Clique no produto para editar a quantidade.",
                            "Alternativamente, acede a 'Produtos' para editar manualmente cada item." 
                        ]
                    ];
                    break;
                case 'produtos':
                    $acao = [
                        'goto' => 'produtos_vendedor.php',
                        'steps' => [
                            "No menu lateral clique em 'Produtos' para ver a lista completa.",
                            "Para adicionar um novo produto clique em 'Novo Produto' na página de produtos.",
                            "Para editar um produto, clique em 'Editar' no respetivo item e atualize a quantidade/preço." 
                        ]
                    ];
                    break;
                case 'vendas':
                    $acao = [
                        'goto' => 'vendas_vendedor.php',
                        'steps' => [
                            "No menu lateral clique em 'Vendas' para ver o histórico e relatórios.",
                            "Use filtros de data para filtrar o período desejado e exportar relatórios se necessário." 
                        ]
                    ];
                    break;
                default:
                    $acao = null;
            }
        }

        // Logging leve para análise de correspondência (não quebra em caso de erro)
        $logLine = sprintf("%s | MSG: %s | RES: %s | TIPO: %s | CAT: %s\n", date('Y-m-d H:i:s'), str_replace("\n"," ", $mensagem), str_replace("\n"," ", $resposta), $tipoResposta, $matchedCategory ?? '');
        $logPath = __DIR__ . '/../../logs/ia_queries.log';
        @file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);

        $resultado = [
            'texto' => $resposta,
            'audio' => true,
            'tipo' => $tipoResposta
        ];

        if ($acao) $resultado['acao'] = $acao;

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