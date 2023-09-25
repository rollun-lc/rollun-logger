FROM maxrollundev/php-fpm-7.2

USER root

RUN apt-get update --fix-missing && apt-get install -y vim

RUN apt-get install -y openssh-server

# Install xdebug
RUN pecl install xdebug-3.0.2 && docker-php-ext-enable xdebug

COPY ./php-fpm.conf  /usr/local/etc/php-fpm.conf
COPY ./conf.d  /usr/local/etc/php/conf.d

WORKDIR /var/www/app

CMD ["php-fpm", "-R"]