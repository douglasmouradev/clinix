# Clinix - SaaS para Clínica Medica (PHP + MySQL)

Sistema SaaS para clinicas de pequeno e medio porte, com autenticacao por sessão, controle de acesso por perfil e prontuário eletronico compartilhado.

## Arquitetura

- PHP 8.2+ sem framework pesado
- Estrutura em camadas (Controllers, Models, Core, Views)
- Front controller em `public/index.php`
- PDO com prepared statements em todos os acessos ao banco
- Sessão para autenticacao e autorizacao por papel

## Estrutura de pastas

- `app/Config`: configuracoes de ambiente
- `app/Core`: infraestrutura base (banco, auth, renderizacao)
- `app/Controllers`: regras de fluxo por modulo
- `app/Models`: acesso a dados
- `app/Views`: telas HTML/CSS/JS
- `database/schema.sql`: criacao do banco e dados iniciais
- `public/index.php`: ponto de entrada da aplicacao

## Perfis suportados

- Admin: gestao de usuários e perfis
- Recepção: cadastro/edicao de pacientes, geracao de senha e fila
- Enfermeira: chamada para triagem e pre-atendimento no prontuário
- Médico: consulta completa, diagnóstico, prescrição e observacoes

## Melhorias de producao implementadas

- Protecao CSRF em todos os formularios `POST`
- Sessão mais segura (cookie httponly/samesite + timeout de inatividade)
- Auditoria basica de operacoes criticas em `audit_logs`
- Busca de pacientes por nome, CPF e telefone
- Finalizacao de atendimento na fila (`done`) com acao na tela
- Mensagens de feedback (sucesso/erro) apos operacoes
- Rate limit de login por usuário/IP com bloqueio temporario
- Painel publico protegido por token de acesso
- Filtros de histórico clinico por tipo e período
- Exportacao de histórico clinico em CSV
- Controle de status de conta de usuário (ativo/inativo)
- Rotacao de token do painel via administração
- Base multi-tenant com isolamento por `tenant_id`
- Configuração por ambiente via `.env`
- Agenda de consultas com status operacional
- Triagem estruturada (PA, FC, temperatura, SpO2, glicemia, dor)
- Anexos avancados com exclusao segura
- Billing SaaS com planos, assinaturas e faturas
- Onboarding automatico de tenant (clínica + admin)
- Limites por plano (usuários ativos)
- LGPD avancado (consentimento, exportacao e anonimização)
- Relatórios executivos com KPIs e filtros por período
- Performance tuning com cache leve e novos indexes
- Hardening enterprise: histórico versionado de consentimento
- Política de retenção LGPD por tenant com execucao manual
- Exportacao executiva em CSV para diretoria

## Painel em TV (outra tela)

- URL protegida: `http://localhost:8000/?route=queue.panel&token=SEU_TOKEN`
- Token gerenciado pelo admin em `/?route=admin.panel` (fallback na variavel `PANEL_ACCESS_TOKEN` do `.env`)

## Como rodar

1. Crie seu arquivo de ambiente:
   - `cp .env.example .env`
2. Ajuste credenciais no `.env`.
3. Importe o SQL:
   - `mysql -u root -p < database/schema.sql`
4. Execute migrations incrementais:
   - `php database/migrate.php`
5. Inicie servidor local:
   - `php -S localhost:8000 -t public`
6. Acesse:
   - `http://localhost:8000/?route=login`

## Qualidade e smoke test

- Validacao completa: `./scripts/quality-check.sh`
- Teste de fumaca: `./scripts/smoke-test.sh`

## Usuários de teste

- admin / 123456
- recepção / 123456
- enfermeira / 123456
- médico / 123456

