# Clinix - SaaS para Clínica Medica (PHP + MySQL)

Sistema SaaS para clínicas de pequeno e médio porte, com autenticação por sessão, controle de acesso por perfil e prontuário eletrônico compartilhado.

## Arquitetura

- PHP 8.2+ sem framework pesado
- Router central com papéis por rota (`app/Core/Router.php`)
- Estrutura em camadas (Controllers, Models, Core, Views)
- Front controller em `public/index.php` (use `public/router.php` em produção)
- PDO com prepared statements
- Sessão segura + CSRF + rate limit de login

## Segurança e compliance

- Anexos em `storage/uploads` (download autenticado, `/uploads` bloqueado)
- Política de senha forte (10+ caracteres) + troca obrigatória no primeiro login
- 2FA (TOTP) opcional por usuário
- Prontuário com retificação versionada (`patient_record_versions`)
- Validação de CPF com dígitos verificadores
- LGPD: retenção automática via cron + execução manual no compliance
- Auditoria consultável em Admin → Auditoria
- Esqueci minha senha (link no login; em local o link também vai para `storage/logs/password-reset.log`)
- Billing com checkout Stripe (`STRIPE_SECRET_KEY`) e webhook (`STRIPE_WEBHOOK_SECRET`)
- Limite de pacientes/usuários por plano (`max_patients`, `max_users`)

## Fila e painel TV

- Fila com AJAX (chamar/finalizar sem recarregar)
- Painel com polling leve (4s local); SSE só em produção (`PANEL_USE_SSE=0` para desativar)
- Histórico das últimas 5 chamadas no painel
- URL: `/?route=queue.panel&tenant=SLUG&token=TOKEN` (ver Admin → Token do painel)

## Administração

- Dashboard com KPIs (pacientes, fila, consultas do dia)
- Tokens de API: Admin → API
- Desativar 2FA: Admin → 2FA (com senha atual)
- Conflito de horário na agenda (mesmo profissional)

## API REST (token)

Headers: `X-Api-Token: seu-token` (cadastre em Admin → API)

- `GET /?route=api.v1.patients&q=busca`
- `GET /?route=api.v1.queue`

## Como rodar (local)

```bash
cp .env.example .env
# Ajuste DB_* no .env
mysql -u root -p < database/schema.sql
php database/migrate.php
php -S localhost:8000 -t public public/router.php
```

Acesse: http://localhost:8000/?route=login

## Docker

```bash
docker compose up --build
# App: http://localhost:8080
```

## Qualidade

```bash
./scripts/quality-check.sh
./scripts/smoke-test.sh
composer install --no-interaction
./vendor/bin/phpunit
```

## Backup

Ver [docs/BACKUP.md](docs/BACKUP.md) e `./scripts/backup-db.sh`.

## Cron (retenção LGPD + lembretes)

```bash
curl "http://localhost:8000/?route=cron.retention&secret=SEU_CRON_SECRET"
```

## Usuários demo (após migration 20260520)

- Clínica (slug): `clinica-demo`
- Usuários: `admin`, `recepção`, `enfermeira`, `médico`
- Senha inicial: `ChangeMe2026!` (troca obrigatória no primeiro login)

## Produção

- Use `public/router.php` no servidor built-in ou Apache/Nginx apontando para `public/`
- Configure `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`, `CRON_SECRET`, `APP_URL`, `MAIL_FROM` / `SMTP_*`
- Não exponha `storage/` nem `public/uploads/` diretamente
