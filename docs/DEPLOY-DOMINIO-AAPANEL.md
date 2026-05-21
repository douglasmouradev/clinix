# Domínio no aaPanel (ex.: clinix.tdesksolutions.com.br)

Clinix em Docker fica em `/opt/clinix-app`. O aaPanel (Nginx) recebe o domínio na porta 80/443 e **repassa** para o container.

## 1. DNS

No painel do domínio `tdesksolutions.com.br`, crie:

| Tipo | Nome | Valor |
|------|------|--------|
| A | `clinix` | `62.72.63.161` |

Aguarde propagar (minutos a algumas horas). Teste: `ping clinix.tdesksolutions.com.br`

## 2. Docker na porta 8080 (aaPanel usa a 80)

```bash
cd /opt/clinix-app
nano .env
```

```env
APP_HTTP_PORT=8080
APP_URL=https://clinix.tdesksolutions.com.br
APP_ENV=production
```

```bash
docker compose -f docker-compose.prod.yml up -d
curl -s http://127.0.0.1:8080/health.php
```

## 3. Criar site no aaPanel

**Site → Adicionar site**

- Domínio: `clinix.tdesksolutions.com.br`
- Caminho: `/www/wwwroot/clinix.tdesksolutions.com.br` (pode ficar; o proxy ignora os arquivos)
- PHP: qualquer (será proxy) ou **Não criar** banco/FTP
- **Não marque SSL ainda** (aplique depois do proxy funcionar)

## 4. Reverse proxy (essencial)

**Site → clinix.tdesksolutions.com.br → Configuração → Reverse proxy**

Adicione proxy para:

```text
http://127.0.0.1:8080
```

Ou edite o Nginx do site e inclua:

```nginx
location / {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_read_timeout 300;
}
```

Salve e recarregue o Nginx no aaPanel.

## 5. SSL (HTTPS)

**Site → SSL → Let's Encrypt → Aplicar** para `clinix.tdesksolutions.com.br`

Depois confirme no `.env`:

```env
APP_URL=https://clinix.tdesksolutions.com.br
```

```bash
docker compose -f docker-compose.prod.yml restart app
```

## 6. Acesso

- https://clinix.tdesksolutions.com.br/?route=login
- https://clinix.tdesksolutions.com.br/health.php

Painel TV (token no admin):

`https://clinix.tdesksolutions.com.br/?route=queue.panel&tenant=clinica-demo&token=SEU_TOKEN`

## Problemas comuns

| Sintoma | Solução |
|---------|---------|
| 502 Bad Gateway | Container parado ou porta errada (`docker ps`, teste `curl 127.0.0.1:8080`) |
| Página do aaPanel / vazio | Falta reverse proxy |
| SSL não emite | DNS A record ainda não apontou para o IP |
| Login perde sessão | `APP_URL` deve ser `https://` com o domínio exato |

## Alternativa: PHP direto no wwwroot (sem Docker)

Se preferir site PHP nativo no aaPanel:

1. Clone em `/www/wwwroot/clinix.tdesksolutions.com.br`
2. Raiz do site no painel: `.../public`
3. PHP 8.2+, MySQL criado no aaPanel, `.env` com `DB_HOST=127.0.0.1`
4. `php database/migrate.php` no terminal

Não misture Docker na 8080 e PHP no mesmo domínio ao mesmo tempo.
