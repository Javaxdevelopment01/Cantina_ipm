<?php
// Inclui cabeçalho e menu
include_once __DIR__ . '/../../../includes/header.php';
// include_once __DIR__ . '/../../../includes/menu.php';

// Recebe dados do produto
$produto = isset($_GET['produto']) ? htmlspecialchars($_GET['produto']) : 'Produto Desconhecido';
$preco = isset($_GET['preco']) ? number_format($_GET['preco'], 2, ',', '.') : '0,00';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-success text-white text-center rounded-top-4">
                    <h4>💳 Finalizar Pagamento</h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted text-center mb-4">
                        Revê os detalhes da tua compra e escolhe o método de pagamento.
                    </p>

                    <div class="border p-3 mb-4 rounded">
                        <h5 class="fw-bold mb-2 text-center"><?php echo $produto; ?></h5>
                        <h6 class="text-center text-success fw-bold">Preço: <?php echo $preco; ?> Kz</h6>
                    </div>

                    <form id="formPagamento">
                        <div class="form-group mb-3">
                            <label class="form-label fw-semibold">Nome Completo</label>
                            <input type="text" name="nome" class="form-control" placeholder="Ex: João António" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label fw-semibold">Número do Estudante</label>
                            <input type="text" name="numero_estudante" class="form-control" placeholder="Ex: 20230045" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label fw-semibold">Seleciona o Método de Pagamento</label>
                            <select name="metodo" class="form-select" required>
                                <option value="">-- Seleciona --</option>
                                <option value="mao">À Mão</option>
                                <option value="cartao">Cartão</option>
                                <option value="online">Pagamento Online</option>
                            </select>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success px-5">Confirmar Compra</button>
                            <a href="/cantina_ipm/app/views/cliente/dashboard_cliente.php" class="btn btn-secondary ms-2">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Mensagem de sucesso -->
            <div id="mensagemSucesso" class="alert alert-success text-center mt-4 d-none">
                ✅ Compra realizada com sucesso. O atendente foi notificado para preparar o teu pedido.
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../../../includes/footer.php'; ?>

<script>
document.getElementById('formPagamento').addEventListener('submit', async function(e) {
    e.preventDefault();

    const form = e.target;
    const nome = form.nome.value.trim();
    const numero = form.numero_estudante.value.trim();
    const metodo = form.metodo.value;

    if (!nome || !numero || !metodo) {
        alert('Por favor, preenche todos os campos antes de continuar.');
        return;
    }

    const dados = {
        nome,
        numero,
        metodo,
        produto: '<?php echo $produto; ?>',
        preco: '<?php echo $preco; ?>'
    };

    if(metodo === 'online'){
        // pagamento online real
        try{
            const res = await fetch('processar_pagamento_online.php', {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify(dados)
            });
            const json = await res.json();
            if(json.ok){
                // redireciona para link de pagamento do gateway
                window.location.href = json.link_pagamento;
            } else {
                alert('Erro ao iniciar pagamento online: ' + json.msg);
            }
        }catch(err){
            console.error(err);
            alert('Erro ao processar pagamento online.');
        }
    } else {
        // métodos presenciais mantêm fluxo atual
        form.classList.add('d-none');
        document.getElementById('mensagemSucesso').classList.remove('d-none');
        setTimeout(() => {
            alert('Pedido enviado ao atendente. A tua refeição será entregue em breve.');
            window.location.href = '/cantina_ipm/app/views/cliente/dashboard_cliente.php';
        }, 2000);
    }
});
</script>

<style>
body {
    background-color: #f5f7fa;
}
.card {
    background-color: #ffffff;
    transition: all 0.3s ease-in-out;
}
.card:hover {
    transform: scale(1.02);
}
.btn-success {
    background-color: #3eb489;
    border: none;
}
.btn-success:hover {
    background-color: #34a078;
}
</style>
