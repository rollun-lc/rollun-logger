FROM ghcr.io/rollun-lc/rollun-ops/base-images/php-fpm-8.1

# Install xdebug
RUN pecl install xdebug-3.4.2 && docker-php-ext-enable xdebug

# Script to wait a port
COPY ./wait-for-it.sh /usr/local/bin/wait-for-it
RUN chmod 555 /usr/local/bin/wait-for-it

# php configs
COPY ./php-fpm.conf  /usr/local/etc/php-fpm.conf
COPY ./conf.d  /usr/local/etc/php/conf.d

# GID and UID mapping for www-data user
ARG WWW_DATA_UID=33
ARG WWW_DATA_GID=33

RUN usermod -u $WWW_DATA_UID www-data && groupmod -g $WWW_DATA_GID www-data

USER www-data
WORKDIR /var/www/app

CMD ["php-fpm", "-R"]