# Doctrine DBAL deprecation cleanup

PHPUnit's full-suite deprecation report identified deprecated calls to
`Doctrine\\DBAL\\Types\\Type::getType()` in the project's custom type tests.

The tests can instantiate the concrete types directly. This avoids the
deprecated process-global type registry without changing production mappings,
DBAL registration, or database schemas.

The report also includes the distinct deprecations for
`Doctrine\\DBAL\\Types\\DateType` and `DateTimeType`. These are not fixed by
replacing the test lookups. In particular, `Equipment` currently maps
`date_start` and `date_end` as `DATE_MUTABLE` and exposes mutable
`DateTime` in its API. Migrating these mappings to immutable types requires
a separate compatibility review of entity accessors, controllers, persistence,
and the schema. Do not replace the mapping or suppress the deprecations
without verifying those interactions.

The external `runalyze/dem-reader` library is outside the scope of this
cleanup.
