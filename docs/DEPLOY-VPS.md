# Deploy do Clinix na VPS

Guia para hospedar em servidor próprio (ex.: IP `62.72.63.161`).

## Requisitos na VPS

- Ubuntu 22.04+ ou Debian 12+ (recomendado)
- Docker Engine + plugin Compose
- Portas **80** (HTTP) e **22** (SSH) liberadas no firewall
- Mínimo: 1 vCPU, 2 GB RAM, 10 GB disco

## 1. Preparar o servidor

```bash
ssh root@62.72.63.161

apt update && apt upgrade -y
apt install -y git curl ufw

# Docker (oficial)
curl -fsSL https://get.docker.com | sh
usermod -aG docker $USER
# saia e entre de novo no SSH para aplicar o grupo docker

ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
```

## 2. Clonar o projeto

```bash
cd /opt
git clone https://github.com/douglasmouradev/clinix.git
cd clinix
```

## 3. Configurar ambiente

```bash
cp .env.production.example .env
nano .env
```

Ajuste obrigatoriamente:

| Variável | Exemplo |
|----------|---------|
| `APP_URL` | `http://62.72.63.161` ou `https://clinix.seudominio.com` |
| `APP_ENV` | `production` |
| `DB_PASS` | senha forte |
| `MYSQL_ROOT_PASSWORD` | senha forte |
| `PANEL_ACCESS_TOKEN` | token longo aleatório |
| `CRON_SECRET` | segredo para cron |

Gere senhas:

```bash
openssl rand -hex 24
```

## 4. Subir com um comando

```bash
chmod +x scripts/deploy-vps.sh
./scripts/deploy-vps.sh
```

Ou manualmente:

```bash
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec -T db mysql -uclinix -pSENHA clinix < database/schema.sql
docker compose -f docker-compose.prod.yml exec app php database/migrate.php
```

## 5. Acessar

- Login: `http://62.72.63.161/?route=login`
- Health: `http://62.72.63.161/health.php`
- Painel TV: Admin → Token do painel (URL com `tenant` + `token`)

**Demo (após migrations):**

- Slug: `clinica-demo`
- Usuários: `admin`, `recepção`, `enfermeira`, `médico`
- Senha: `ChangeMe2026!` — troque no primeiro login

## 6. Cron (LGPD)

```bash
crontab -e
```

```cron
0 3 * * * curl -fsS "http://62.72.63.161/?route=cron.retention&secret=SEU_CRON_SECRET" >/dev/null
```

## 7. HTTPS com domínio (recomendado)

Com domínio apontando para o IP (`A` record → `62.72.63.161`):

```bash
apt install -y nginx certbot python3-certbot-nginx
```

`/etc/nginx/sites-available/clinix`:

```nginx
server {
    listen 80;
    server_name clinix.seudominio.com;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

No `.env` use `APP_HTTP_PORT=8080` e `APP_URL=https://clinix.seudominio.com`, depois:

```bash
ln -s /etc/nginx/sites-available/clinix /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
certbot --nginx -d clinix.seudominio.com
docker compose -f docker-compose.prod.yml up -d --build
```

## 8. Atualizar versão

```bash
cd /opt/clinix
git pull origin main
./scripts/deploy-vps.sh
```

## 9. Backup

```bash
./scripts/backup-db.sh
# ou manual:
docker compose -f docker-compose.prod.yml exec db mysqldump -uclinix -p clinix > backup.sql
```

## Segurança

- Não commite o `.env` no Git
- Troque senhas demo após o primeiro acesso
- Restrinja SSH por chave (`PasswordAuthentication no`)
- MySQL **não** está exposto na internet (só rede interna Docker)
- Configure firewall (`ufw`) e considere Fail2ban

## Solução de problemas

| Problema | Ação |
|----------|------|
| Página em branco | `docker compose -f docker-compose.prod.yml logs app` |
| Erro de banco | Verifique `DB_HOST=db` no `.env` |
| 403 no painel | Use URL com `tenant` e `token` do admin |
| Permissão em uploads | `chmod -R 775 storage` na VPS |
