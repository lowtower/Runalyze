#!/usr/bin/env bash

set -euo pipefail

umask 0002

export LANG="${LANG:-C.UTF-8}"
export LANGUAGE="${LANGUAGE:-C.UTF-8}"
export LC_ALL="${LC_ALL:-C.UTF-8}"

cd /var/www/html

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-runalyze}"
DB_USER="${DB_USER:-runalyze}"
DB_PASSWORD="${DB_PASSWORD:-runalyze}"

LOCK_FILE="/var/www/html/var/.entrypoint-setup.lock"
LOCK_TIMEOUT="${ENTRYPOINT_LOCK_TIMEOUT:-180}"

log() {
    echo "[runalyze] $(date '+%Y-%m-%d %H:%M:%S') - $*"
}

# =============================================================================
# Setup-Block
# =============================================================================
setup() {
    log "Keep setup-lock (PID $$). Start initialisation ..."

    mkdir -p \
      data/backup-tool/backup \
      data/backup-tool/import \
      data/cache \
      data/import \
      data/poster \
      data/sessions \
      data/views \
      var/cache \
      var/cache/legacy/cache \
      var/log \
      var/poster \
      var/queue \
      var/sessions \
      var/tmp \
      public/uploads \
      /tmp/composer-cache

    log "Cleaning caches, logs and sessions ..."
    for dir in \
        data/cache \
        data/sessions \
        var/cache \
        var/log \
        var/sessions
    do
        find "$dir" -mindepth 1 -not -name ".gitignore" -not -name ".gitkeep" -delete
    done

    chown -R www-data:www-data data var public || true
    find data var public -type d -exec chmod 2775 {} +
    find data var public -type f -exec chmod  664 {} +

    setfacl -R -m   u:www-data:rwx data var public
    setfacl -R -m d:u:www-data:rwx data var public
    setfacl -R -m   g:www-data:rwx data var public
    setfacl -R -m d:g:www-data:rwx data var public

    # The old Travis CI setup did exactly this:
    if [ ! -f data/config.yaml ] && [ -f config/default_config.yaml ]; then
        log "Creating data/config.yaml from config/default_config.yaml ..."
        cp config/default_config.yaml data/config.yaml
    fi

    # Adjust RUNALYZE legacy config for the container network.
    if [ -f data/config.yaml ]; then
        log "Updating data/config.yaml for container services ..."

        sed -i "s/database_host:.*/database_host:         ${DB_HOST}/"           data/config.yaml || true
        sed -i "s/database_port:.*/database_port:         ${DB_PORT}/"           data/config.yaml || true
        sed -i "s/database_name:.*/database_name:         ${DB_NAME}/"           data/config.yaml || true
        sed -i "s/database_user:.*/database_user:         ${DB_USER}/"           data/config.yaml || true
        sed -i "s/database_password:.*/database_password: ${DB_PASSWORD}/"       data/config.yaml || true
    fi

    if [ ! -d vendor ]; then
        log "Installing PHP dependencies with Composer ..."
        composer install --prefer-source --no-interaction --ignore-platform-reqs
    fi

    # Show useful debugging info on startup.
    php -v
    composer --version

    echo ""
    log "Waiting for database ..."
    until mysqladmin ping \
        -h "${DB_HOST}" \
        -P "${DB_PORT}" \
        -u "${DB_USER}" \
        -p"${DB_PASSWORD}" \
        --silent
    do
        log "Database not ready yet ..."
        sleep 2
    done
    log "Database is ready."
}

# -----------------------------------------------------------------------------
# Serialise setup block with flock
# -----------------------------------------------------------------------------
mkdir -p "$(dirname "${LOCK_FILE}")"

(
    log "Warte auf Setup-Lock (${LOCK_FILE}) ..."
    if ! flock -w "${LOCK_TIMEOUT}" 200; then
        log "ERROR: Don't retrieve setup lock within ${LOCK_TIMEOUT}s."
        exit 1
    fi
    setup
) 200>"${LOCK_FILE}"

echo ""
echo "=================================================="
echo "RUNALYZE started"
echo "Hostname    : $(hostname)"
echo "IPs         : $(hostname -I)"
echo "Port        : 8080"
echo "web-address : http://localhost:8080"
echo "DB Host     : ${DB_HOST}"
echo "phpMyAdmin  : http://localhost:8081"
echo "Redis Host  : redis"
echo "MailHog Host: http://localhost:8025"
echo "=================================================="
echo ""

exec "$@"
