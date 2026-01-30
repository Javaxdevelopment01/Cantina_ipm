# 📱 Sistema Completo de Responsividade - Cantina IPM

## ✅ O Que Foi Implementado

### 1. **CSS Responsivo Global** (`assets/css/responsive.css`)

- Breakpoints: 576px, 768px, 992px, 1200px
- Classes utilitárias reutilizáveis
- Grids responsivas
- Tabelas adaptadas para mobile
- Formulários responsivos
- Sidebars colapsáveis
- Componentes flex-wrap

### 2. **JavaScript Responsivo** (`assets/js/responsive.js`)

- Menu Hamburger automático
- Sidebar móvel toggle
- Detecção de viewport changes
- Tabelas dinâmicas (thead → data-label em mobile)
- Otimização para touch devices
- Funções auxiliares

### 3. **Header Responsivo** (`includes/header_responsive.php`)

- Logo com detecção automática de caminho
- Menu dropdown em mobile
- Hamburger icon (Font Awesome)
- Navegação adaptável
- Fecha menu ao clicar fora

### 4. **Dashboards Atualizados**

✅ `app/views/cliente/dashboard_cliente.php`
✅ `app/views/Vendedor/dashboard_vendedor.php`
✅ `app/views/adm/dashboard_adm.php`

---

## 🚀 Como Usar

### **Opção 1: Integração Automática** (Recomendado)

Execute o script de integração para adicionar CSS e JS em TODAS as views automaticamente:

```bash
cd C:\wamp64\www\cantina_ipm
php integrate_responsive.php
```

**O que faz:**

- Percorre todos os arquivos PHP em `app/views/`
- Adiciona `<link>` para `responsive.css` após `<meta name="viewport">`
- Adiciona `<script>` para `responsive.js` antes de `</body>`
- Reporta sucesso/erros

---

### **Opção 2: Integração Manual**

Em cada arquivo PHP, adicione:

**No `<head>`:**

```html
<link rel="stylesheet" href="../../assets/css/responsive.css" />
```

**Antes de `</body>`:**

```html
<script src="../../assets/js/responsive.js"></script>
```

---

## 📐 Breakpoints Utilizados

| Dispositivo   | Largura       | Breakpoint                  |
| ------------- | ------------- | --------------------------- |
| Mobile        | ≤ 576px       | `@media (max-width: 576px)` |
| Mobile grande | 576px - 768px | `@media (max-width: 768px)` |
| Tablet        | 768px - 992px | `@media (max-width: 992px)` |
| Desktop       | ≥ 992px       | Estilos padrão              |

---

## 🎨 Classes CSS Disponíveis

### **Grids Responsivos**

```html
<!-- Auto-adapta: 1 col mobile → 2 cols tablet → 4 cols desktop -->
<div class="grid-responsive">
  <div>Item 1</div>
  <div>Item 2</div>
  <div>Item 3</div>
</div>

<!-- 2 colunas em desktop, 1 em mobile -->
<div class="grid-responsive cols-2">
  <div>Coluna 1</div>
  <div>Coluna 2</div>
</div>
```

### **Containers Responsivos**

```html
<!-- Padding e max-width adaptativos -->
<div class="container-responsive">Conteúdo aqui</div>
```

### **Flexbox Responsivo**

```html
<!-- Em mobile vira flex-direction: column -->
<div class="flex-responsive">
  <button>Botão 1</button>
  <button>Botão 2</button>
</div>
```

### **Tabelas Responsivas**

```html
<div class="table-responsive-wrapper">
  <table>
    <thead>
      <tr>
        <th>Nome</th>
        <th>Email</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td data-label="Nome:">João</td>
        <td data-label="Email:">joao@email.com</td>
      </tr>
    </tbody>
  </table>
</div>
```

### **Visibilidade Condicional**

```html
<!-- Mostra apenas em mobile -->
<div class="show-mobile">Menu Mobile</div>

<!-- Esconde em mobile -->
<div class="hide-mobile">Menu Desktop</div>
```

### **Textos Adaptativos**

```html
<h1 class="text-responsive-lg">Título Grande</h1>
<p class="text-responsive">Parágrafo normal</p>
```

### **Botões Responsivos**

```html
<button class="btn-responsive">
  <i class="fas fa-download"></i>
  Descarregar
</button>
```

---

## 🔧 Funções JavaScript Disponíveis

### **Detectar Mobile**

