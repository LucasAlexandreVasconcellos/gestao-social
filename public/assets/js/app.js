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

  applyMask('.mask-phone', val => {
    const clean = val.replace(/\D/g, '');
    if (clean.length > 10) {
      return clean.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
    }
    return clean.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
  });

  // 2.1 Consulta e Autopreenchimento Inteligente de Endereço via CEP (ViaCEP API com Fallback)
  const formatCep = val => {
    return val
      .replace(/\D/g, '')
      .replace(/(\d{5})(\d{1,3})$/, '$1-$2')
      .slice(0, 9);
  };

  const consultarCep = async (input) => {
    const clean = input.value.replace(/\D/g, '');
    if (clean.length !== 8) return;
    if (input.dataset.lastCep === clean) return;

    input.dataset.lastCep = clean;
    const form = input.closest('form') || document;
    const feedback = form.querySelector('#cep-feedback') || form.querySelector('.cep-feedback');

    const logradouro = form.querySelector('#logradouro') || form.querySelector('[name="logradouro"]');
    const bairro = form.querySelector('#bairro') || form.querySelector('[name="bairro"]');
    const municipio = form.querySelector('#municipio') || form.querySelector('[name="municipio"]');
    const uf = form.querySelector('#uf') || form.querySelector('[name="uf"]');
    const numero = form.querySelector('#numero') || form.querySelector('[name="numero"]');

    if (feedback) {
      feedback.style.display = 'block';
      feedback.innerHTML = '<span style="color: var(--primary);">⏳ Consultando CEP...</span>';
    }

    try {
      let res = await fetch(`https://viacep.com.br/ws/${clean}/json/`);
      let data = await res.json();

      // Fallback para AwesomeAPI caso ViaCEP retorne erro
      if (data.erro) {
        try {
          const resFallback = await fetch(`https://cep.awesomeapi.com.br/json/${clean}`);
          if (resFallback.ok) {
            const dataFallback = await resFallback.json();
            data = {
              logradouro: dataFallback.address,
              bairro: dataFallback.district,
              localidade: dataFallback.city,
              uf: dataFallback.state
            };
          }
        } catch (_) {}
      }

      if (!data.erro) {
        if (logradouro && (data.logradouro || !logradouro.value)) {
          logradouro.value = data.logradouro || logradouro.value;
        }
        if (bairro && (data.bairro || !bairro.value)) {
          bairro.value = data.bairro || bairro.value;
        }
        if (municipio && (data.localidade || !municipio.value)) {
          municipio.value = data.localidade || municipio.value;
        }
        if (uf && (data.uf || !uf.value)) {
          uf.value = (data.uf || uf.value).toUpperCase();
        }

        if (feedback) {
          const local = [data.localidade, data.uf].filter(Boolean).join(' - ');
          feedback.innerHTML = `<span style="color: #059669; font-weight: 600;">✓ Endereço localizado${local ? ': ' + local : ''}</span>`;
        }

        // Coloca o cursor automaticamente no campo número para agilizar a operação
        if (numero) {
          numero.focus();
        }
      } else {
        if (feedback) {
          feedback.innerHTML = '<span style="color: #d97706;">⚠ CEP não localizado. Preencha o endereço manualmente.</span>';
        }
      }
    } catch (err) {
      if (feedback) {
        feedback.innerHTML = '<span style="color: #6b7280;">Não foi possível consultar o CEP automaticamente. Preencha os campos manualmente.</span>';
      }
    }
  };

  document.querySelectorAll('.mask-cep, #cep').forEach(input => {
    input.addEventListener('input', function () {
      this.value = formatCep(this.value);
      if (this.value.replace(/\D/g, '').length === 8) {
        consultarCep(this);
      }
    });

    input.addEventListener('blur', function () {
      this.value = formatCep(this.value);
      if (this.value.replace(/\D/g, '').length === 8) {
        consultarCep(this);
      }
    });
  });

  // 2.2 Formatação Monetária Automática (Ex: 1000 -> 1.000,00)
  const formatMoney = (val) => {
    if (val === null || val === undefined) return '0,00';
    let str = String(val).trim();
    if (str === '') return '0,00';

    str = str.replace(/[^\d,\.\-]/g, '');
    if (str === '') return '0,00';

    let num = 0;
    if (str.includes(',')) {
      // Ex: '1.000,50' ou '1000,5' ou '1000,00'
      const parts = str.split(',');
      const intPart = parts[0].replace(/\./g, '');
      const decPart = (parts.slice(1).join('') + '00').slice(0, 2);
      num = parseFloat(`${intPart}.${decPart}`);
    } else if (str.includes('.')) {
      const parts = str.split('.');
      if (parts.length > 2) {
        // Ex: 1.000.000
        num = parseFloat(str.replace(/\./g, ''));
      } else if (parts[1].length === 3 && parseInt(parts[0], 10) > 0) {
        // Ex: 1.000 (milhar)
        num = parseFloat(str.replace(/\./g, ''));
      } else {
        // Ex: 1000.5 ou 1000.50
        num = parseFloat(str);
      }
    } else {
      // Ex: '1000' -> 1000.00
      num = parseFloat(str);
    }

    if (isNaN(num) || num < 0) {
      num = 0;
    }

    return num.toLocaleString('pt-BR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  };

  const moneySelectors = '.mask-money, #rendaFamiliar, #despesasMensais, #orcamentoPrevisto, #valorRealizado, #valorEstimado';
  document.querySelectorAll(moneySelectors).forEach(input => {
    // Formatação inicial
    if (input.value && input.value.trim() !== '') {
      input.value = formatMoney(input.value);
    }

    // Ao finalizar a escrita (blur), formata no padrão monetário brasileiro
    input.addEventListener('blur', function () {
      this.value = formatMoney(this.value);
    });

    // Ao focar, se estiver 0,00, seleciona tudo para facilitar digitação
    input.addEventListener('focus', function () {
      if (this.value === '0,00') {
        this.select();
      }
    });

    // Permite digitação natural de caracteres monetários
    input.addEventListener('keypress', function (e) {
      const char = String.fromCharCode(e.which || e.keyCode);
      if (!/[\d,\.]/.test(char) && e.which !== 0 && e.which !== 8) {
        e.preventDefault();
      }
    });
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
