#!/bin/bash
set -e

cd /workspaces/-government-idss-laravel12

if [ ! -f .env ]; then
    echo "ERROR: .env is missing. Application not started."
    exit 1
fi

if [ ! -f database/government_idss.sqlite ]; then
    echo "ERROR: SQLite database is missing. Application not started."
    exit 1
fi

mkdir -p storage/logs

php artisan optimize:clear

if ! curl -fsS http://127.0.0.1:8000/up >/dev/null 2>&1; then
    nohup php artisan serve --host=0.0.0.0 --port=8000 \
        > storage/logs/codespaces-server.log 2>&1 &
fi

for i in {1..20}; do
    if curl -fsS http://127.0.0.1:8000/up >/dev/null 2>&1; then
        echo "SUCCESS: IDSS is running on port 8000."
        exit 0
    fi
    sleep 1
done

echo "ERROR: Laravel did not become healthy. Server log:"
tail -30 storage/logs/codespaces-server.log
exit 1
