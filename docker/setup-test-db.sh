#!/usr/bin/env bash
#
# Prepares the databases of the tests ("composer test:setup"):
#  - the MySQL databases for the legacy tests and for the test_empty environment
#  - the schema of the SQLite database of the kernel tests
#
# The test database lives in RAM (service "db-test"), so run this again after it was restarted.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

DB_HOST="${DB_TEST_HOST:-${DB_HOST:-db}}"
DB_PORT="${DB_TEST_PORT:-${DB_PORT:-3306}}"
DB_ROOT_USER="${DB_ROOT_USER:-root}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-root}"
DB_UNITTEST_NAME="${DB_UNITTEST_NAME:-runalyze_unittest}"
DB_TEST_EMPTY_NAME="${DB_TEST_EMPTY_NAME:-runalyze_test_empty}"
DB_WAIT_TIMEOUT="${DB_WAIT_TIMEOUT:-60}"

mysql_args=(-h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_ROOT_USER}" -p"${DB_ROOT_PASSWORD}")

log() {
    echo "[test-setup] $*"
}

log "Waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."

waited=0
until mysqladmin ping "${mysql_args[@]}" --silent > /dev/null; do
    if [ "${waited}" -ge "${DB_WAIT_TIMEOUT}" ]; then
        log "ERROR: MySQL at ${DB_HOST}:${DB_PORT} did not answer within ${DB_WAIT_TIMEOUT}s." >&2
        exit 1
    fi

    sleep 2
    waited=$((waited + 2))
done

log "MySQL is ready."

log "Recreating the databases ${DB_UNITTEST_NAME} and ${DB_TEST_EMPTY_NAME} ..."
mysql "${mysql_args[@]}" -e "
    DROP DATABASE IF EXISTS \`${DB_UNITTEST_NAME}\`;
    DROP DATABASE IF EXISTS \`${DB_TEST_EMPTY_NAME}\`;
    CREATE DATABASE \`${DB_UNITTEST_NAME}\`;
    CREATE DATABASE \`${DB_TEST_EMPTY_NAME}\`;
"

log "Clearing the caches of the test environments ..."
rm -rf var/cache/test var/cache/test_empty \
    || log "WARNING: could not remove var/cache/test and var/cache/test_empty completely." >&2

log "Ensuring the legacy cache directory exists and is writable ..."
mkdir -p var/cache/legacy/cache
chmod -R 777 var/cache/legacy

log "Updating the schema of the SQLite database (environment test) ..."
php bin/console --env=test doctrine:schema:update --force

log "Updating the schema of the MySQL database (environment test_empty) ..."
php bin/console --env=test_empty doctrine:schema:update --force

log "Importing the legacy schema into ${DB_UNITTEST_NAME} ..."
mysql "${mysql_args[@]}" "${DB_UNITTEST_NAME}" < inc/install/structure.sql

log "Done."
