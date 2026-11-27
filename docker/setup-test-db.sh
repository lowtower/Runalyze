#!/usr/bin/env bash
#
# Prepares the databases of the tests ("composer test:setup"):
#  - the MySQL databases for the legacy tests and for the test_empty environment
#  - the schema of the SQLite database of the kernel tests
#
# The test database lives in RAM (service "db-test"), so run this again after it was restarted.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

export DB_TEST_HOST="${DB_TEST_HOST:-db-test}"
export DB_TEST_PORT="${DB_TEST_PORT:-3306}"
DB_ROOT_USER="${DB_ROOT_USER:-root}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-root}"
export DB_UNITTEST_NAME="${DB_UNITTEST_NAME:-runalyze_unittest}"
export DB_TEST_EMPTY_NAME="${DB_TEST_EMPTY_NAME:-runalyze_test_empty}"
DB_WAIT_TIMEOUT="${DB_WAIT_TIMEOUT:-60}"

log() {
    echo "[test-setup] $*"
}

if [[ "${DB_TEST_HOST}" == "db" ]]; then
    log "ERROR: Refusing to recreate test databases on the development database service." >&2
    exit 1
fi

if [[ ! "${DB_UNITTEST_NAME}" =~ ^[a-zA-Z0-9_]+$ || ! "${DB_TEST_EMPTY_NAME}" =~ ^[a-zA-Z0-9_]+$ ]]; then
    log "ERROR: Test database names must contain only letters, digits and underscores." >&2
    exit 1
fi

if [[ "${DB_UNITTEST_NAME}" == "${DB_TEST_EMPTY_NAME}" ||
      "${DB_UNITTEST_NAME}" == "${DATABASE_NAME:-runalyze}" ||
      "${DB_TEST_EMPTY_NAME}" == "${DATABASE_NAME:-runalyze}" ]]; then
    log "ERROR: Test database names must be distinct from each other and the application database." >&2
    exit 1
fi

mysql_args=(-h"${DB_TEST_HOST}" -P"${DB_TEST_PORT}" -u"${DB_ROOT_USER}" -p"${DB_ROOT_PASSWORD}")

log "Waiting for MySQL at ${DB_TEST_HOST}:${DB_TEST_PORT} ..."

waited=0
until mysqladmin ping "${mysql_args[@]}" --silent > /dev/null; do
    if [ "${waited}" -ge "${DB_WAIT_TIMEOUT}" ]; then
        log "ERROR: MySQL at ${DB_TEST_HOST}:${DB_TEST_PORT} did not answer within ${DB_WAIT_TIMEOUT}s." >&2
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
