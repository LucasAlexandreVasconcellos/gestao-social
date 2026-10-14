# Sistema de Gestão Social e Assistencial (PHP)

Plataforma web completa desenvolvida em PHP moderno (8.2+) para gerenciamento de famílias, indivíduos em situação de vulnerabilidade, avaliações socioassistenciais, atendimentos, benefícios materiais, controle de estoque, doações, projetos sociais e relatórios analíticos.

---

## ⚡ Início Rápido (Executar a Aplicação)

Sem necessidade de configurar banco de dados, Docker ou Node.js:

```bash
# Iniciar o servidor embutido do PHP na pasta pública
php -S localhost:8000 -t public
```

Acesse no navegador: **[http://localhost:8000](http://localhost:8000)**

Para executar a suíte de testes automatizados:
```bash
php tests/run_tests.php
```

---

## 📌 Documentação Completa

- 📘 **[Guia de Arquitetura (ARCHITECTURE.md)](docs/ARCHITECTURE.md)**: Visão geral da arquitetura MVC, Service Layer, Repository Pattern e ciclo de vida das requisições.
- 🗄️ **[Guia para Integração com o Banco de Dados (DatabaseIntegration.md)](docs/DatabaseIntegration.md)**: **Documento fundamental para o desenvolvedor responsável pelo banco de dados.** Lista todas as 10 interfaces de repositório, contratos, operações atômicas necessárias e instruções passo a passo para conectar o banco sem alterar a aplicação.
- 📜 **[Catálogo de Regras de Negócio (BUSINESS_RULES.md)](docs/BUSINESS_RULES.md)**: Detalhamento das 16 regras de negócio fundamentais e validações implementadas no servidor.
- 🚀 **[Manual de Instalação e Execução (INSTALLATION.md)](docs/INSTALLATION.md)**: Requisitos, credenciais dos 6 perfis para teste e instruções detalhadas de execução.

---

## 🛡️ Regra Arquitetural de Persistência

> **O desenvolvimento do banco de dados não faz parte do escopo atual desta aplicação.**  
> Todas as operações de dados são mediadas por contratos (`app/Contracts/Repositories/`) e alimentadas em modo de demonstração por repositórios em memória (`app/Repositories/Demo/`).  
> A aplicação não contém nenhum script SQL, migration ou conexão ativa, estando 100% preparada para receber a camada de banco de dados desenvolvida pelo integrante responsável da equipe.