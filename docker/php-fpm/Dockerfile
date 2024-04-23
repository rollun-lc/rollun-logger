FROM maxrollundev/php-fpm-dev-7.2

USER root

RUN apt-get update --fix-missing && apt-get install -y vim

COPY ./php-fpm.conf  /usr/local/etc/php-fpm.conf
COPY ./conf.d  /usr/local/etc/php/conf.d

WORKDIR /var/www/app

CMD ["php-fpm", "-R"]