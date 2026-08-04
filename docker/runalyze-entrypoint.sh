#!/usr/bin/env bash
set -euo pipefail

umask 0002

export LANG="${LANG:-C.UTF-8}"
export LANGUAGE="${LANGUAGE:-C.UTF-8}"
export LC_ALL="${LC_ALL:-C.UTF-8}"

cd /var/www/html

mkdir -p \
  data/backup-tool/backup \
  data/backup-tool/import \
  data/cache \
  data/import \
  data/poster \
  data/sessions \
  data/views \
  var/cache \
  var/logs \
  var/poster \
  var/queue \
  var/sessions \
  var/tmp \
  web/uploads \
  /tmp/composer-cache

echo "Cleaning caches, logs and sessions ..."
for dir in \
    data/cache \
    data/sessions \
    var/cache \
    var/logs \
    var/sessions
do
    find "$dir" -mindepth 1 -not -name ".gitignore" -not -name ".gitkeep" -delete
done

chown -R www-data:www-data data var web/uploads || true
find data var web/uploads -type d -exec chmod 2775 {} +
find data var web/uploads -type f -exec chmod  664 {} +

setfacl -R -m   u:www-data:rwx data var
setfacl -R -m d:u:www-data:rwx data var
setfacl -R -m   g:www-data:rwx data var
setfacl -R -m d:g:www-data:rwx data var

# The old Travis CI setup did exactly this:
#   cp app/config/default_config.yml data/config.yml
if [ ! -f data/config.yml ] && [ -f app/config/default_config.yml ]; then
  echo "[runalyze] Creating data/config.yml from app/config/default_config.yml ..."
  cp app/config/default_config.yml data/config.yml
fi

# Adjust RUNALYZE legacy config for the container network.
if [ -f data/config.yml ]; then
  echo "[runalyze] Updating data/config.yml for container services ..."

  sed -i "s/database_host:.*/database_host:         ${DB_HOST:-db}/"           data/config.yml || true
  sed -i "s/database_port:.*/database_port:         ${DB_PORT:-3306}/"         data/config.yml || true
  sed -i "s/database_name:.*/database_name:         ${DB_NAME:-runalyze}/"     data/config.yml || true
  sed -i "s/database_user:.*/database_user:         ${DB_USER:-runalyze}/"     data/config.yml || true
  sed -i "s/database_password:.*/database_password: ${DB_PASSWORD:-runalyze}/" data/config.yml || true
fi

# Downloading timezone database
echo "Downloading timezone database ..."
for i in {1..3}; do
    tmp="./data/timezone.sqlite"
    domain="https://cdn.runalyze.com/update/timezone.sqlite"
    if wget --progress=bar:force -O "$tmp" "$domain"; then
        echo "✓ Downloaded timezone database successfully"
        break
    else
        if [ $i -lt 3 ]; then
            echo "⚠ Attempt $i failed, retrying in 5 seconds..."
            sleep 5
        else
            echo "⚠ Could download timezone database, using existing version"
        fi
    fi
done

# Compatibility patch for old Composer lockfile.
#   Original miniflux/picoFeed repository no longer works.
#   aaronpk/picofeed is a public fork of PicoFeed.
if [ -f composer.lock ]; then
  if grep -q "github.com/miniflux/picoFeed.git" composer.lock; then
    echo "[runalyze] Patching composer.lock PicoFeed repository URL ..."
    sed -i "s|https://github.com/miniflux/picoFeed.git|https://github.com/aaronpk/picofeed.git|g" composer.lock
  fi

  if grep -q "github.com/repos/miniflux/picoFeed" composer.lock; then
    echo "[runalyze] Patching composer.lock PicoFeed dist URL ..."
    sed -i "s|https://api.github.com/repos/miniflux/picoFeed|https://api.github.com/repos/aaronpk/picofeed|g" composer.lock
  fi
fi

if [ ! -d vendor ]; then
  echo "[runalyze] Installing PHP dependencies with Composer 1.x ..."
  composer install --prefer-source --no-interaction --ignore-platform-reqs
fi

# Show useful debugging info on startup.
php -v
composer --version

echo ""
echo "Waiting for database ..."
until mysqladmin ping \
    -h "${DB_HOST:-db}" \
    -P "${DB_PORT:-3306}" \
    -u "${DB_USER:-root}" \
    -p"${DB_PASSWORD:-root}" \
    --silent
do
    echo "Database not ready yet ..."
    sleep 2
done
echo "Database is ready."

echo ""
echo "=================================================="
echo "RUNALYZE started"
echo "Hostname    : $(hostname)"
echo "IPs         : $(hostname -I)"
echo "Port        : 8080"
echo "web-address : http://localhost:8080"
echo "DB Host     : ${DB_HOST:-db}"
echo "phpMyAdmin  : http://localhost:8081"
echo "Redis Host  : redis"
echo "MailHog Host: http://localhost:8025"
echo "=================================================="
echo ""

exec "$@"
