# Doctrine DBAL deprecation cleanup

PHPUnit's full-suite deprecation report identified deprecated calls to
`Doctrine\\DBAL\\Types\\Type::getType()` in the project's custom type tests.

The tests can instantiate the concrete types directly. This avoids the
deprecated process-global type registry without changing production mappings,
DBAL registration, or database schemas.

The full-suite report also includes distinct deprecations for
`Doctrine\\DBAL\\Types\\DateType` and `DateTimeType`. The
`Equipment` entity maps `date_start` and `date_end` to date-only values.
Those fields can use `DATE_IMMUTABLE` and `DateTimeImmutable` consistently.
The equipment form must opt in to Symfony's `datetime_immutable` input
format; setters accept both mutable and immutable input via
`DateTimeInterface`, copying the date to an immutable value before storing it.
The existing SQL date columns do not change.

This migration addresses the known equipment `DateType` mapping, **not
necessarily every source** of Doctrine's `DateType` or `DateTimeType`
deprecations. Rerun the full PHPUnit deprecation report and investigate
remaining callers separately before claiming a deprecation-free suite.

The external `runalyze/dem-reader` library is outside the scope of this
cleanup.

## Doctrine metadata cache after changing a mapped field type

When an existing installation is upgraded from `DATE_MUTABLE` to
`DATE_IMMUTABLE`, the PHP property expects `DateTimeImmutable`.
Stale ORM metadata may still specify `date` and cause Doctrine to hydrate
`DateTime` directly into the typed property, failing before any entity
setters are invoked. This is a cache invalidation problem, not a column
migration.

The metadata cache now uses its own `doctrine.metadata_cache_pool` rather
than the shared `doctrine.system_cache_pool`. Changing the pool identity
avoids reusing old field mappings and keeps query and metadata caches
separate. Clear and warm the Symfony cache during deployment whenever ORM
mapping attributes change, for example with
`APP_ENV=prod php bin/console cache:clear --env=prod`.

The repository regression test asserts the effective metadata types and
hydrates equipment through the dashboard's `findForPanel()` query after
clearing the identity map. If a running deployment still fails, inspect
its actual Doctrine metadata and restart long-running PHP processes after
cache clearing.
