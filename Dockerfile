ARG PHP_VERSION=8.1.34
ARG DEBIAN_FRONTEND=noninteractive

ARG APP_ENV=dev

###############################################################################
# Builder Images
###############################################################################
FROM docker.io/composer/composer:2 AS composer

FROM docker.io/mlocati/php-extension-installer:latest AS php_extension_installer

###############################################################################
# Web Server
###############################################################################
FROM docker.io/php:${PHP_VERSION}-apache

ENV APP_ENV=${APP_ENV} \
    TZ=Europe/Berlin \
    LANG=C.UTF-8 \
    LANGUAGE=C.UTF-8 \
    LC_ALL=C.UTF-8 \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

# Install Packages
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      acl \
      wget \
      locales \
      git \
      openssh-client \
      unzip \
      zip \
      tzdata \
      nano \
      vim-tiny \
      ca-certificates \
      curl \
      mariadb-client \
      sqlite3 \
      libsqlite3-dev \
      libsqlite3-mod-spatialite \
      spatialite-bin \
      libicu-dev \
      libzip-dev \
      zlib1g-dev \
      libpng-dev \
      libjpeg-dev \
      libfreetype6-dev \
      libxml2-dev \
      libmcrypt-dev \
      libcurl4-openssl-dev \
      libonig-dev \
      python3 \
      python3-pip \
      librsvg2-bin \
      inkscape \
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

# Install PHP Extensions
COPY --from=php_extension_installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    ast \
    bcmath \
    gettext \
    intl \
    pdo_mysql \
    xsl \
    zip \
    mysqli \
    gd
# Dev only
RUN if [ "$APP_ENV" = "dev" ]; then \
      install-php-extensions xdebug; \
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

# Copy/link sources
COPY --link . ./

# Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.* ./
RUN composer install --no-progress || true \
    composer --version

# Python dependencies for gpxtrackposter
#RUN pip3 install --no-cache-dir -r vendor/runalyze/gpxtrackposter/requirements.txt

# Apache
COPY docker/php/runalyze.ini /usr/local/etc/php/conf.d/99-runalyze.ini
COPY docker/apache/runalyze.conf /etc/apache2/sites-available/000-default.conf

# Entrypoint
COPY docker/runalyze-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/runalyze-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/runalyze-entrypoint.sh"]

CMD ["apache2-foreground"]
