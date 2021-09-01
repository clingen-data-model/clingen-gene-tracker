FROM jward3/php:8.0-apache

LABEL maintainer="TJ Ward" \
    io.openshift.tags="gene-tracker:v1" \
    io.k8s.description="An application for tracking gene/disease curation process." \
    io.openshift.expose-services="8080:http,8443:https" \
    io.k8s.display-name="gene-tracker version 1" \
    io.openshift.tags="php,apache"

ENV XDG_CONFIG_HOME=/srv/app

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

RUN apt-get install -yqq librdkafka-dev \
    && pecl install rdkafka-5.0.0 \
    && apt-get install -y --no-install-recommends openssl \
    && sed -i 's,^\(MinProtocol[ ]*=\).*,\1'TLSv1.0',g' /etc/ssl/openssl.cnf \
    && sed -i 's,^\(CipherString[ ]*=\).*,\1'DEFAULT@SECLEVEL=1',g' /etc/ssl/openssl.cnf\
    && rm -rf /var/lib/apt/lists/*

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
