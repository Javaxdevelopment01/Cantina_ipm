<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if(empty($data['nome']) || empty($data['numero']) || empty($data['produto']) || empty($data['preco'])){
    echo json_encode(['ok'=>false,'msg'=>'Dados incompletos']);
    exit;
}

require_once __DIR__.'/../../../config/database.php';

try{
    $pdo = Conexao::getInstance();
    $stmt = $pdo->prepare("INSERT INTO vendas (nome_cliente, numero_estudante, produto, preco, metodo_pagamento, status_pagamento, data_criacao) VALUES (?,?,?,?,?,?,NOW())");
    $stmt->execute([
        $data['nome'],
        $data['numero'],
        $data['produto'],
        str_replace(',','.', $data['preco']),
        'online',
        'pendente'
    ]);
    $venda_id = $pdo->lastInsertId();

    // Aqui você chamaria a API do gateway real
    // Por enquanto, link de teste para simulação
    $link_pagamento = "/cantina_ipm/app/views/cliente/pagamento_online_simulado.php?venda=$venda_id";

    echo json_encode(['ok'=>true,'link_pagamento'=>$link_pagamento]);
}catch(Exception $e){
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
