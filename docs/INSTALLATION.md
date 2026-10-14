# Manual de Instalação e Execução

Este guia orienta como executar a plataforma de Gestão Social e Assistencial em ambiente de desenvolvimento local ou servidor web compatível.

> **IMPORTANTE:** O sistema foi projetado para funcionar **sem depender de banco de dados, Node.js, Composer ou compiladores**. Você só precisa do executável do PHP.

---

## 1. Requisitos do Sistema

- **PHP:** Versão 8.2 ou superior (testado e homologado no PHP 8.2+).
- **Extensões Recomendadas:** Extensões padrão do PHP (`session`, `json`, `spl`).
- **Navegador Web Moderno:** Google Chrome, Mozilla Firefox, Microsoft Edge ou Safari.
- **Banco de Dados:** **NENHUM**. Não é necessário instalar MySQL, PostgreSQL ou configurar qualquer SGBD nesta etapa.

---

## 2. Como Executar Usando o Servidor Embutido do PHP (Recomendado)

O PHP possui um servidor web embutido ideal para desenvolvimento e demonstração.

### Passo 1: Abrir o Terminal na Pasta Raiz do Projeto
Abra o prompt de comando (PowerShell, CMD ou Bash) e navegue até a pasta do projeto:
```bash
cd "c:\Users\lucas.vasconcellos\Downloads\Gestão Social\gestao_social"
```

### Passo 2: Iniciar o Servidor PHP Apontando para o Diretório `public`
Execute o comando abaixo:
```bash
php -S localhost:8000 -t public
```

### Passo 3: Acessar no Navegador
Abra seu navegador e acesse o endereço:
```
http://localhost:8000
```

A tela de login será exibida imediatamente.

---

## 3. Credenciais e Perfis de Acesso para Demonstração

Para facilitar a avaliação técnica e o teste de todos os fluxos e permissões, a tela de login possui botões de **Acesso Rápido em 1 Clique** para os 6 perfis institucionais:

| Perfil | E-mail de Teste | Senha Padrão | Foco de Acesso |
|---|---|---|---|
| **Administrador** | `admin@gestaosocial.org.br` | `demo123` | Acesso irrestrito a todos os módulos e configurações |
| **Coordenação** | `fernanda.lima@gestaosocial.org.br` | `demo123` | Supervisão geral, indicadores e projetos sociais |
| **Assistente Social** | `juliana.costa@gestaosocial.org.br` | `demo123` | Fichas familiares, avaliações, visitas e dados sensíveis |
| **Estoque** | `robson.almoxarife@gestaosocial.org.br` | `demo123` | Almoxarifado, entradas, saídas e controle de validades |
| **Atendimento** | `camila.recepcao@gestaosocial.org.br` | `demo123` | Recepção, cadastros básicos e agendamentos |
| **Consulta** | `auditoria@gestaosocial.org.br` | `demo123` | Acesso de leitura e relatórios gerenciais |

Você também pode alternar entre os perfis a qualquer momento sem sair da sessão, acessando o menu **Usuários e Perfis** (`/usuarios`).

---

## 4. Como Executar os Testes Automatizados

O sistema conta com uma suíte de testes automatizados completa que valida as regras de negócio, cálculos sociais, validações e controle de permissões.

Para executar os testes via linha de comando:
```bash
php tests/run_tests.php
```

### Resultados Verificados pelos Testes:
- [x] Validação de campos obrigatórios e sanitização
- [x] Algoritmo matemático do Módulo 11 para CPFs válidos e tratamento do CPF opcional
- [x] Formatação de CEPs brasileiros
- [x] Escape anti-XSS e hashing seguro de senhas com `password_hash()`
- [x] Geração e validação de tokens CSRF
- [x] Criação de famílias com geração de código sequencial (`FAM-xxx`)
- [x] Preservação de vínculos familiares históricos ao desvincular membros
- [x] Cálculo de renda familiar per capita e sugestão orientativa de vulnerabilidade
- [x] Proibição terminante de confirmação de entrega de benefícios sem itens
- [x] Efetivação de entregas com dedução em memória do estoque
- [x] Movimentações de estoque e alertas de estoque crítico e vencimento
- [x] Segregação conceitual entre doações financeiras e de produtos
- [x] Progressão de beneficiários em projetos (Inscrito -> Convocado -> Atendido -> Ausente)
- [x] Matriz de autorização RBAC por perfil de usuário

---

## 5. Como Executar em Servidor Apache (Opcional)

Caso prefira rodar a aplicação em um servidor Apache local (como XAMPP, Laragon ou WampServer):

1. Aponte o `DocumentRoot` do VirtualHost para o diretório `/public` da aplicação.
2. Certifique-se de que o módulo `mod_rewrite` do Apache está habilitado.
3. O arquivo `public/.htaccess` já está configurado para rotear todas as requisições para o `public/index.php`.
