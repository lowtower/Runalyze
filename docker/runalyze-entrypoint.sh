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

ENTRYPOINT_ROLE="${ENTRYPOINT_ROLE:-app}"

LOCK_FILE="/var/www/html/var/.entrypoint-setup.lock"
LOCK_TIMEOUT="${ENTRYPOINT_LOCK_TIMEOUT:-180}"

READY_FILE="/var/www/html/var/.entrypoint-cache-ready"
READY_TIMEOUT="${ENTRYPOINT_READY_TIMEOUT:-180}"

log() {
    echo "[runalyze] $(date '+%Y-%m-%d %H:%M:%S') - $*"
}

# =============================================================================
# Setup-Block (only ever run by the "app" role)
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
      var/email \
      var/log \
      var/poster \
      var/queue \
      var/sessions \
      public/uploads \
      /tmp/composer-cache

    rm -f "${READY_FILE}"

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

    touch "${READY_FILE}"
    log "Cache marked as ready (${READY_FILE})."

    # Startup Scripts
    if [ -f "/var/www/html/bin/download-timezone-sqlite.sh" ]; then
        bash /var/www/html/bin/download-timezone-sqlite.sh
    fi

    if [ -f "/var/www/html/bin/download-disposable-email-domains.sh" ]; then
        bash /var/www/html/bin/download-disposable-email-domains.sh
    fi
}

# -----------------------------------------------------------------------------
# Wait-only block (every role other than "app")
# -----------------------------------------------------------------------------
wait_for_cache_ready() {
    log "Role '${ENTRYPOINT_ROLE}' does not perform cache setup; waiting for the 'app' role to signal readiness ..."

    local waited=0
    while [ ! -f "${READY_FILE}" ]; do
        if [ "${waited}" -ge "${READY_TIMEOUT}" ]; then
            log "ERROR: Cache was not marked ready within ${READY_TIMEOUT}s (missing ${READY_FILE}). Is the 'app' container running and healthy?"
            exit 1
        fi

        sleep 2
        waited=$((waited + 2))
    done

    log "Cache is ready, continuing."
}

mkdir -p "$(dirname "${LOCK_FILE}")"

if [ "${ENTRYPOINT_ROLE}" = "app" ]; then
    (
        log "Warte auf Setup-Lock (${LOCK_FILE}) ..."
        if ! flock -w "${LOCK_TIMEOUT}" 200; then
            log "ERROR: Don't retrieve setup lock within ${LOCK_TIMEOUT}s."
            exit 1
        fi
        setup
    ) 200>"${LOCK_FILE}"
else
    wait_for_cache_ready
fi

echo ""
echo "=================================================="
echo "RUNALYZE started"
echo "Role        : ${ENTRYPOINT_ROLE}"
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
