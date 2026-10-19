#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")/.."
if [ ! -f .env ]; then cp .env.example .env; fi
# Generate values without requiring PHP/Composer on the host.
docker run --rm -v "$PWD:/project" -w /project php:8.5-cli-alpine php -r '
$p=".env"; $s=file_get_contents($p);
foreach (["DB_PASSWORD","MYSQL_ROOT_PASSWORD"] as $k) $s=preg_replace_callback("/^".$k."=\\s*$/m",fn()=> $k."=".bin2hex(random_bytes(32)), $s);
$s=preg_replace_callback("/^APP_KEY=\\s*$/m",fn()=>"APP_KEY=base64:".base64_encode(random_bytes(32)),$s); file_put_contents($p,$s);'
mkdir -p .local-data
docker compose pull db
docker compose build --pull
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
printf '%s\n' 'Siap. Buat admin: docker compose exec app php artisan ipl:create-admin' 'Buka http://localhost:8080'
