FROM maxrollundev/php-fpm-dev-7.2

USER root

RUN set -eux; \
    sed -i -e 's|deb.debian.org/debian|archive.debian.org/debian|g' \
           -e 's|security.debian.org/debian-security|archive.debian.org/debian-security|g' /etc/apt/sources.list || true; \
    printf 'Acquire::Check-Valid-Until "false";\n' > /etc/apt/apt.conf.d/99no-check-valid-until; \
    apt-get update --fix-missing && apt-get install -y vim

COPY ./php-fpm.conf  /usr/local/etc/php-fpm.conf
COPY ./conf.d  /usr/local/etc/php/conf.d

RUN composer self-update

WORKDIR /var/www/app

CMD ["php-fpm", "-R"]