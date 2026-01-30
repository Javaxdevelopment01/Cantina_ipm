# 🐛 BUG RESOLVIDO: Pedido passa a "Desconhecido" após gerar factura

## ❌ Problema Identificado

**Fluxo original bugado:**

1. ✓ Cria pedido → estado `'pendente'`
2. ✓ Marca como atendido → estado `'atendido'`
3. ✓ Abre modal de factura → preenchimento de campos (cliente, vendedor, preço)
4. ✓ Gera factura → estado muda para `'finalizado'`
5. ❌ Volta na aba de Pedidos → pedido aparece como **"Desconhecido"**

## 🔍 Causa Raiz

### Problema 1: Estados Não Reconhecidos

Em `app/views/adm/gestao_pedidos.php`, a lógica só reconhecia:

- `'pendente'` → Pendente (amarelo)
- `'atendido'` → Atendido (verde)
- `'cancelado'` → Cancelado (vermelho)

Quando o pedido era atualizado para `'finalizado'` em `processar_venda.php`, **não havia case para este estado**, então exibia o padrão: **"Desconhecido"** (cinzento)

### Problema 2: Fluxo de Estados Confuso

- **`pedidoController.php`**: Ao marcar como "atendido", criava uma venda com estado `'finalizada'`
- **`processar_venda.php`**: Ao gerar factura, alterava o pedido para `'finalizado'`

Isso criava dois pontos de atualização diferentes e estados redundantes.

## ✅ Solução Implementada

### 1. Adicionar reconhecimento do estado "Finalizado"

**Arquivo:** `app/views/adm/gestao_pedidos.php`

```php
} elseif ($estado === 'finalizado') {
    $badgeClass = 'bg-info-subtle text-info';
    $label = 'Finalizado';
}
```

Agora o sistema exibe: **"Finalizado"** (azul) em vez de "Desconhecido"

### 2. Simplificar o fluxo de atualização de estados

**Arquivo:** `app/controllers/pedidoController.php`

Removido a lógica que criava venda automaticamente ao marcar como atendido:

```php
// ❌ REMOVIDO: Lógica de criar venda
if ($estado === 'atendido') {
    // INSERT INTO venda ...
}

// ✅ AGORA: Apenas atualiza o estado
// pendente → atendido → finalizado
UPDATE pedido SET estado = ?, lido = 1 WHERE id = ?
```

## 📊 Fluxo Corrigido

| Estado         | Quando                       | Onde                                 | Badge       |
| -------------- | ---------------------------- | ------------------------------------ | ----------- |
| **pendente**   | Pedido criado                | Cliente faz pedido                   | 🟡 Amarelo  |
| **atendido**   | Vendedor marca atender       | Vendedor clica "Atender"             | 🟢 Verde    |
| **finalizado** | Factura gerada com pagamento | Após preencher preço e gerar factura | 🔵 Azul     |
| **cancelado**  | Pedido cancelado             | Vendedor cancela pedido              | 🔴 Vermelho |

## 🧪 Como Testar

1. Acessa: http://localhost/test_fluxo_pedidos.php
2. Verifica os estados encontrados
3. Faz um novo pedido até gerar factura
4. Volta em Pedidos e verifica se aparece **"Finalizado"** (não "Desconhecido")

## 📝 Resumo das Mudanças

| Arquivo                | Mudança                                                            |
| ---------------------- | ------------------------------------------------------------------ |
| `gestao_pedidos.php`   | Adicionado case para `'finalizado'` → exibe como "Finalizado" azul |
| `pedidoController.php` | Removido INSERT de venda no método `atualizarEstadoPedido()`       |
| `processar_venda.php`  | ✓ Já estava correto, apenas atualiza para `'finalizado'`           |

---

**Status:** ✅ RESOLVIDO
**Impacto:** Fluxo de pedidos agora é consistente e intuitivo
