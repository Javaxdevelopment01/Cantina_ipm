# YASMIN - Assistente Virtual da Cantina IPM

## Visão Geral

**YASMIN** é uma assistente virtual inteligente que ajuda os clientes a escolherem produtos no cardápio digital da Cantina IPM. Desenvolvida em **Python puro** (sem dependências externas), integra-se perfeitamente com o sistema PHP existente.

## Recursos Principais

✨ **Recomendações Inteligentes**

- Detecta automaticamente as preferências do cliente (saudável, rápido, vegetariano, etc.)
- Recomenda até 3 produtos relevantes com preços e descrições
- Aprendizado baseado em keywords e categorias

🎯 **Intenções Reconhecidas**

- Pratos quentes / Refeições
- Bebidas e sucos
- Lanches rápidos
- Doces e sobremesas
- Opções saudáveis
- Produtos vegetarianos
- Pagamento e checkout

💬 **Interface Amigável**

- Chat integrado no dashboard do cliente
- Botão flutuante com animações
- Mensagens formatadas com emojis
- Botões de "Adicionar ao Carrinho" integrados

🔧 **Fácil Integração**

- API REST em PHP (`yasmin_api.php`)
- Executa script Python via CLI
- Resposta JSON estruturada
- Sem dependências externas (puro Python 3.6+)

## Instalação e Configuração

### Pré-requisitos

- Python 3.6+ instalado no servidor
- PHP 7.4+ com capacidade de executar comandos (função `exec()`)
- Sistema operacional: Windows, Linux ou macOS

### Verificar Python

```bash
# Verificar se Python está instalado
python --version
# ou
python3 --version
```

### Arquivos

| Arquivo                                   | Descrição                                    |
| ----------------------------------------- | -------------------------------------------- |
| `ia/yasmin_assistant.py`                  | Script principal da assistente (Python puro) |
| `app/api/yasmin_api.php`                  | Wrapper PHP que integra YASMIN com o sistema |
| `app/views/cliente/dashboard_cliente.php` | Dashboard do cliente com widget YASMIN       |

## Como Funciona

### Fluxo de Requisição

```
[Cliente] → [Chat Widget] → [PHP API] → [Python YASMIN] → [JSON Response]
   ↓                             ↓
Digita mensagem              yasmin_api.php
                                ↓
                          executa via CLI
                                ↓
                         yasmin_assistant.py
                                ↓
                         Analisa intenção
                         Filtra produtos
                         Gera recomendações
                                ↓
                            [JSON Output]
```

### Análise de Intenção

O YASMIN detecta automaticamente o que o cliente procura:

```python
# Exemplos
"Quero algo saudável" → Categoria: saude → Recomenda: Salada, Suco
"Um lanche rápido" → Categoria: rapido → Recomenda: Sanduíche
"Bebida refrescante" → Categoria: bebidas → Recomenda: Sucos
"Vegetariano" → Tags: vegetariano → Recomenda: Salada, Sanduíche
```

## API REST

### Endpoint

**POST** `/app/api/yasmin_api.php`

### Request

```json
{
  "mensagem": "Quero algo saudável",
  "produtos_json": "/path/to/produtos.json" (opcional)
}
```

### Response Sucesso

```json
{
  "success": true,
  "mensagem": "Ótimo! Recomendo estes produtos...",
  "intencao": "saude",
  "categoria": "saude",
  "recomendacoes": [
    {
      "id": 2,
      "nome": "Suco Natural de Laranja",
      "categoria": "bebidas",
      "preco": 150.0,
      "descricao": "Suco fresco e natural",
      "tags": ["saudável", "bebida", "refrescante"],
      "disponivel": true
    },
    ...
  ],
  "timestamp": "2025-11-30T18:08:49.650116"
}
```

### Response Erro

```json
{
  "success": false,
  "error": "Mensagem vazia"
}
```

## Uso CLI (Python)

### Modo Básico

```bash
python ia/yasmin_assistant.py "Quero algo saudável"
```

### Modo Demo (mostra produtos padrão)

```bash
python ia/yasmin_assistant.py "Quero algo saudável" --demo
```

### Modo Interativo

```bash
python ia/yasmin_assistant.py
# Inicia conversa interativa até digitar "sair"
```

### Com Arquivo de Produtos

```bash
python ia/yasmin_assistant.py "Quero algo saudável" --produtos /path/to/produtos.json
```

### Modo Verbose (debug)

```bash
python ia/yasmin_assistant.py "Quero algo saudável" --verbose
```

## Integração com Banco de Dados (Opcional)

Para usar produtos reais do banco de dados em vez dos produtos padrão:

### 1. Criar endpoint que exporta produtos

