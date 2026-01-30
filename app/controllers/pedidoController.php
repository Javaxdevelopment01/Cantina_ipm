<?php
class PedidoController {
    private $conn;

    public function __construct() {
        require_once __DIR__ . '/../../config/database.php';
        // o arquivo de configuração define $conn (PDO)
        $this->conn = $conn;
    }

    public function criarPedido($dados) {
        try {
            $this->conn->beginTransaction();

            // Primeiro: verifica stock e faz lock das linhas (SELECT FOR UPDATE)
            foreach ($dados['items'] as $item) {
                $stmt = $this->conn->prepare("
                    SELECT id, nome, quantidade 
                    FROM produto 
                    WHERE id = ? 
                    FOR UPDATE
                ");
                $stmt->execute([$item['id']]);
                $produto = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$produto) {
                    $this->conn->rollBack();
                    return ['success' => false, 'error' => 'Produto não encontrado: ' . $item['id']];
                }
                
                if ($produto['quantidade'] <= 0) {
                    $this->conn->rollBack();
                    return ['success' => false, 'error' => 'Produto esgotado: ' . $produto['nome']];
                }
                
                if ($item['qty'] > $produto['quantidade']) {
                    $this->conn->rollBack();
                    return [
                        'success' => false, 
                        'error' => "Stock insuficiente para {$produto['nome']} (disponível: {$produto['quantidade']}, pedido: {$item['qty']})"
                    ];
                }
            }

            // Se chegamos aqui, temos stock suficiente e as linhas estão locked
            $id_cliente = $dados['id_cliente'] ?? null;
            $methodData = $dados['methodData'] ?? [];
            $buyerName = trim((string)($methodData['buyer_name'] ?? ''));

            if (empty($id_cliente) && $buyerName !== '') {
                // Tenta criar um cliente mínimo com o nome fornecido (guest)
                $stmt = $this->conn->prepare("INSERT INTO cliente (nome) VALUES (?)");
                $stmt->execute([$buyerName]);
                $id_cliente = $this->conn->lastInsertId();
            }

            // Insere o pedido usando o id_cliente (pode ser null)
            $stmt = $this->conn->prepare("
                INSERT INTO pedido (id_cliente, forma_pagamento, total, estado, lido) 
                VALUES (?, ?, ?, 'pendente', 0)
            ");
            $stmt->execute([
                $id_cliente,
                $dados['forma_pagamento'],
                $dados['total']
            ]);

            $pedidoId = $this->conn->lastInsertId();

            // Insere os itens do pedido
            $stmt = $this->conn->prepare("
                INSERT INTO pedido_itens (id_pedido, id_produto, quantidade, preco) 
                VALUES (?, ?, ?, ?)
            ");

                foreach ($dados['items'] as $item) {
                    $stmt->execute([
                        $pedidoId,
                        $item['id'],
                        $item['qty'],
                        $item['price']
                    ]);

                    // Atualiza estoque do produto: agora é seguro decrementar diretamente pois já validamos e temos lock
                    $upd = $this->conn->prepare("UPDATE produto SET quantidade = quantidade - ? WHERE id = ?");
                    $upd->execute([$item['qty'], $item['id']]);
                }

            $this->conn->commit();
            return ['success' => true, 'pedido_id' => $pedidoId];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function listarPedidosPendentes() {
        $stmt = $this->conn->query("
            SELECT p.*, c.nome as cliente_nome 
            FROM pedido p
            LEFT JOIN cliente c ON p.id_cliente = c.id
            WHERE p.estado = 'pendente'
            ORDER BY p.data_pedido DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPedidoItens($id_pedido) {
        // Busca dados do cliente e do pedido (para forma_pagamento)
        $stmt = $this->conn->prepare("
            SELECT c.*, p.forma_pagamento
            FROM pedido p
            LEFT JOIN cliente c ON p.id_cliente = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id_pedido]);
        $data_pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        // Busca itens do pedido
        // Note: tabela de produtos no banco é 'produto' (singular) conforme outras consultas
        $stmt = $this->conn->prepare("
            SELECT pi.*, pr.nome as produto_nome
            FROM pedido_itens pi
            LEFT JOIN produto pr ON pi.id_produto = pr.id
            WHERE pi.id_pedido = ?
        ");
        $stmt->execute([$id_pedido]);
        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'itens' => $itens, 
            'cliente' => [
                'id' => $data_pedido['id'] ?? null,
                'nome' => $data_pedido['nome'] ?? null
            ],
            'forma_pagamento' => $data_pedido['forma_pagamento'] ?? 'N/A'
        ];
    }

    public function atualizarEstadoPedido($id_pedido, $estado) {
        try {
            $this->conn->beginTransaction();
            
            // Atualiza o estado do pedido
            $stmt = $this->conn->prepare("
                UPDATE pedido 
                SET estado = ?, lido = 1
                WHERE id = ?
            ");
            $stmt->execute([$estado, $id_pedido]);

            // Se foi marcado como "atendido", cria uma venda
            if ($estado === 'atendido') {
                // Verifica se já existe venda
                $checkStmt = $this->conn->prepare("SELECT id FROM venda WHERE id_pedido = ? LIMIT 1");
                $checkStmt->execute([$id_pedido]);
                
                if ($checkStmt->rowCount() === 0) {
                    // Cria nova venda (aparecerá em VENDAS)
                    $stmt = $this->conn->prepare("
                        INSERT INTO venda (id_pedido, id_vendedor, data_venda, total, valor_pago, troco, estado)
                        SELECT 
                            id, 
                            ?, 
                            NOW(), 
                            total,
                            0,
                            0,
                            'pendente'
                        FROM pedido WHERE id = ?
                    ");
                    $stmt->execute([$_SESSION['vendedor_id'], $id_pedido]);
                }
            }

            $this->conn->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function checkNewOrders() {
        $stmt = $this->conn->query("
            SELECT COUNT(*) as count 
            FROM pedido 
            WHERE estado = 'pendente' AND lido = 0
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}
