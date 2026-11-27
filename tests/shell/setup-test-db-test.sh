#!/usr/bin/env bash
set -euo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT

mkdir -p "$tmp/project/docker" "$tmp/project/inc/install" "$tmp/bin"
cp "$project_dir/docker/setup-test-db.sh" "$tmp/project/docker/"
touch "$tmp/project/inc/install/structure.sql"

cat > "$tmp/bin/mock" <<'MOCK'
#!/usr/bin/env bash
set -euo pipefail
printf '%s %s\n' "${0##*/}" "$*" >> "$CALL_LOG"
if [[ "${0##*/}" == php ]]; then
    printf 'env %s %s %s %s\n' "$DB_TEST_HOST" "$DB_TEST_PORT" "$DB_UNITTEST_NAME" "$DB_TEST_EMPTY_NAME" >> "$CALL_LOG"
fi
if [[ "${0##*/}" == mysqladmin ]]; then
    exit "${PING_EXIT:-0}"
fi
MOCK
chmod +x "$tmp/bin/mock"
for command in mysqladmin mysql php; do
    ln -s mock "$tmp/bin/$command"
done

run_setup() {
    : > "$tmp/calls"
    env -i PATH="$tmp/bin:$PATH" CALL_LOG="$tmp/calls" "$@" \
        bash "$tmp/project/docker/setup-test-db.sh" > "$tmp/output" 2>&1
}

assert_rejected() {
    local expected="$1"
    shift
    if run_setup "$@"; then
        echo "Expected setup to reject: $*" >&2
        exit 1
    fi
    grep -Fq "$expected" "$tmp/output"
    if [[ -s "$tmp/calls" ]]; then
        echo "Unexpected external command before validation: $*" >&2
        cat "$tmp/calls" >&2
        exit 1
    fi
}

assert_rejected 'Refusing to recreate test databases' DB_TEST_HOST=db
assert_rejected 'must contain only letters' 'DB_UNITTEST_NAME=invalid-name'
assert_rejected 'must contain only letters' 'DB_TEST_EMPTY_NAME=invalid name'
assert_rejected 'must be distinct' DB_UNITTEST_NAME=same DB_TEST_EMPTY_NAME=same
assert_rejected 'must be distinct' DB_UNITTEST_NAME=runalyze
assert_rejected 'must be distinct' DATABASE_NAME=custom DB_TEST_EMPTY_NAME=custom

if run_setup PING_EXIT=1 DB_WAIT_TIMEOUT=0 DB_HOST=db DB_PORT=3307; then
    echo 'Expected setup to fail when the test database is unavailable.' >&2
    exit 1
fi
grep -Fq 'MySQL at db-test:3306 did not answer' "$tmp/output"
grep -Fq 'mysqladmin ping -hdb-test -P3306' "$tmp/calls"
[[ "$(wc -l < "$tmp/calls")" -eq 1 ]]

run_setup DB_TEST_HOST=127.0.0.1 DB_TEST_PORT=3308 DB_UNITTEST_NAME=custom_unit DB_TEST_EMPTY_NAME=custom_empty
grep -Fq 'mysqladmin ping -h127.0.0.1 -P3308' "$tmp/calls"
grep -Fq 'DROP DATABASE IF EXISTS `custom_unit`' "$tmp/calls"
grep -Fq 'DROP DATABASE IF EXISTS `custom_empty`' "$tmp/calls"
grep -Fq 'php bin/console --env=test doctrine:schema:update --force' "$tmp/calls"
grep -Fq 'php bin/console --env=test_empty doctrine:schema:update --force' "$tmp/calls"
[[ "$(grep -Fc 'env 127.0.0.1 3308 custom_unit custom_empty' "$tmp/calls")" -eq 2 ]]
grep -Fq 'mysql -h127.0.0.1 -P3308 -uroot -proot custom_unit' "$tmp/calls"

echo 'Test database setup regression tests passed.'
