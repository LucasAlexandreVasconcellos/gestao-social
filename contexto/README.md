# 📋 Pasta de Contexto — Sistema de Gestão Social e Assistencial

Esta pasta reúne a documentação completa de handover técnico do projeto, consolidando tudo o que já foi arquitetado, implementado, testado e auditado, bem como os próximos passos e o passo a passo para continuar o desenvolvimento em qualquer outra máquina.

---

## 🗂️ Estrutura dos Documentos de Contexto

| Arquivo | Descrição |
| :--- | :--- |
| **[01_o_que_foi_feito.md](01_o_que_foi_feito.md)** | Relatório minucioso de todas as funcionalidades, camadas (Core, Services, Repositories, Views, Controllers), DDLs, Triggers, regras de negócio e testes implementados com 100% de sucesso. |
| **[02_o_que_falta_fazer.md](02_o_que_falta_fazer.md)** | Backlog de pendências, funcionalidades recomendadas para expansão futura, melhorias de produção e tarefas em aberto. |
| **[03_como_rodar_em_outra_maquina.md](03_como_rodar_em_outra_maquina.md)** | Guia prático com comandos exatos para clonar o repositório, instalar pré-requisitos, configurar o banco de dados PostgreSQL e subir o servidor na nova máquina. |
| **[04_relatorio_auditoria_seguranca_lgpd.md](04_relatorio_auditoria_seguranca_lgpd.md)** | Relatório formal de auditoria técnica de segurança da informação (OWASP Top 10) e conformidade estrita com a LGPD (Lei nº 13.709/2018). |

---

## ⚡ Guia Rápido de 3 Passos na Nova Máquina

1. **Clonar o Repositório:**
   ```bash
   git clone https://github.com/LucasAlexandreVasconcellos/gestao-social.git
   cd gestao-social
   ```

2. **Copiar o arquivo de ambiente e configurar:**
   ```bash
   cp .env.example .env
   # Se tiver PostgreSQL local, ajuste as credenciais no .env ou execute:
   php scripts/setup_database.php [sua_senha_postgres]
   ```

3. **Iniciar o Servidor:**
   ```bash
   php -S localhost:8000 -t public
   ```
   Acesse [http://localhost:8000](http://localhost:8000) no seu navegador. O sistema funcionará imediatamente:
   - Se o PostgreSQL estiver configurado, usará os **10 Repositórios de Banco de Dados** com persistência real.
   - Se o banco ainda não estiver configurado, ele iniciará no **Modo Demonstração em Memória** de forma transparente, permitindo testes imediatos.
