/**
 * SISTEMA DE GESTÃO SOCIAL E ASSISTENCIAL
 * Script Vanilla JS para interatividades da interface
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Controle das Abas (Tabs)
  const tabBtns = document.querySelectorAll('.tab-btn');
  tabBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const targetId = this.getAttribute('data-tab');
      const container = this.closest('.tabs-container') || document;

      container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      container.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

      this.classList.add('active');
      const targetContent = container.querySelector('#' + targetId);
      if (targetContent) {
        targetContent.classList.add('active');
      }

      // Atualiza o hash da URL para permitir links diretos
      if (history.replaceState) {
        history.replaceState(null, null, '#' + targetId);
      }
    });
  });

  // Abre aba com base no hash da URL
  if (window.location.hash) {
    const hash = window.location.hash.substring(1);
    const activeBtn = document.querySelector(`.tab-btn[data-tab="${hash}"]`);
    if (activeBtn) {
      activeBtn.click();
    }
  }

  // 2. Máscaras Simples de Campos de Formulário
  const applyMask = (selector, maskFn) => {
    document.querySelectorAll(selector).forEach(input => {
      input.addEventListener('input', function (e) {
        this.value = maskFn(this.value);
      });
    });
  };

  applyMask('.mask-cpf', val => {
    return val
      .replace(/\D/g, '')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d{1,2})$/, '$1-$2')
      .slice(0, 14);
  });

  applyMask('.mask-cep', val => {
    return val
      .replace(/\D/g, '')
      .replace(/(\d{5})(\d{1,3})$/, '$1-$2')
      .slice(0, 9);
  });

  applyMask('.mask-phone', val => {
    const clean = val.replace(/\D/g, '');
    if (clean.length > 10) {
      return clean.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
    }
    return clean.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
  });

  // 3. Adicionar / Remover Linha Dinâmica de Itens (Entrega de Benefícios)
  const btnAddItem = document.getElementById('btn-add-item');
  const itemsContainer = document.getElementById('itens-entrega-container');
  if (btnAddItem && itemsContainer) {
    btnAddItem.addEventListener('click', function () {
      const template = document.getElementById('item-row-template');
      if (template) {
        const clone = template.content.cloneNode(true);
        itemsContainer.appendChild(clone);
      }
    });

    itemsContainer.addEventListener('click', function (e) {
      if (e.target.closest('.btn-remove-item')) {
        const row = e.target.closest('.item-row');
        if (row) {
          row.remove();
        }
      }
    });
  }

  // 4. Adicionar / Remover Linha Dinâmica de Itens (Doações)
  const btnAddDoacaoItem = document.getElementById('btn-add-doacao-item');
  const doacaoContainer = document.getElementById('itens-doacao-container');
  if (btnAddDoacaoItem && doacaoContainer) {
    btnAddDoacaoItem.addEventListener('click', function () {
      const template = document.getElementById('item-doacao-row-template');
      if (template) {
        const clone = template.content.cloneNode(true);
        doacaoContainer.appendChild(clone);
      }
    });

    doacaoContainer.addEventListener('click', function (e) {
      if (e.target.closest('.btn-remove-item')) {
        const row = e.target.closest('.item-row');
        if (row) {
          row.remove();
        }
      }
    });
  }

  // 5. Confirmação de Ações Críticas
  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', function (e) {
      const msg = this.getAttribute('data-confirm') || 'Deseja realmente confirmar esta operação?';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  // 6. Menu Mobile Toggle
  const toggleBtn = document.getElementById('sidebar-toggle');
  const sidebar = document.querySelector('.app-sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }
});
