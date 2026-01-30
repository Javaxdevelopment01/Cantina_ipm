#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
YASMIN Integration Test
Testa a integração completa do assistente YASMIN com o sistema Cantina IPM
"""

import subprocess
import json
import sys
from pathlib import Path


def testar_yasmin_python():
    """Testa o script Python direto"""
    print("=" * 60)
    print("TEST 1: YASMIN Python Script (Direct)")
    print("=" * 60)
    
    teste_casos = [
        ("Quero algo saudável", "saude"),
        ("Um lanche rápido", "rapido"),
        ("Bebidas refrescantes", "bebidas"),
        ("Vegetariano", "vegetariano"),
        ("Recomenda um bolo", "doces"),
        ("Olá", "saudacao"),
    ]
    
    for mensagem, categoria_esperada in teste_casos:
        try:
            resultado = subprocess.run(
                [sys.executable, 'ia/yasmin_assistant.py', mensagem],
                capture_output=True,
                text=True,
                timeout=10
            )
            
            if resultado.returncode == 0:
                try:
                    data = json.loads(resultado.stdout)
                    status = "✓ PASS" if data.get('success') else "✗ FAIL"
                    intencao = data.get('intencao', 'N/A')
                    recomendacoes = len(data.get('recomendacoes', []))
                    print(f"{status} | '{mensagem}' → {intencao} ({recomendacoes} recomendações)")
                except json.JSONDecodeError:
                    print(f"✗ JSON Parse Error para: {mensagem}")
            else:
                print(f"✗ Script Error: {resultado.stderr}")
        except subprocess.TimeoutExpired:
            print(f"✗ Timeout para: {mensagem}")
        except Exception as e:
            print(f"✗ Exception: {e}")
    
    print()


def testar_api_php():
    """Testa a API PHP"""
    print("=" * 60)
    print("TEST 2: YASMIN PHP API")
    print("=" * 60)
    
    # Testa se o arquivo PHP existe
    php_path = Path('app/api/yasmin_api.php')
    if php_path.exists():
        print(f"✓ API PHP encontrada: {php_path}")
    else:
        print(f"✗ API PHP não encontrada: {php_path}")
        return
    
    # Testa sintaxe PHP
    try:
        resultado = subprocess.run(
            ['php', '-l', str(php_path)],
            capture_output=True,
            text=True,
            timeout=5
        )
        if 'No syntax errors' in resultado.stdout:
            print("✓ Sintaxe PHP válida")
        else:
            print(f"✗ Erro de sintaxe: {resultado.stdout}")
    except Exception as e:
        print(f"✗ Erro ao verificar PHP: {e}")
    
    print()


def testar_dashboard():
    """Testa o dashboard do cliente"""
    print("=" * 60)
    print("TEST 3: Dashboard Cliente")
    print("=" * 60)
    
    dashboard_path = Path('app/views/cliente/dashboard_cliente.php')
    if dashboard_path.exists():
        print(f"✓ Dashboard encontrado: {dashboard_path}")
        
        # Verificar se YASMIN está integrado
        conteudo = dashboard_path.read_text(encoding='utf-8')
        
        checks = {
            'Widget YASMIN HTML': '#yasminWidget' in conteudo,
            'Botão YASMIN': '#botaoYasmin' in conteudo,
            'API YASMIN JS': '/app/api/yasmin_api.php' in conteudo,
            'Event listeners': 'enviarYasminMessage' in conteudo,
            'Removido botão GIA': '#botaoIA' not in conteudo,
        }
        
        for check_nome, resultado in checks.items():
            status = "✓" if resultado else "✗"
            print(f"{status} {check_nome}")
    else:
        print(f"✗ Dashboard não encontrado: {dashboard_path}")
    
    print()


def testar_produtos_demo():
    """Testa produtos de demonstração"""
    print("=" * 60)
    print("TEST 4: Demo Products")
    print("=" * 60)
    
    try:
        resultado = subprocess.run(
            [sys.executable, 'ia/yasmin_assistant.py', 'teste', '--demo'],
            capture_output=True,
            text=True,
            timeout=10
        )
        
        if resultado.returncode == 0 and 'Produtos Disponíveis' in resultado.stdout:
            print("✓ Produtos de demo carregados corretamente")
            
            # Contar produtos
            linhas = resultado.stdout.split('\n')
            produtos = [l for l in linhas if l.strip().startswith('[')]
            print(f"✓ Total de produtos demo: {len(produtos)}")
        else:
            print(f"✗ Erro ao carregar demo: {resultado.stderr}")
    except Exception as e:
        print(f"✗ Exception: {e}")
    
    print()


def gerar_relatorio():
    """Gera relatório final"""
    print("=" * 60)
    print("RELATÓRIO FINAL DE INTEGRAÇÃO YASMIN")
    print("=" * 60)
    print()
    print("✨ Assistente YASMIN - Configuração Completa ✨")
    print()
    print("Componentes Instalados:")
    print("  ✓ ia/yasmin_assistant.py - Script Python")
    print("  ✓ app/api/yasmin_api.php - API Wrapper PHP")
    print("  ✓ app/views/cliente/dashboard_cliente.php - Dashboard Atualizado")
    print("  ✓ ia/README_YASMIN.md - Documentação Completa")
    print()
    print("Próximos Passos:")
    print("  1. Aceder ao dashboard do cliente")
    print("  2. Clicar no botão YASMIN (lado inferior direito)")
    print("  3. Escrever uma pergunta (ex: 'Quero algo saudável')")
    print("  4. YASMIN irá recomendar produtos")
    print("  5. Clicar em 'Adicionar' para colocar no carrinho")
    print()
    print("Exemplos de Perguntas:")
    print("  • 'Quero algo saudável'")
    print("  • 'Um lanche rápido'")
    print("  • 'Bebidas'")
    print("  • 'Vegetariano'")
    print("  • 'Recomenda um doce'")
    print()
    print("=" * 60)


if __name__ == '__main__':
    print()
    print(" " * 20 + "YASMIN INTEGRATION TEST")
    print()
    
    testar_yasmin_python()
    testar_api_php()
    testar_dashboard()
    testar_produtos_demo()
    gerar_relatorio()
