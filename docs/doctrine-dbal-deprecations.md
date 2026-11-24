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
