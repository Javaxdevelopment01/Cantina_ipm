#!/usr/bin/env python
# -*- coding: utf-8 -*-
import sys
import os
sys.path.insert(0, os.path.dirname(__file__))

from yasmin_assistant import YASMINVendor
import json

vendor_context = {
    'totalPedidos': 10,
    'pedidosPendentes': 3,
    'totalClientes': 25,
    'quantidadeEstoque': 500,
    'produtosAlerta': [{'nome': 'Café', 'quantidade': 2}],
    'produtosMaisVendidos': [{'nome': 'Pão', 'total_vendido': 50, 'receita': 1000}]
}

print("=== Test YASMIN Vendor ===\n")

yasmin = YASMINVendor(vendor_context=vendor_context)

# Test 1: Quantos pedidos
print("Test 1: Quantos pedidos?")
result = yasmin.processar_mensagem_vendor("quantos pedidos")
print(json.dumps(result, ensure_ascii=False, indent=2))
print("\n" + "="*50 + "\n")

# Test 2: Estoque
print("Test 2: Qual é o estoque?")
result = yasmin.processar_mensagem_vendor("qual e o estoque")
print(json.dumps(result, ensure_ascii=False, indent=2))
print("\n" + "="*50 + "\n")

# Test 3: Clientes
print("Test 3: Quantos clientes?")
result = yasmin.processar_mensagem_vendor("quantos clientes temos")
print(json.dumps(result, ensure_ascii=False, indent=2))
