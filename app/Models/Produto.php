<?php

require_once __DIR__ . '/../../config/database.php';


class Produto {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function listar() {
        $stmt = $this->conn->query("SELECT p.*, c.nome as categoria_nome FROM produto p LEFT JOIN categoria c ON p.categoria_id = c.id");
        return $stmt->fetchAll();
    }

    public function criar($dados) {
        $stmt = $this->conn->prepare("INSERT INTO produto (nome, descricao, preco, quantidade, categoria_id) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$dados['nome'], $dados['descricao'], $dados['preco'], $dados['quantidade'], $dados['categoria_id']]);
    }

    public function buscar($id) {
        $stmt = $this->conn->prepare("SELECT * FROM produto WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function atualizar($id, $dados) {
        $stmt = $this->conn->prepare("UPDATE produto SET nome=?, descricao=?, preco=?, quantidade=?, categoria_id=? WHERE id=?");
        return $stmt->execute([$dados['nome'], $dados['descricao'], $dados['preco'], $dados['quantidade'], $dados['categoria_id'], $id]);
    }

    public function deletar($id) {
        $stmt = $this->conn->prepare("DELETE FROM produto WHERE id=?");
        return $stmt->execute([$id]);
    }
}
