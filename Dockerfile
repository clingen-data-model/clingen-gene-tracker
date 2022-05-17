FROM jward3/php:8.0-apache

LABEL maintainer="TJ Ward" \
    io.openshift.tags="gene-tracker:v1" \
    io.k8s.description="An application for tracking gene/disease curation process." \
    io.openshift.expose-services="8080:http,8443:https" \
    io.k8s.display-name="gene-tracker version 1" \
    io.openshift.tags="php,apache"

ENV XDG_CONFIG_HOME=/srv/app

# These need to be set so the artisan storage link can run.
ENV DX_USERNAME=dummy
ENV DX_PASSWORD=dummy
ENV DX_GROUP=dummy

USER root

WORKDIR /srv/app

COPY ./composer.lock ./composer.json /srv/app/
COPY ./database/seeds ./database/seeds
COPY ./database/factories ./database/factories

RUN composer install \
        --no-interaction \
        --no-plugins \
        --no-scripts \
        --no-dev \
        --no-suggest \
        --prefer-dist

COPY .docker/php/conf.d/* $PHP_INI_DIR/conf.d/

COPY .docker/start.sh /usr/local/bin/start

COPY . /srv/app

RUN chgrp -R 0 /srv/app \
    && chmod -R g+w /srv/app \
    && chmod g+x /srv/app/.openshift/deploy.sh \
    && chmod g+x /usr/local/bin/start
    # && pecl install xdebug-2.9.5 \
    # && docker-php-ext-enable xdebug \

RUN php artisan storage:link

USER 1001

CMD ["/usr/local/bin/start"]
