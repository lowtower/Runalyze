#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

DB_HOST="${DB_TEST_HOST:-${DB_HOST:-db}}"
DB_PORT="${DB_TEST_PORT:-${DB_PORT:-3306}}"
DB_ROOT_USER="${DB_ROOT_USER:-root}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-root}"

echo "[test-setup] Waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."

until mysqladmin ping \
  -h"${DB_HOST}" \
  -P"${DB_PORT}" \
  -u"${DB_ROOT_USER}" \
  -p"${DB_ROOT_PASSWORD}" \
  --silent
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
    DROP DATABASE IF EXISTS runalyze_test;
    DROP DATABASE IF EXISTS runalyze_unittest;
    CREATE DATABASE runalyze_test;
    CREATE DATABASE runalyze_unittest;
  "

echo "[test-setup] Creating data/config.yml if missing ..."

if [ ! -f data/config.yml ] && [ -f app/config/default_config.yml ]; then
  cp app/config/default_config.yml data/config.yml
fi

echo "[test-setup] Patching app runtime config ..."

sed -i "s/database_host:.*/database_host: db/"               data/config.yml || true
sed -i "s/database_port:.*/database_port: 3306/"             data/config.yml || true
sed -i "s/database_name:.*/database_name: runalyze/"         data/config.yml || true
sed -i "s/database_user:.*/database_user: runalyze/"         data/config.yml || true
sed -i "s/database_password:.*/database_password: runalyze/" data/config.yml || true

echo "[test-setup] Patching Symfony test configs ..."

sed -i "s/database_host:.*/database_host: db/"                  app/config/config_test.yml || true
sed -i "s/database_port:.*/database_port: 3306/"                app/config/config_test.yml || true
sed -i "s/database_name:.*/database_name: runalyze_test/"       app/config/config_test.yml || true
sed -i "s/database_user:.*/database_user: runalyze_test/"       app/config/config_test.yml || true
sed -i "s/database_password:.*/database_password: password/"    app/config/config_test.yml || true

if [ -f app/config/config_test_empty.yml ]; then
  sed -i "s/database_host:.*/database_host: db/"                app/config/config_test_empty.yml || true
  sed -i "s/database_port:.*/database_port: 3306/"              app/config/config_test_empty.yml || true
  sed -i "s/database_user:.*/database_user: runalyze_test/"     app/config/config_test_empty.yml || true
  sed -i "s/database_password:.*/database_password: password/"  app/config/config_test_empty.yml || true
fi

echo "[test-setup] Clearing caches ..."

rm -rf var/cache/test app/cache/test data/cache/* || true

echo "[test-setup] Updating Doctrine schema for runalyze_test ..."

php bin/console --env=test doctrine:schema:update --force --complete

echo "[test-setup] Importing legacy schema into runalyze_unittest ..."

mysql \
  -h"${DB_HOST}" \
  -P"${DB_PORT}" \
  -u"${DB_ROOT_USER}" \
  -p"${DB_ROOT_PASSWORD}" \
  runalyze_unittest \
  < inc/install/structure.sql

echo "[test-setup] Done."
