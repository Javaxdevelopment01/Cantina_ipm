{% raw %}# Guia de Responsividade e Menu Hamburger

## 📱 Menu Hamburger Responsivo

Um novo header responsivo foi criado e está disponível em `includes/header_responsive.php`.

### Como usar em suas views:

1. **No topo do seu arquivo PHP:**

```php
<?php include(__DIR__ . '/../includes/header_responsive.php'); ?>
```

2. **O header automaticamente:**
   - Mostra o menu normal em desktop (telas ≥ 769px)
   - Mostra um botão hamburger em mobile (telas ≤ 768px)
   - O menu dropdown funciona automaticamente

### Estrutura de Imports

O arquivo detecta automaticamente se está em `app/views/` ou raiz e ajusta os caminhos:

- Se está em `app/views/cliente/`, `app/views/Vendedor/`, etc. → adiciona `../../`
- Se está na raiz → usa caminho direto

## 🎨 Responsividade CSS

### Breakpoints utilizados:

- **Desktop:** > 768px
- **Tablet/Mobile:** ≤ 768px

### Exemplo de CSS responsivo para seu sistema:

```css
/* Desktop first - define o padrão */
.elemento {
  display: flex;
  gap: 32px;
  font-size: 16px;
}

/* Mobile - sobrescreve para telas pequenas */
@media (max-width: 768px) {
  .elemento {
    flex-direction: column;
    gap: 16px;
    font-size: 14px;
  }
}
```

## 📋 Implementação em Toda a Sistema

### 1. Views de Cliente (`app/views/cliente/`)

- [x] Adicionar header_responsive.php
- [ ] Revisão de cards/grids para mobile
- [ ] Testar navegação no celular

### 2. Views de Vendedor (`app/views/Vendedor/`)

- [ ] Adicionar header_responsive.php em todas as views
- [ ] Adaptar tabelas para mobile (pode virar cards)
- [ ] Verificar modais em tela pequena

### 3. Views de Admin (`app/views/adm/`)

- [ ] Adicionar header_responsive.php
- [ ] Adaptar dashboards para mobile
- [ ] Testar relatórios em celular

## 🔧 JavaScript para Menu

O script incluso automaticamente:

1. Abre/fecha menu ao clicar no hamburger
2. Fecha menu ao clicar em um link
3. Fecha menu ao clicar fora

Nenhuma configuração adicional necessária!

## 📱 Testar Responsividade

### No navegador:

1. Pressione `F12` (DevTools)
2. Clique em "Toggle device toolbar" (Ctrl+Shift+M)
3. Selecione diferentes dispositivos

### Checklist:

- [ ] Menu abre/fecha corretamente
- [ ] Logo visível em mobile
- [ ] Texto legível (mín. 14px)
- [ ] Botões clicáveis (mín. 44x44px)
- [ ] Sem scroll horizontal

## 🎯 Próximos Passos

1. Integrar `header_responsive.php` em todas as views principais
2. Adicionar media queries aos estilos CSS existentes
3. Testar em dispositivos reais (iPhone, Android)
4. Ajustar imagens e ícones para mobile

## ⚠️ Notas Importantes

- O header fica **fixo** no topo (position: fixed)
- Sempre adicione uma **margem superior** ao conteúdo para evitar sobreposição
- Use `@media (max-width: 768px)` para estilos mobile
- Prefira **flex** e **grid** em vez de floats para layouts responsivos

---

**Responsividade = Melhor experiência para todos os usuários! 📲**
{% endraw %}
