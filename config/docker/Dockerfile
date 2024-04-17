FROM php:8.3-fpm-alpine as parent-lenka-php-fpm

ARG SCRIPT_URL='https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions'
RUN set -eux && \
    curl -sSLf -o /usr/local/bin/install-php-extensions ${SCRIPT_URL} && \
    chmod +x /usr/local/bin/install-php-extensions && \
    apk add --update --no-cache \
      git bash supervisor gettext openssh supervisor npm openjdk11 nginx \
    && \
    rm -f /etc/nginx/nginx.conf /etc/nginx/conf.d/default.conf && \
    mkdir -p /var/log/supervisor /etc/supervisor/conf.d/ && \
    install-php-extensions \
      @composer intl zip opcache pdo pdo_pgsql \
      pcntl sockets apcu amqp redis xsl

ARG APP_ENV=dev
ARG SCRIPT_URL='https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh'
ARG PHP_IDE_CONFIG="serverName=Docker"
ENV PHP_IDE_CONFIG=$PHP_IDE_CONFIG
ARG DEV_XDEBUG_AUTOSTART=trigger
ARG DEV_HOST_IP=172.18.4.1
ARG DEV_XDEBUG_IDE_KEY=PHPSTORM
RUN if [ "$APP_ENV" = "dev" ]; then \
      install-php-extensions xdebug gd && \
      touch /var/log/xdebug.log && chmod 0666 /var/log/xdebug.log && \
      cd /usr/local/etc/php/conf.d/ && \
      echo "xdebug.mode=debug" >> docker-php-ext-xdebug.ini; \
      echo "xdebug.start_with_request=$DEV_XDEBUG_AUTOSTART" >> docker-php-ext-xdebug.ini; \
      echo "xdebug.client_host=$DEV_HOST_IP" >> docker-php-ext-xdebug.ini; \
      echo "xdebug.client_port=9003" >> docker-php-ext-xdebug.ini; \
      echo "xdebug.log=/var/log/xdebug.log" >> docker-php-ext-xdebug.ini; \
      echo "xdebug.idekey=$DEV_XDEBUG_IDE_KEY" >> docker-php-ext-xdebug.ini; \
      curl -1sLf ${SCRIPT_URL} | /bin/sh && \
      apk add --no-cache symfony-cli ranger vim nano vifm; \
    fi

COPY ./config/docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./config/docker/nginx/nginx-default.conf /etc/nginx/conf.d/default.conf
COPY ./config/docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY ./config/docker/supervisor/conf.d /etc/supervisor/conf.d/
COPY ./config/docker/php/memory_limit.ini /usr/local/etc/php/conf.d/memory_limit.ini

ARG SUPERVISOR_RPC_PORT=9551
ARG SUPERVISOR_RPC_USERNAME="default"
ARG SUPERVISOR_RPC_PASSWORD="default"

ARG PUID=1000
ARG PGID=1000

RUN cd /etc/supervisor/conf.d && \
    envsubst < monitor.conf > m && \
    mv m monitor.conf && \
    addgroup -g $PGID app && \
    adduser -D -u $PUID -G app app && \
    addgroup app www-data

USER app
COPY --chown=app:app . /var/www/lenka
WORKDIR /var/www/lenka

RUN if [ "$APP_ENV" != "dev" ]; then  \
      composer install --no-dev --no-scripts && \
      bin/console secrets:decrypt-to-local -e "$APP_ENV" && \
      composer dump-env "$APP_ENV" && \
      composer run post-install-cmd && \
      chmod -R g+w var && chgrp -R www-data var; \
    fi

USER www-data
EXPOSE 8080

CMD ["/usr/bin/supervisord"]
