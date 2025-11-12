<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/Produto.php';

/**
 * Controlador responsável pela gestão dos produtos.
 * Segue o padrão MVC para facilitar o uso nas views (ex: dashboard_cliente.php)
 */
class ProdutoController {

    private $produto;

    public function __construct() {
        global $conn;
        $this->produto = new Produto($conn);
    }

    /**
     * Retorna todos os produtos cadastrados
     */
    public function listarProdutos() {
        return $this->produto->listar();
    }

    /**
     * Cria um novo produto com os dados recebidos
     */
    public function criarProduto($dados) {
        return $this->produto->criar($dados);
    }

    /**
     * Atualiza um produto existente
     */
    public function atualizarProduto($id, $dados) {
        return $this->produto->atualizar($id, $dados);
    }

    /**
     * Deleta um produto pelo ID
     */
    public function deletarProduto($id) {
        return $this->produto->deletar($id);
    }
}
?>