```javascript
if (isMobileViewport()) {
  console.log("Está em mobile");
}
```

### **Toggle Sidebar**

```javascript
toggleSidebar();
```

### **Scroll Suave**

```javascript
smoothScroll("elementId");
```

### **Ajustar Altura ao Viewport**

```javascript
adjustHeightToViewport(".meu-elemento", 64); // 64px offset
```

---

## 📋 Menu Hamburger

Adicione este HTML no seu header:

```html
<header>
  <div class="navbar">
    <div class="logo-brand">
      <img src="assets/images/ipm_logo.png" alt="Logo" />
      <span>Meu App</span>
    </div>

    <button class="menu-toggle" id="menuToggle">
      <i class="fas fa-bars"></i>
    </button>

    <nav class="nav-links" id="navMenu">
      <a href="#home">Home</a>
      <a href="#about">Sobre</a>
      <a href="#contact">Contacto</a>
    </nav>
  </div>
</header>
```

O JavaScript faz o resto automaticamente!

---

## 🧪 Como Testar Responsividade

### **No Navegador (DevTools)**

1. Pressione `F12` para abrir DevTools
2. Clique no ícone "Toggle device toolbar" (ou `Ctrl+Shift+M`)
3. Selecione diferentes dispositivos
4. Teste interações

### **Dispositivos Reais**

1. Copie a URL da aplicação
2. Aceda via QR code ou IP:porta do seu PC
3. Teste em iPhone, Android, tablets

### **Checklist de Testes**

- [ ] Menu abre/fecha em mobile
- [ ] Texto legível (mín. 14px)
- [ ] Botões clicáveis (mín. 44x44px)
- [ ] Sem scroll horizontal
- [ ] Imagens carregam bem
- [ ] Tabelas são legíveis em mobile
- [ ] Modais funcionam em celular
- [ ] Touch events funcionam

---

## 🎯 Próximos Passos

### 1. **Executar Integração Automática**

```bash
php integrate_responsive.php
```

### 2. **Testar Todas as Views**

- [ ] Cliente dashboard
- [ ] Cliente perfil
- [ ] Vendedor dashboard
- [ ] Vendedor pedidos
- [ ] Admin dashboard
- [ ] Admin gestão de produtos
- [ ] Admin gestão de vendedores
- [ ] Admin gestão de clientes

### 3. **Ajustes Customizados**

Se alguma view precisar de estilos específicos, adicione `@media` queries no `<style>` da view

### 4. **Deploy em Produção**

Certifique-se que:

- Todos os `href` e `src` estão corretos
- Logo está em `assets/images/ipm_logo.png`
- CSS e JS carregam sem erros (console)

---

## 📞 Suporte

### **Problemas Comuns**

**P: Menu não abre em mobile?**
R: Verifique se `responsive.js` está sendo carregado (console do navegador)

**P: Layout quebrado em tablet?**
R: Use DevTools para identificar qual breakpoint está falhando

**P: Imagens saem do container?**
R: Use `class="img-responsive"` ou `max-width: 100%; height: auto;`

**P: Menu não fecha ao clicar fora?**
R: Certifique-se que o JavaScript está ativo

---

## 📊 Estrutura de Arquivos

```
cantina_ipm/
├── assets/
│   ├── css/
│   │   └── responsive.css       ← CSS Global
│   └── js/
│       └── responsive.js        ← JavaScript Global
├── app/
│   └── views/
│       ├── cliente/
│       │   └── dashboard_cliente.php    ✅ Atualizado
│       ├── Vendedor/
│       │   └── dashboard_vendedor.php   ✅ Atualizado
│       └── adm/
│           └── dashboard_adm.php        ✅ Atualizado
├── includes/
│   └── header_responsive.php   ← Header reutilizável
├── integrate_responsive.php    ← Script de integração
└── RESPONSIVE_GUIDE.md
```

---

## ✨ Boas Práticas

1. **Mobile First**: Defina estilos para mobile primeiro, depois use `@media` para desktop
2. **Flexibilidade**: Use `flex` e `grid` em vez de floats
3. **Viewport**: Sempre inclua `<meta name="viewport">`
4. **Touch**: Use `min-height: 44px` para botões (Apple recomenda)
5. **Testes**: Teste em devices reais, não apenas no navegador

---

**🎉 Sistema responsivo implementado com sucesso!**

Para dúvidas ou ajustes, edite `responsive.css` ou `responsive.js` conforme necessário.
