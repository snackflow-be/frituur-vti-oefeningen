#!/usr/bin/env bash
# Draait één keer bij het aanmaken van de Codespace. Duurt ± 3 minuten.
set -euo pipefail
composer install --no-interaction --prefer-dist --quiet
npm install --silent
[ -f .env ] || cp .env.example .env
php artisan key:generate --quiet
touch database/database.sqlite
php artisan migrate:fresh --seed --quiet
npm run build --silent
echo
echo "Klaar. Start de site met:  php artisan serve --host 0.0.0.0"
echo "Draai de tests met:        php artisan test"
