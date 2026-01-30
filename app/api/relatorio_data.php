<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Verificar se é vendedor ou admin
if(!isset($_SESSION['vendedor_id']) && !isset($_SESSION['admin_id'])){
    echo json_encode([
        'success' => false,
        'error' => 'não autorizado'
    ]);
    http_response_code(401);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try{
    // Indicadores
    $ind = [];

    // Total de vendas (todas)
    $stmt = $conn->query("SELECT IFNULL(SUM(total),0) AS total_vendas FROM venda");
    $indRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $ind['total_vendas'] = floatval($indRow['total_vendas']);

    // Total de clientes
    $stmt = $conn->query("SELECT COUNT(*) AS total_clientes FROM cliente");
    $ind['total_clientes'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['total_clientes']);

    // Produtos em stock (count where quantidade > 0)
    $stmt = $conn->query("SELECT COUNT(*) AS produtos_stock FROM produto WHERE quantidade > 0");
    $ind['produtos_em_stock'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['produtos_stock']);

    // Produtos com baixo stock (<5)
    $stmt = $conn->query("SELECT COUNT(*) AS produtos_baixo FROM produto WHERE quantidade < 5");
    $ind['produtos_baixo_stock'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['produtos_baixo']);

    // Lucro mensal (exemplo simplificado: soma total do mês)
    $stmt = $conn->prepare("SELECT IFNULL(SUM(total),0) AS total_mes FROM venda WHERE MONTH(data_venda)=MONTH(CURDATE()) AND YEAR(data_venda)=YEAR(CURDATE())");
    $stmt->execute();
    $ind['vendas_mes_atual'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total_mes']);

    // Séries: vendas por mês (últimos 12 meses)
    $series = [];
    $labels = [];
    $stmt = $conn->prepare("SELECT DATE_FORMAT(data_venda, '%Y-%m') as ym, IFNULL(SUM(total),0) as total FROM venda WHERE data_venda >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY ym ORDER BY ym ASC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $r){
        $labels[] = $r['ym'];
        $series[] = floatval($r['total']);
    }

    // Top produtos em estoque (por valor em estoque = quantidade * preço)
    $stmt = $conn->prepare("SELECT p.nome, p.quantidade, p.preco, (p.quantidade * p.preco) as valor_estoque 
                           FROM produto p 
                           WHERE p.quantidade > 0 
                           ORDER BY valor_estoque DESC LIMIT 8");
    $stmt->execute();
    $top = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vendas por vendedor (total)
    $stmt = $conn->prepare("SELECT vd.nome, IFNULL(SUM(v.total),0) AS total_vendas, 
                           COUNT(v.id) as num_vendas
                           FROM vendedor vd 
                           LEFT JOIN venda v ON v.id_vendedor = vd.id 
                           GROUP BY vd.id 
                           ORDER BY total_vendas DESC");
    $stmt->execute();
    $vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'success'=>true,
        'indicadores'=>$ind,
        'series'=>['labels'=>$labels,'data'=>$series],
        'top_produtos'=>$top,
        'vendas_por_vendedor'=>$vendedores
    ];

    echo json_encode($response);

}catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}

?>