```php
<?php
// app/api/exportar_produtos.php
header('Content-Type: application/json');
// Buscar produtos do banco de dados
$produtos = $controller->listarProdutos();
echo json_encode($produtos);
```

### 2. Gerar JSON periodicamente

```bash
# Cron job que atualiza lista de produtos a cada hora
0 * * * * curl -s http://cantina-ipm/app/api/exportar_produtos.php > /path/to/produtos.json
```

### 3. Usar no YASMIN

```javascript
// No dashboard_cliente.php, passar o caminho do arquivo
const resp = await fetch(BASE_URL + "/app/api/yasmin_api.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    mensagem: msg,
    produtos_json: "/path/to/produtos.json",
  }),
});
```

## Estrutura de Produtos

O YASMIN espera produtos com este formato JSON:

```json
[
  {
    "id": 1,
    "nome": "Prato de Arroz com Frango",
    "categoria": "pratos_quentes",
    "preco": 450.0,
    "descricao": "Arroz branco, frango grelhado e legumes",
    "tags": ["proteína", "refeição completa", "popular"],
    "disponivel": true
  },
  ...
]
```

### Categorias Suportadas

- `pratos_quentes` - Pratos principais
- `bebidas` - Bebidas diversas
- `lanches` - Lanches rápidos
- `doces` - Sobremesas
- `saude` - Opções saudáveis

### Tags Comuns

- `saudável` - Prato saudável
- `vegetariano` - Sem carne
- `rápido` - Pronto rapidamente
- `proteína` - Alto teor de proteína
- `refrescante` - Bebida refrescante
- `popular` - Produto popular
- `barato` - Preço baixo

## Troubleshooting

### Erro: "Python não encontrado"

**Problema:** `Error: Python não encontrado no sistema`

**Solução:**

```bash
# Verificar se Python está instalado
python --version

# Se não estiver, instalar:
# Windows: https://www.python.org
# Linux: sudo apt-get install python3
# macOS: brew install python3
```

### Erro: "YASMIN não está configurado"

**Problema:** `Error: YASMIN não está configurado`

**Solução:**

1. Verificar se `ia/yasmin_assistant.py` existe
2. Verificar permissões de leitura do arquivo
3. Limpar cache do navegador

### Resposta Lenta

**Problema:** O YASMIN demora para responder

**Solução:**

1. Primeira requisição é mais lenta (Python iniciando)
2. Considerar usar cache de respostas frequentes
3. Aumentar timeout em `yasmin_api.php` se necessário

### Caracteres Acentuados Errados

**Problema:** Acentos aparecem incorretamente

**Solução:**

- Garantir UTF-8 em headers PHP: `header('Content-Type: application/json; charset=utf-8');` ✓
- Script Python com `# -*- coding: utf-8 -*-` ✓
- Já está configurado nos arquivos fornecidos

## Exemplos de Conversas

### Exemplo 1: Recomendação Saudável

```
Cliente: "Quero algo saudável"
YASMIN: "Ótimo! Recomendo estes produtos para ti:
• Suco Natural de Laranja - Kz 150.00
• Salada Verde - Kz 250.00
✨ Algum destes te interessa?"
```

### Exemplo 2: Lanche Rápido

```
Cliente: "Preciso de algo rápido"
YASMIN: "Entendi! Recomendo estes lanches rápidos:
• Sanduíche de Queijo - Kz 200.00
• Pastel de Carne - Kz 180.00
✨ Algum destes te interessa?"
```

### Exemplo 3: Opção Vegetariana

```
Cliente: "Vegetariano"
YASMIN: "Ótimo! Recomendo estes produtos vegetarianos para ti:
• Sanduíche de Queijo - Kz 200.00
• Salada Verde - Kz 250.00
✨ Algum destes te interessa?"
```

## Recursos Futuros

🚀 **Melhorias Planejadas**

- [ ] Integração com histórico de compras do cliente
- [ ] Aprendizado personalizado por cliente
- [ ] Reconhecimento de voz em português
- [ ] Integração com API de previsão de demanda
- [ ] Multi-idioma (inglês, espanhol, etc.)
- [ ] Análise de tendências de vendas
- [ ] Sugestões baseadas em horário (café da manhã vs almoço)
- [ ] Integração com sistemas de promoção

## Suporte

Para problemas ou sugestões:

1. Verificar logs em `app/logs/`
2. Testar diretamente: `python ia/yasmin_assistant.py "teste"`
3. Verificar permissões de arquivo e diretórios
4. Consultar documentação em cada arquivo fonte

## Licença

Desenvolvido para o Sistema Cantina IPM © 2025

---

**YASMIN: Tornando a escolha de produtos mais fácil e inteligente!** ✨
