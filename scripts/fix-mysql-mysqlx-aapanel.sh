#!/usr/bin/env bash
# Corrige MariaDB aaPanel: remove/comenta mysqlx-bind-address (não suportado em algumas versões).
set -euo pipefail

FILES=(
    /etc/my.cnf
    /www/server/mysql/my.cnf
    /etc/mysql/my.cnf
    /www/server/mysql/etc/my.cnf
)

fixed=0
for f in "${FILES[@]}"; do
    [[ -f "$f" ]] || continue
    if grep -q 'mysqlx-bind-address' "$f" 2>/dev/null; then
        cp "$f" "${f}.bak.$(date +%s)"
        sed -i 's/^[[:space:]]*mysqlx-bind-address/# mysqlx-bind-address disabled by clinix fix/' "$f"
        echo "Corrigido: $f"
        fixed=1
    fi
done

if [[ "$fixed" -eq 0 ]]; then
    echo "Nenhum arquivo padrão com mysqlx-bind-address. Busca global:"
    grep -r 'mysqlx-bind-address' /etc/my.cnf /www/server/mysql/ /etc/mysql/ 2>/dev/null || true
    exit 1
fi

/etc/init.d/mysqld stop 2>/dev/null || true
pkill -9 mariadbd mysqld 2>/dev/null || true
sleep 2
/etc/init.d/mysqld start
sleep 2
if ss -tlnp | grep -q ':3306'; then
    echo "MySQL OK na porta 3306."
else
    echo "MySQL ainda não subiu. Verifique: tail -20 /www/server/data/*.err"
    exit 1
fi
