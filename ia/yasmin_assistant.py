#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
YASMIN - Virtual Assistant for Cantina IPM
Helps customers choose products based on preferences and recommendations.

Usage:
    python yasmin_assistant.py "<user_message>" [--produtos_json "products.json"] [--verbose]
    
    Comunicates via JSON:
    - Input: user message (Portuguese)
    - Output: JSON with assistant response, recommendations, and metadata
"""

import json
import sys
import re
import argparse
from datetime import datetime
from typing import Dict, List, Tuple, Optional
import os
import io
import base64
import tempfile
import difflib

try:
    import requests
except Exception:
    requests = None
try:
    from gtts import gTTS
except Exception:
    gTTS = None
try:
    import pyttsx3
except Exception:
    pyttsx3 = None

# Configurar stdout para UTF-8 em Windows
if sys.platform == 'win32':
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')


class YASMINAssistant:
    """
    YASMIN Assistant - Recomenda produtos e ajuda na escolha.
    Usa análise de linguagem natural com fuzzy matching para tolerância a erros.
    """
    
    def __init__(self, produtos_json: Optional[str] = None, verbose: bool = False):
        """
        Inicializa o assistente YASMIN.
        
        Args:
            produtos_json: Caminho para arquivo JSON com produtos
            verbose: Mostra logs detalhados
        """
        self.verbose = verbose
        self.produtos = []
        self.historico = []
        self.nome = "YASMIN"
        self.categoria_atual = None
        
        # Keywords para detecção de intenção
        self.keywords = {
            'pratos_quentes': ['prato', 'quente', 'comida', 'refeição', 'almoço', 'principal', 'arroz', 'feijão', 'frango', 'carne', 'peixe', 'jantar', 'fome'],
            'bebidas': ['bebida', 'suco', 'sumo', 'refrigerante', 'água', 'chá', 'café', 'refri', 'beber', 'sede'],
            'lanches': ['lanche', 'snack', 'sanduíche', 'sando', 'pastel', 'bolo', 'biscoito', 'rápido', 'merenda'],
            'doces': ['doce', 'sobremesa', 'chocolate', 'pudim', 'bolo', 'açúcar', 'candy', 'gelado', 'sorvete'],
            'saude': ['saudável', 'dieta', 'leve', 'salada', 'fruta', 'natural', 'bio', 'vegetal', 'fit', 'fitness'],
            'rapido': ['rápido', 'pressa', 'rápida', 'pronto', 'preparado', 'já feito', 'urgente'],
            'barato': ['barato', 'preço', 'caro', 'desconto', 'promoção', 'oferta', 'económico', 'economico', 'baratinho'],
            'vegetariano': ['vegetariano', 'vegano', 'sem carne', 'verdura', 'legume', 'alface', 'salada', 'vegan'],
            'recomendacao': ['recomenda', 'sugere', 'qual é o melhor', 'mais popular', 'mais vendido', 'famoso', 'indica', 'sugestão'],
        }
        
        # Mensagens padrão
        self.saudacoes = {
            'inicial': f"Olá! Sou a {self.nome}, tua assistente virtual da Cantina IPM.\nEstou aqui para ajudarte a escolher o melhor produto para ti!",
            'help': "Posso ajudarte a:\n• Escolher pratos quentes\n• Recomendar bebidas\n• Sugerir lanches\n• Procurar por preferências (saudável, vegetariano, etc.)\n• Mostrar o melhor custo-benefício\n\nO que procuras hoje?",
            'nao_entendi': "Desculpa, não compreendi bem. Podes reformular a pergunta?",
        }
        
        self.carregar_produtos(produtos_json)
    
    def carregar_produtos(self, json_path: Optional[str] = None):
        """Carrega produtos de um arquivo JSON ou retorna lista vazia."""
        if json_path and os.path.isfile(json_path):
            try:
                with open(json_path, 'r', encoding='utf-8') as f:
                    self.produtos = json.load(f)
                if self.verbose:
                    print(f"[YASMIN] Carregados {len(self.produtos)} produtos de {json_path}", file=sys.stderr)
            except Exception as e:
                if self.verbose:
                    print(f"[YASMIN] Erro ao carregar produtos: {e}", file=sys.stderr)
                self.produtos = []
        else:
            # Produtos padrão de demonstração (caso arquivo não exista)
            self.produtos = self._get_produtos_demo()
    
    def _get_produtos_demo(self) -> List[Dict]:
        """Retorna lista de produtos de demonstração."""
        return [
            {
                'id': 1,
                'nome': 'Prato de Arroz com Frango',
                'categoria': 'pratos_quentes',
                'preco': 450.0,
                'descricao': 'Arroz branco, frango grelhado e legumes',
                'tags': ['proteína', 'refeição completa', 'popular'],
                'disponivel': True
            },
            {
                'id': 2,
                'nome': 'Suco Natural de Laranja',
                'categoria': 'bebidas',
                'preco': 150.0,
                'descricao': 'Suco fresco e natural',
                'tags': ['saudável', 'bebida', 'refrescante'],
                'disponivel': True
            },
            {
                'id': 3,
                'nome': 'Sanduíche de Queijo',
                'categoria': 'lanches',
                'preco': 200.0,
                'descricao': 'Pão integral com queijo fresco',
                'tags': ['lanche', 'rápido', 'vegetariano'],
                'disponivel': True
            },
            {
                'id': 4,
                'nome': 'Salada Verde',
                'categoria': 'saude',
                'preco': 250.0,
                'descricao': 'Alface, tomate, cebola e azeite',
                'tags': ['saudável', 'leve', 'vegetariano'],
                'disponivel': True
            },
            {
                'id': 5,
                'nome': 'Bolo de Chocolate',
                'categoria': 'doces',
                'preco': 120.0,
                'descricao': 'Bolo caseiro de chocolate belga',
                'tags': ['doce', 'sobremesa', 'favorito'],
                'disponivel': True
            },
        ]
    
    def detectar_intencao(self, mensagem: str) -> Tuple[str, List[str]]:
        """
        Detecta a intenção do usuário baseado em keywords com fuzzy matching.
        Retorna (intenção_principal, [categorias_relevantes])
        """
        msg_lower = mensagem.lower().strip()
        palavras_msg = re.findall(r'\w+', msg_lower)
        
        # Pontuação para cada categoria
        scores = {}
        
        for categoria, keywords in self.keywords.items():
            score = 0
            # 1. Match exato de substrings (old method, very reliable)
            score += sum(2 for kw in keywords if kw in msg_lower)
            
            # 2. Fuzzy matching palavra a palavra (para typos)
            # Se a palavra do usuário for >= 80% similar a uma keyword
            for palavra in palavras_msg:
                if len(palavra) < 3: continue # ignora palavras muito curtas
                matches = difflib.get_close_matches(palavra, keywords, n=1, cutoff=0.6)
                if matches:
                    score += 1 # Bonus por match aproximado
            
            if score > 0:
                scores[categoria] = score
        
        # Ordena por score
        if scores:
            categorias = sorted(scores.items(), key=lambda x: x[1], reverse=True)
            intencao_principal = categorias[0][0]
            categorias_relevantes = [cat for cat, _ in categorias]
            return intencao_principal, categorias_relevantes
        
        return 'generico', []
    
    def filtrar_produtos(self, categoria: Optional[str] = None, 
                        tags: Optional[List[str]] = None,
                        max_preco: Optional[float] = None) -> List[Dict]:
        """Filtra produtos por critérios."""
        resultado = self.produtos[:]
        
        # Filtro por categoria
        if categoria:
            resultado = [p for p in resultado if p.get('categoria') == categoria]
        
        # Filtro por tags
        if tags:
            resultado = [p for p in resultado if any(tag in p.get('tags', []) for tag in tags)]
        
        # Filtro por preço máximo
        if max_preco:
            resultado = [p for p in resultado if p.get('preco', 0) <= max_preco]
        
        # Apenas produtos disponíveis
        resultado = [p for p in resultado if p.get('disponivel', True)]
        
        return resultado
    
    def gerar_recomendacao(self, produtos: List[Dict], top: int = 3) -> List[Dict]:
        """Gera top N recomendações baseado em popularidade/disponibilidade."""
        if not produtos:
            return []
        
        # Ordena por número de tags (popular) e preço (barato)
        ordenado = sorted(
            produtos,
            key=lambda p: (len(p.get('tags', [])), -p.get('preco', 0)),
            reverse=True
        )
        
        return ordenado[:top]
    
    def formatar_produto(self, produto: Dict) -> str:
        """Formata um produto para exibição."""
        nome = produto.get('nome', 'Produto')
        preco = produto.get('preco', 0)
        descricao = produto.get('descricao', '')
        tags = ', '.join(produto.get('tags', []))
        
        resultado = f"• **{nome}** - Kz {preco:.2f}\n"
        if descricao:
            resultado += f"  {descricao}\n"
        if tags:
            resultado += f"  Tags: {tags}\n"
        
        return resultado
    
    def processar_mensagem(self, mensagem: str) -> Dict:
        """
        Processa uma mensagem do usuário e retorna resposta estruturada.
        
        Returns:
            Dict com: {
                'success': bool,
                'mensagem': str,  # Resposta legível
                'intencao': str,  # Intenção detectada
                'recomendacoes': List[Dict],  # Produtos recomendados
                'categoria': Optional[str],
            }
        """
        msg = mensagem.strip()
        
        if not msg:
            return {
                'success': False,
                'mensagem': self.saudacoes['nao_entendi'],
                'intencao': 'vazio',
                'recomendacoes': []
            }
        
        # Detecta saudação/help (com fuzzy match simples)
        palavras = msg.lower().split()
        saudacoes_kws = ['oi', 'olá', 'ola', 'ei', 'começar', 'inicio']
        ajuda_kws = ['ajuda', 'help', 'socorro', 'fazer', 'funciona']
        
        tem_saudacao = any(difflib.get_close_matches(p, saudacoes_kws, cutoff=0.85) for p in palavras)
        tem_ajuda = any(difflib.get_close_matches(p, ajuda_kws, cutoff=0.8) for p in palavras)

        if tem_saudacao and len(palavras) <= 2:
            return {
                'success': True,
                'mensagem': self.saudacoes['help'],
                'intencao': 'saudacao',
                'recomendacoes': []
            }
        
        if tem_ajuda:
            return {
                'success': True,
                'mensagem': self.saudacoes['help'],
                'intencao': 'ajuda',
                'recomendacoes': []
            }
        
        # Detecta intenção
        intencao, categorias = self.detectar_intencao(msg)
        
        if intencao == 'generico':
            return {
                'success': True,
                'mensagem': f"Entendi... Podes ser mais específico? Por exemplo:\n• 'Quero algo saudável'\n• 'Recomenda um lanche rápido'\n• 'Bebidas refrescantes'",
                'intencao': 'generico',
                'recomendacoes': []
            }
        
        # Filtra produtos
        categoria_principal = categorias[0] if categorias else None
        produtos_filtrados = self.filtrar_produtos(categoria=categoria_principal)
        
        # Se houver tags específicas, refina ainda mais
        tags_especiais = []
        if 'saude' in categorias or 'saudável' in msg.lower():
            tags_especiais.append('saudável')
        if 'vegetariano' in categorias or msg.lower().count('vegetariano') > 0:
            tags_especiais.append('vegetariano')
        if 'rapido' in categorias:
            tags_especiais.append('rápido')
        
        if tags_especiais:
            produtos_filtrados = self.filtrar_produtos(tags=tags_especiais)
        
        # Gera recomendações
        recomendacoes = self.gerar_recomendacao(produtos_filtrados, top=3)
        
        # Constrói resposta
        if recomendacoes:
            msg_resposta = "Ótimo! Recomendo estes produtos para ti:\n\n"
            for prod in recomendacoes:
                msg_resposta += self.formatar_produto(prod)
            msg_resposta += "\nAlgum destes te interessa?"
        else:
            msg_resposta = "Desculpa, não encontrei produtos que correspondem à tua procura. Podes tentar outra pesquisa?"
        
        return {
            'success': True,
            'mensagem': msg_resposta,
            'intencao': intencao,
            'recomendacoes': recomendacoes,
            'categoria': categoria_principal,
            'timestamp': datetime.now().isoformat()
        }


class YASMINVendor(YASMINAssistant):
    """
    YASMIN Vendor Edition - Entende contexto de vendedor.
    Responde perguntas sobre pedidos, clientes, vendas e estoque.
    """
    
    def __init__(self, produtos_json: Optional[str] = None, vendor_context: Optional[Dict] = None, verbose: bool = False):
        """
        Inicializa YASMIN em modo vendedor.
        
        Args:
            produtos_json: Caminho para arquivo JSON com produtos
            vendor_context: Dict com dados do BD (pedidos, clientes, estoque, etc.)
            verbose: Mostra logs detalhados
        """
        super().__init__(produtos_json, verbose)
        self.vendor_context = vendor_context or {}
        self.nome = "YASMIN Vendedor"
        
        # Adiciona keywords para mode vendor
        self.keywords.update({
            'pedidos': ['pedido', 'pedidos', 'pendente', 'atendido', 'entregue', 'encomenda', 'ordem', 'solicitação'],
            'clientes': ['cliente', 'clientes', 'quantos clientes', 'total de clientes', 'comprador', 'usuário'],
            'estoque': ['estoque', 'quantidade', 'alerta', 'baixo', 'falta', 'armazém', 'stock', 'sobra'],
            'vendas': ['vendas', 'mais vendido', 'receita', 'lucro', 'faturamento', 'venda', 'dinheiro', 'ganho', 'faturado'],
            'relatorio': ['relatorio', 'resumo', 'estatisticas', 'dados', 'analise', 'report'],
        })
        
        self.saudacoes['inicial'] = f"Olá! Sou a {self.nome}.\nEstou aqui para ajudarte com pedidos, clientes, vendas e estoque!"
        self.saudacoes['help'] = "Posso ajudarte a:\n• Ver status de pedidos\n• Consultar informações de clientes\n• Verificar estoque e alertas\n• Analisar as melhores vendas\n• Gerar relatórios rápidos\n\nO que precisa?"
    
    def processar_mensagem_vendor(self, mensagem: str) -> Dict:
        """
        Processa mensagem em contexto de vendedor.
        Consulta dados do BD passados no vendor_context.
        """
        msg = mensagem.strip().lower()
        palavras = msg.split()
        
        # Detecta intenção com fuzzy
        intencao, categorias = self.detectar_intencao(msg)

        # 1. Queries de Pedidos
        if 'pedidos' in categorias:
            ctx = self.vendor_context
            total = ctx.get('totalPedidos', 0)
            pendentes = ctx.get('pedidosPendentes', 0)
            resposta = f"📊 **Estatísticas de Pedidos:**\n• Total de pedidos: {total}\n• Pendentes: {pendentes}\n• Atendidos: {total - pendentes}"
            if pendentes > 0:
                resposta += "\n\n🔔 Existem pedidos pendentes que requerem atenção!"
            return {
                'success': True,
                'mensagem': resposta,
                'intencao': 'pedidos',
                'recomendacoes': [],
                'timestamp': datetime.now().isoformat()
            }
        
        # 2. Query de Clientes
        if 'clientes' in categorias:
            total_clientes = self.vendor_context.get('totalClientes', 0)
            resposta = f"👥 **Clientes:**\nTotal de clientes cadastrados: {total_clientes}"
            return {
                'success': True,
                'mensagem': resposta,
                'intencao': 'clientes',
                'recomendacoes': [],
                'timestamp': datetime.now().isoformat()
            }
        
        # 3. Query de Estoque
        if 'estoque' in categorias:
            ctx = self.vendor_context
            total_est = ctx.get('quantidadeEstoque', 0)
            alertas = ctx.get('produtosAlerta', [])
            menor_produto = ctx.get('produtoMenorEstoque')
            
            # Sub-intent: Perguntando especificamente sobre o mais baixo/crítico
            palavras_chave_baixo = ['baixo', 'menor', 'pouco', 'acabar', 'crítico', 'critico', 'falta']
            quer_saber_menor = any(p in msg for p in palavras_chave_baixo)
            
            if quer_saber_menor and menor_produto:
                nome = menor_produto.get('nome', 'Produto')
                qtd = menor_produto.get('quantidade', 0)
                resposta = f"⚠️ **Estoque Crítico:**\nO produto com menor estoque é **{nome}** com apenas **{qtd}** unidades."
                if alertas:
                    outros = [p['nome'] for p in alertas if p['nome'] != nome]
                    if outros:
                        resposta += f"\n\nOutros produtos baixos: {', '.join(outros[:3])}."
                return {
                    'success': True,
                    'mensagem': resposta,
                    'intencao': 'estoque_critico',
                    'recomendacoes': [],
                    'timestamp': datetime.now().isoformat()
                }

            resposta = f"📦 **Estoque:**\nTotal em estoque: {total_est} unidades\n"
            if alertas:
                resposta += f"\n⚠️  **Produtos com alerta (≤5 unidades):**\n"
                for prod in alertas[:5]:
                    resposta += f"• {prod.get('nome', 'Produto')}: {prod.get('quantidade', 0)} un\n"
            else:
                resposta += "\n✅ Nenhum produto com alerta de estoque baixo."
            
            return {
                'success': True,
                'mensagem': resposta,
                'intencao': 'estoque',
                'recomendacoes': [],
                'timestamp': datetime.now().isoformat()
            }
        
        # 4. Query de Vendas
        if 'vendas' in categorias:
            mais_vendidos = self.vendor_context.get('produtosMaisVendidos', [])
            
            resposta = "🏆 **Produtos Mais Vendidos:**\n"
            if mais_vendidos:
                for i, prod in enumerate(mais_vendidos[:5], 1):
                    nome = prod.get('nome', 'Produto')
                    vendido = prod.get('total_vendido', 0)
                    receita = prod.get('receita', 0)
                    resposta += f"{i}. {nome}: {vendido} un (Kz {receita:.2f})\n"
            else:
                resposta += "Sem vendas registradas ainda."
            
            return {
                'success': True,
                'mensagem': resposta,
                'intencao': 'vendas',
                'recomendacoes': [],
                'timestamp': datetime.now().isoformat()
            }
        
        # Fallback para modo genérico se não detectar query específica
        if intencao == 'generico':
            return {
                'success': True,
                'mensagem': "Desculpa, não compreendi bem. Podes tentar perguntar sobre 'pedidos', 'clientes', 'estoque' ou 'vendas'?",
                'intencao': 'generico',
                'recomendacoes': []
            }
        
        # Se não for vendor-specific, usa processor normal (recomendações de produtos)
        return super().processar_mensagem(mensagem)


def main():
    """Função principal para CLI."""
    parser = argparse.ArgumentParser(
        description='YASMIN Assistant - Assistente Virtual da Cantina IPM'
    )
    parser.add_argument('mensagem', nargs='?', help='Mensagem do usuário')
    parser.add_argument('--produtos', help='Caminho para JSON de produtos')
    parser.add_argument('--audio', action='store_true', help='Gerar áudio (TTS) e retornar base64')
    parser.add_argument('--verbose', action='store_true', help='Modo verbose')
    parser.add_argument('--demo', action='store_true', help='Modo demo com produtos padrão')
    parser.add_argument('--vendor', action='store_true', help='Modo vendedor (entende contexto de vendedor)')
    parser.add_argument('--vendor-context', help='JSON com contexto de vendedor (pedidos, clientes, estoque)')
    
    args = parser.parse_args()
    
    # Parse vendor context se fornecido
    vendor_context = None
    if args.vendor_context:
        try:
            vendor_context = json.loads(args.vendor_context)
        except json.JSONDecodeError as e:
            if args.verbose:
                sys.stderr.write(f'[YASMIN] Erro ao parsear vendor_context: {e}\n')
            vendor_context = {}
    
    # Inicializa YASMIN (cliente ou vendor)
    if args.vendor:
        yasmin = YASMINVendor(
            produtos_json=args.produtos,
            vendor_context=vendor_context,
            verbose=args.verbose
        )
        if args.verbose:
            sys.stderr.write('[YASMIN] Modo Vendedor ativado.\n')
    else:
        yasmin = YASMINAssistant(
            produtos_json=args.produtos,
            verbose=args.verbose
        )
    
    # Se for demo, mostra lista de produtos
    if args.demo:
        print("=== YASMIN Demo - Produtos Disponíveis ===\n")
        for prod in yasmin.produtos:
            print(f"[{prod['id']}] {prod['nome']} - Kz {prod['preco']:.2f}")
        print()
    
    # Processa mensagem
    if args.mensagem:
        mensagem = args.mensagem

        # Inicializa variáveis para o output
        openai_key = None 

        # Se for modo vendor, processa como vendor
        if args.vendor and isinstance(yasmin, YASMINVendor):
            resultado = yasmin.processar_mensagem_vendor(mensagem)
            resposta_text = resultado.get('mensagem')
            recomendacoes = resultado.get('recomendacoes', [])
        else:
            # Se houver OPENAI_API_KEY no ambiente e requests disponível, usa LLM para resposta mais natural
            openai_key = os.getenv('OPENAI_API_KEY') or os.getenv('OPENAI_KEY')
            resposta_text = None
            recomendacoes = []

            if openai_key and requests is not None:
                try:
                    if args.verbose:
                        sys.stderr.write('[YASMIN] Usando OpenAI via API para gerar resposta.\n')
                    payload = {
                        'model': 'gpt-3.5-turbo',
                        'messages': [
                            {'role': 'system', 'content': 'És a YASMIN, assistente de uma cantina universitária. Responde em Português de Angola, educado e prático.'},
                            {'role': 'user', 'content': mensagem}
                        ],
                        'temperature': 0.6,
                        'max_tokens': 500
                    }
                    headers = {'Authorization': f'Bearer {openai_key}', 'Content-Type': 'application/json'}
                    r = requests.post('https://api.openai.com/v1/chat/completions', json=payload, headers=headers, timeout=15)
                    if r.status_code == 200:
                        jr = r.json()
                        resposta_text = jr.get('choices', [])[0].get('message', {}).get('content') if jr.get('choices') else None
                    else:
                        if args.verbose:
                            sys.stderr.write(f"[YASMIN] OpenAI returned {r.status_code}: {r.text}\n")
                except Exception as e:
                    if args.verbose:
                        sys.stderr.write(f"[YASMIN] Erro ao chamar OpenAI: {e}\n")

            # fallback local processing
            if not resposta_text:
                resultado = yasmin.processar_mensagem(mensagem)
                resposta_text = resultado.get('mensagem')
                recomendacoes = resultado.get('recomendacoes', [])

        # Se requisitado, gera áudio (base64 MP3) — tenta pyttsx3 primeiro (offline, rápido, natural), depois gTTS
        audio_b64 = None
        audio_mime = None
        if args.audio:
            # Tenta pyttsx3 primeiro (offline, melhor velocidade e qualidade natural)
            if pyttsx3 is not None:
                try:
                    engine = pyttsx3.init()
                    # Tenta voz portuguesa
                    try:
                        voices = engine.getProperty('voices')
                        for voice in voices:
                            if 'pt' in voice.languages or 'portuguese' in voice.name.lower() or 'português' in voice.name.lower():
                                engine.setProperty('voice', voice.id)
                                break
                    except:
                        pass
                    
                    # Velocidade mais natural (mais lenta para clareza)
                    engine.setProperty('rate', 135)
                    
                    tf = tempfile.NamedTemporaryFile(delete=False, suffix='.wav')
                    tmpname = tf.name
                    tf.close()
                    
                    # Limpar texto para TTS (remover markdown, melhorar pausas)
                    # 1. Substitui bullets e quebras de linha por pontuação para forçar pausa
                    tts_text = re.sub(r'[\•\-\*]\s+', ', ', resposta_text) # Bullets viram vírgulas
                    tts_text = re.sub(r'\n+', '. ', tts_text) # Novas linhas viram pontos
                    
                    # 2. Remove markdown restante (* para negrito)
                    tts_text = re.sub(r'[\*]', '', tts_text)
                    
                    # 3. Remove Kz e decimais .00 para fala mais limpa
                    tts_text = re.sub(r'Kz', '', tts_text, flags=re.IGNORECASE)
                    tts_text = re.sub(r'\.00', '', tts_text)
                    
                    # 4. Normaliza espaços
                    tts_text = re.sub(r'\s+', ' ', tts_text).strip()
                    
                    engine.save_to_file(tts_text, tmpname)
                    engine.runAndWait()
                    
                    # Converte WAV para base64
                    with open(tmpname, 'rb') as f:
                        audio_bytes = f.read()
                        audio_b64 = base64.b64encode(audio_bytes).decode('ascii')
                        audio_mime = 'audio/wav'
                    
                    try:
                        os.unlink(tmpname)
                    except Exception:
                        pass
                    
                    if args.verbose:
                        sys.stderr.write('[YASMIN] Áudio gerado com pyttsx3 (offline).\n')
                except Exception as e:
                    if args.verbose:
                        sys.stderr.write(f'[YASMIN] Erro ao gerar TTS com pyttsx3: {e}\n')
                    audio_b64 = None  # fallback para gTTS
            
            # Fallback para gTTS
            if audio_b64 is None and gTTS is not None:
                try:
                    # Limpar texto para TTS
                    tts_text = re.sub(r'[\•\-\*]\s+', ', ', resposta_text)
                    tts_text = re.sub(r'\n+', '. ', tts_text)
                    tts_text = re.sub(r'[\*]', '', tts_text)
                    
                    tts_text = re.sub(r'Kz', '', tts_text, flags=re.IGNORECASE)
                    tts_text = re.sub(r'\.00', '', tts_text)
                    
                    tts_text = re.sub(r'\s+', ' ', tts_text).strip()

                    tts = gTTS(text=tts_text, lang='pt', slow=False)
                    tf = tempfile.NamedTemporaryFile(delete=False, suffix='.mp3')
                    tmpname = tf.name
                    tf.close()
                    tts.save(tmpname)
                    with open(tmpname, 'rb') as f:
                        audio_bytes = f.read()
                        audio_b64 = base64.b64encode(audio_bytes).decode('ascii')
                        audio_mime = 'audio/mpeg'
                    try:
                        os.unlink(tmpname)
                    except Exception:
                        pass
                    if args.verbose:
                        sys.stderr.write('[YASMIN] Áudio gerado com gTTS (online).\n')
                except Exception as e:
                    if args.verbose:
                        sys.stderr.write(f'[YASMIN] Erro ao gerar TTS com gTTS: {e}\n')

        output = {
            'success': True,
            'mensagem': resposta_text,
            'recomendacoes': recomendacoes,
            'intencao': 'llm' if resposta_text and openai_key else ('local_fuzzy' if 'difflib' in sys.modules else 'local'),
            'timestamp': datetime.now().isoformat()
        }
        if audio_b64:
            output['audio_base64'] = audio_b64
            output['audio_mime'] = audio_mime

        print(json.dumps(output, ensure_ascii=False))
    else:
        # Modo interativo (CLI)
        print(f"{yasmin.saudacoes['inicial']}\n")
        while True:
            try:
                entrada = input("\nVocê: ").strip()
                if entrada.lower() in ['sair', 'exit', 'quit']:
                    print("Obrigada! Até breve! 👋")
                    break
                resultado = yasmin.processar_mensagem(entrada)
                print(f"\nYASMIN: {resultado['mensagem']}")
            except KeyboardInterrupt:
                print("\n\nObrigada! Até breve! 👋")
                break


if __name__ == '__main__':
    main()
