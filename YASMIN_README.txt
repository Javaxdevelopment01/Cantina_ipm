# 🎉 Sistema Cantina IPM - YASMIN Assistente Virtual Implementada!

## 📱 Como Aceder a YASMIN

### Para Clientes

1. **Abrir Dashboard do Cliente**
   - URL: `http://cantina-ipm/app/views/cliente/dashboard_cliente.php`

2. **Clicar no Botão YASMIN**
   - Localização: Canto inferior direito da página
   - Ícone: ✨ Varinha mágica
   - Cor: Azul petroleo com gradiente

3. **Escrever uma Pergunta**
   - Exemplos:
     - "Quero algo saudável"
     - "Um lanche rápido"
     - "Bebidas"
     - "Vegetariano"
     - "Recomenda um doce"

4. **Ver Recomendações**
   - YASMIN mostrará até 3 produtos recomendados
   - Clique em "Adicionar" para colocar no carrinho

5. **Finalizar Compra**
   - Continue normalmente com o carrinho

---

## 🔧 Configuração do Sistema

### Pré-requisitos
- Python 3.6+ instalado
- PHP 7.4+
- Servidor Apache/IIS

### Verificar Instalação

```bash
# Verificar Python
python --version

# Testar YASMIN direto
python ia/yasmin_assistant.py "Quero algo saudável"

# Executar testes de integração
python ia/test_yasmin_integration.py
```

### Estrutura de Diretórios

```
/cantina_ipm/
├── ia/
│   ├── yasmin_assistant.py          ← Assistente YASMIN (Python)
│   ├── README_YASMIN.md              ← Documentação técnica
│   └── test_yasmin_integration.py    ← Suite de testes
├── app/
│   ├── api/
│   │   └── yasmin_api.php            ← API Wrapper (PHP)
│   └── views/
│       └── cliente/
│           └── dashboard_cliente.php ← Dashboard com YASMIN
└── YASMIN_IMPLEMENTATION.md          ← Resumo de implementação
```

---

## 📚 Documentação

### Para Clientes
- Nada a instalar! YASMIN funciona automaticamente no dashboard.

### Para Administradores
- **Documentação Técnica**: `ia/README_YASMIN.md`
- **Relatório de Implementação**: `YASMIN_IMPLEMENTATION.md`
- **Testes**: `ia/test_yasmin_integration.py`

### Para Desenvolvedores
- **Script Python**: `ia/yasmin_assistant.py` (350+ linhas comentadas)
- **API PHP**: `app/api/yasmin_api.php`
- **Integração JS**: `app/views/cliente/dashboard_cliente.php`

---

## 🚀 Features Principais

✨ **Detecção Inteligente de Intenção**
- Compreende várias formas de pergunta
- Categoriza automaticamente

🎯 **Recomendações Automáticas**
- Até 3 produtos recomendados por pergunta
- Baseado em preferências (saudável, vegetariano, rápido)

💬 **Chat Profissional**
- Widget flutuante com animações
- Responsivo para mobile/tablet
- Integrado com carrinho

🔐 **Sem APIs Externas**
- Python puro (sem dependências)
- Sem custos adicionais
- Privacidade garantida

---

## 🎯 Exemplos de Uso

### Pergunta: "Quero algo saudável"
```
YASMIN responde:
"Ótimo! Recomendo estes produtos para ti:

• Suco Natural de Laranja - Kz 150.00
  Suco fresco e natural
  [Adicionar]

• Salada Verde - Kz 250.00
  Alface, tomate, cebola e azeite
  [Adicionar]

Algum destes te interessa?"
```

### Pergunta: "Um lanche rápido"
```
YASMIN responde:
"Ótimo! Recomendo estes produtos para ti:

• Sanduíche de Queijo - Kz 200.00
  Pão integral com queijo fresco
  [Adicionar]

Algum destes te interessa?"
```

---

## 🛠️ Troubleshooting

### Problema: YASMIN não aparece
**Solução:**
- Atualizar página (Ctrl+F5)
- Verificar console do navegador (F12)
- Testar Python: `python ia/yasmin_assistant.py "teste"`

### Problema: Mensagem lenta
**Solução:**
- Primeira requisição é mais lenta (Python iniciando)
- Respostas posteriores são mais rápidas
- Normal no servidor

### Problema: Acentos incorretos
**Solução:**
- Limpar cache: Ctrl+Shift+Del
- Já está configurado para UTF-8

---

## 📞 Contactos

Para suporte técnico:

1. **Verificar logs**: `/app/logs/`
2. **Testar integração**: `python ia/test_yasmin_integration.py`
3. **Testes manuais**: `python ia/yasmin_assistant.py "test"`

---

## ✅ Status do Sistema

| Componente | Status | Data |
|-----------|--------|------|
| YASMIN Python | ✅ Pronto | 30/11/2025 |
| API PHP | ✅ Pronto | 30/11/2025 |
| Dashboard | ✅ Pronto | 30/11/2025 |
| Testes | ✅ Passando | 30/11/2025 |
| Documentação | ✅ Completa | 30/11/2025 |

---

## 🎓 Próximos Passos

### Curto Prazo
- [ ] Testar com clientes reais
- [ ] Coletar feedback
- [ ] Monitorar performance

### Médio Prazo
- [ ] Integrar com dados reais do BD
- [ ] Adicionar análise de tendências
- [ ] Personalização por cliente

### Longo Prazo
- [ ] Reconhecimento de voz
- [ ] Multi-idioma
- [ ] Integração com IA mais avançada

---

## 🎉 Conclusão

**YASMIN está pronto para transformar a experiência do cliente da Cantina IPM!**

A assistente virtual oferece:
- ✅ Experiência mais interativa
- ✅ Recomendações personalizadas
- ✅ Facilidade de escolha
- ✅ Sistema 100% informatizado
- ✅ Sem custos adicionais

**Bem-vindo ao futuro! 🚀**

---

*Última atualização: 30 Novembro 2025*
*Versão: 1.0*
*Status: ✅ Produção*
