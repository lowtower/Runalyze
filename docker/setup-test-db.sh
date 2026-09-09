#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

DB_HOST="${DB_TEST_HOST:-${DB_HOST:-db}}"
DB_PORT="${DB_TEST_PORT:-${DB_PORT:-3306}}"
DB_ROOT_USER="${DB_ROOT_USER:-root}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-root}"
DB_TEST_NAME="${DB_TEST_NAME:-runalyze_test}"
DB_UNITTEST_NAME="${DB_UNITTEST_NAME:-runalyze_unittest}"
DB_TEST_EMPTY_NAME="${DB_TEST_EMPTY_NAME:-runalyze_test_empty}"

echo "[test-setup] Waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."

until mysqladmin ping \
    -h"${DB_HOST}" \
    -P"${DB_PORT}" \
    -u"${DB_ROOT_USER}" \
    -p"${DB_ROOT_PASSWORD}" \
    --silent;
do
    sleep 2
done

echo "[test-setup] MySQL is ready."

echo "[test-setup] Creating test databases ..."
mysql \
    -h"${DB_HOST}" \
    -P"${DB_PORT}" \
    -u"${DB_ROOT_USER}" \
    -p"${DB_ROOT_PASSWORD}" \
    -e "
        DROP DATABASE IF EXISTS \`${DB_TEST_NAME}\`;
        DROP DATABASE IF EXISTS \`${DB_UNITTEST_NAME}\`;
        DROP DATABASE IF EXISTS \`${DB_TEST_EMPTY_NAME}\`;
        CREATE DATABASE \`${DB_TEST_NAME}\`;
        CREATE DATABASE \`${DB_UNITTEST_NAME}\`;
        CREATE DATABASE \`${DB_TEST_EMPTY_NAME}\`;
    "

echo "[test-setup] Clearing caches ..."

rm -rf var/cache/test var/cache/test_empty || true

echo "[test-setup] Updating Doctrine schema (functional connection, MySQL) ..."

# php bin/console --env=test doctrine:schema:update --force --complete --em=default --connection=functional 2>/dev/null || \
php bin/console --env=test doctrine:schema:update --force --complete

echo "[test-setup] Updating Doctrine schema for empty MySQL DB ..."

php bin/console --env=test_empty doctrine:schema:update --force --complete

echo "[test-setup] Importing legacy schema into ${DB_UNITTEST_NAME} ..."

mysql \
    -h"${DB_HOST}" \
    -P"${DB_PORT}" \
    -u"${DB_ROOT_USER}" \
    -p"${DB_ROOT_PASSWORD}" \
    "${DB_UNITTEST_NAME}" \
    < inc/install/structure.sql

echo "[test-setup] Done."
