ARG PHP_VERSION=8.5.12
ARG DEBIAN_FRONTEND=noninteractive

ARG APP_ENV=dev

###############################################################################
# Builder Images
###############################################################################
FROM docker.io/composer/composer:2 AS composer

FROM docker.io/mlocati/php-extension-installer:latest AS php_extension_installer

###############################################################################
# Shared CLI Base (workers)
###############################################################################
FROM docker.io/php:$PHP_VERSION-cli AS base

ARG DEBIAN_FRONTEND
ARG APP_ENV

ENV APP_ENV=$APP_ENV \
    TZ=Europe/Berlin \
    LANG=C.UTF-8 \
    LANGUAGE=C.UTF-8 \
    LC_ALL=C.UTF-8 \
    COMPOSER_CACHE_DIR=/tmp/composer-cache

# Install only what the workers need: git/unzip for composer's fallback
# install (runalyze/* are installed from source, see composer.json). Python
# is deliberately NOT part of the base: only the poster and garmin workers
# need it, the backup worker stays a plain PHP-CLI image.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      ca-certificates \
      curl \
      git \
      unzip \
      hostname \
      tzdata \
    ; \
    rm -rf /var/lib/apt/lists/*

RUN ln -snf /usr/share/zoneinfo/Europe/Berlin /etc/localtime; \
    echo "Europe/Berlin" > /etc/timezone

# Install only the PHP extensions the message handlers need.
COPY --from=php_extension_installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    bcmath \
    intl \
    pdo_mysql \
    opcache

WORKDIR /var/www/html

# Composer: dependencies are installed before the sources are copied, so the
# expensive vendor layer stays cached when only the sources change. Autoload
# generation is skipped here: the classmap needs inc/ and plugin/, which only
# exist once the sources are copied (see the dump-autoload step below).
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --link composer.* ./
RUN set -eux; \
    mkdir -p inc plugin; \
    if [ "$APP_ENV" = "dev" ]; then \
      composer install --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader; \
    else \
      composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader; \
    fi; \
    composer --version

# Copy/link sources
COPY --link . ./

# The autoloader can only be dumped now that the classmap directories
# (inc/, plugin/) exist in the image.
RUN set -eux; \
    if [ "$APP_ENV" = "prod" ]; then \
      composer dump-autoload --no-dev --no-interaction; \
    else \
      composer dump-autoload --no-interaction; \
    fi

COPY docker/php/runalyze.ini /usr/local/etc/php/conf.d/99-runalyze.ini

# Entrypoint
COPY docker/runalyze-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/runalyze-entrypoint.sh

###############################################################################
# Workers (CLI only, one queue per target)
###############################################################################
FROM base AS worker

ENTRYPOINT ["/usr/local/bin/runalyze-entrypoint.sh"]

CMD ["php", "bin/console", "messenger:consume", "backup", "--time-limit=3600", "--memory-limit=256M", "--no-interaction"]

###############################################################################
# Python base for all workers that shell out to Python tools
###############################################################################
FROM worker AS worker-python

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      python3 \
      python3-venv \
    ; \
    rm -rf /var/lib/apt/lists/*

# Python virtual environment shared by the poster and Garmin workers
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

###############################################################################
# Poster worker (Python gpxtrackposter + librsvg)
###############################################################################
FROM worker-python AS worker-poster

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends librsvg2-bin; \
    rm -rf /var/lib/apt/lists/*

RUN pip install --no-cache-dir vendor/runalyze/gpxtrackposter/.

CMD ["php", "bin/console", "messenger:consume", "poster", "--time-limit=3600", "--memory-limit=256M", "--no-interaction"]

###############################################################################
# Garmin worker (Python garminconnect, prepared for future sync handlers)
###############################################################################
FROM worker-python AS worker-garmin

RUN pip install --no-cache-dir garminconnect

CMD ["php", "bin/console", "messenger:consume", "garmin", "--time-limit=3600", "--memory-limit=256M", "--no-interaction"]

###############################################################################
# Web Server
###############################################################################
FROM docker.io/php:$PHP_VERSION-apache AS app

ARG DEBIAN_FRONTEND
ARG APP_ENV

ENV APP_ENV=$APP_ENV \
    TZ=Europe/Berlin \
    LANG=C.UTF-8 \
    LANGUAGE=C.UTF-8 \
    LC_ALL=C.UTF-8 \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

# Install packages. Lean notes:
# - no *-dev headers: install-php-extensions manages its own build
#   dependencies and keeps only the runtime libraries
# - no python3-venv/-pip and no gpxtrackposter: poster rendering happens in
#   worker-poster; python3 and rsvg-convert stay only for the poster tool's
#   availability check (Component\Tool\Poster\Availability)
# - no inkscape: SVG-to-PNG conversion always uses rsvg-convert
#   (PosterGenerationMessageHandler::getConverter())
# - no openssh-client and no wget: composer clones via https, downloads use curl
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      acl \
      locales \
      git \
      unzip \
      zip \
      tzdata \
      nano \
      vim-tiny \
      ca-certificates \
      curl \
      mariadb-client \
      sqlite3 \
      libsqlite3-mod-spatialite \
      spatialite-bin \
      python3 \
      librsvg2-bin \
    ; \
    a2enmod rewrite headers expires; \
    rm -rf /var/lib/apt/lists/*

# Timezone
RUN ln -snf /usr/share/zoneinfo/Europe/Berlin /etc/localtime; \
    echo "Europe/Berlin" > /etc/timezone; \
    dpkg-reconfigure --frontend=noninteractive tzdata

# Set locale
RUN printf '%s\n' 'en_US.UTF-8 UTF-8' 'de_DE.UTF-8 UTF-8' > /etc/locale.gen; \
    locale-gen; \
    dpkg-reconfigure --frontend=noninteractive locales; \
    update-locale LANG=en_US.UTF-8

RUN echo 'umask 0002' >> /etc/bash.bashrc
RUN echo 'alias ll="ls -lrt --color"' >> /etc/bash.bashrc
RUN printf '%s\n' "PS1='\n\[\e[1;33m\]\t\[\e[0m\] -\[\e[1;32m\] \h\[\e[0m\] - \[\e[1;36m\]\u\[\e[0m\] - \[\e[1;37m\]\w \n\s - \!: '" >> /etc/bash.bashrc

# Install PHP Extensions
COPY --from=php_extension_installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    bcmath \
    gettext \
    intl \
    pdo_mysql \
    xsl \
    zip \
    mysqli \
    gd
# Dev only: xdebug for debugging, ast for static analysis
RUN if [ "$APP_ENV" = "dev" ]; then \
      install-php-extensions xdebug ast; \
      echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
      echo "xdebug.mode=develop,coverage"  >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
      echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
      echo "xdebug.cli_color=1"            >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    fi
# Prod only
RUN if [ "$APP_ENV" = "prod" ]; then \
      install-php-extensions opcache apcu; \
    fi

WORKDIR /var/www/html

# Composer: dependencies before the sources (layer caching), --no-dev for
# prod, and no swallowed failures (set -eux). Autoload generation is
# skipped here: the classmap needs inc/ and plugin/, which only exist
# once the sources are copied (see the dump-autoload step below).
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --link composer.* ./
RUN set -eux; \
    mkdir -p inc plugin; \
    if [ "$APP_ENV" = "dev" ]; then \
      composer install --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader; \
    else \
      composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader; \
    fi; \
    composer --version

# Copy/link sources
COPY --link . ./

# The autoloader can only be dumped now that the classmap directories
# (inc/, plugin/) exist in the image.
RUN set -eux; \
    if [ "$APP_ENV" = "prod" ]; then \
      composer dump-autoload --no-dev --no-interaction; \
    else \
      composer dump-autoload --no-interaction; \
    fi

# Apache
COPY docker/php/runalyze.ini /usr/local/etc/php/conf.d/99-runalyze.ini
COPY docker/apache/runalyze.conf /etc/apache2/sites-available/000-default.conf

# Entrypoint
COPY docker/runalyze-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/runalyze-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/runalyze-entrypoint.sh"]

CMD ["apache2-foreground"]

###############################################################################
# Full image (dev): app + Python stack in one artifact, so a single image
# can serve every role (web, backup, poster, garmin) via ENTRYPOINT_ROLE and
# compose command overrides. Production keeps the lean split targets.
###############################################################################
FROM app AS full

# Reuse the gpxtrackposter venv from the worker-python stage: it is
# self-contained and its python3 symlink resolves against the system
# python3 that the app stage already ships for the availability check.
COPY --from=worker-python /opt/venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"
