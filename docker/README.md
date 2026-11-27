# Development environment configuration

The default Compose files are for development. They mount the checkout into the app and all workers. Start the stack with:

```sh
docker compose up -d
```

With a compatible Podman Compose provider, use `podman compose` instead.

## Compose configuration

Compose reads the shell environment and, by default, the project root `.env` for interpolation. Only variables explicitly mapped in the Compose configuration are passed to containers. Shell variables take precedence over values in the interpolation file.

Use an optional, untracked `.env.compose.local` for local infrastructure overrides. For example:

```dotenv
APP_PORT=8080
DB_PORT=3307
DATABASE_NAME=runalyze
DATABASE_USER=runalyze
DATABASE_PASSWORD=replace-with-local-password
DB_ROOT_PASSWORD=replace-with-local-root-password
```

Use the same file when creating or recreating the stack:

```sh
docker compose --env-file .env.compose.local up -d
```

The explicit env file replaces Compose's default interpolation file; the Compose configuration supplies defaults for omitted variables. Alternatively, export the infrastructure variables in the shell before invoking Compose.

The app, workers, MariaDB and phpMyAdmin use the same interpolated database credentials. `DATABASE_HOST=db` and `DATABASE_PORT=3306` are fixed inside the development network. `DB_PORT` is only the published host port. Changing MariaDB initialization variables does not change existing database accounts in an initialized volume; update those accounts separately before changing credentials.

Migrate old Compose overrides `DB_NAME`, `DB_USER` and `DB_PASSWORD` to `DATABASE_NAME`, `DATABASE_USER` and `DATABASE_PASSWORD`. Do not put Compose database credential overrides solely in Symfony's local Dotenv files.

## Symfony configuration

Symfony reads Dotenv files directly from the mounted checkout. Compose no longer injects the entire `.env.local` as real environment variables. A local file is not required for startup.

Symfony's usual precedence is `.env`, `.env.local`, `.env.<environment>`, then `.env.<environment>.local`, with real environment variables taking precedence. The standard test environment skips `.env.local`. For development-specific overrides, prefer `.env.dev.local`, especially for `APP_SECRET` and `APP_DEBUG` because `.env.dev` defines those defaults.

For example, in `.env.dev.local`:

```dotenv
MAILER_DSN=smtp://mailhog:1025
REDIS_URL=redis://redis:6379
MAIL_LOCALDOMAIN=http://localhost:8080
```

For authenticated SMTP, use a DSN such as `smtps://username:password@smtp.example.com:465` with URL-encoded credentials. Do not commit credentials.

Variables explicitly injected by Compose (including database credentials, test database connection settings, APP_ENV and role-specific Python paths) cannot be overridden through Symfony Dotenv. Configure infrastructure variables through Compose instead. Settings consumed directly by shell scripts must also be provided explicitly to those scripts or containers; Symfony Dotenv is not a shell environment file.

The test setup script defaults to the dedicated `db-test` service, never to `db`. It recreates the two configured test databases. For tests outside Compose, explicitly set `DB_TEST_HOST` and `DB_TEST_PORT` in the shell, along with any custom test database names and credentials. Use the same values for setup and PHPUnit. Never point test setup at a production database server. Both test environments disable mail and live Overpass requests by default. PHPUnit also sets its own application environment and mail transport. The local Compose and Symfony files are excluded from Git and the image build context.
