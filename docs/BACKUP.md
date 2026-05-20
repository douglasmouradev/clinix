# Backup do banco Clinix

## Backup manual

```bash
chmod +x scripts/backup-db.sh
./scripts/backup-db.sh
```

Arquivos em `storage/backups/`.

## Restauração

```bash
mysql -u root -p clinix < storage/backups/SEU_ARQUIVO.sql
```

## Cron LGPD + notificações

Agende no servidor (substitua o secret):

```bash
curl -fsS "https://SEU_DOMINIO/?route=cron.retention&secret=SEU_CRON_SECRET"
```

Recomendado: 1x por dia (ex.: 03:00).
