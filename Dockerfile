FROM ghcr.io/clingen-data-model/cgwi-php-11.7-8.2:v1.1.6

LABEL maintainer="UNC ClinGen Infrastructure Team" \
    io.openshift.tags="gene-tracker:v1" \
    io.k8s.description="An application for tracking gene/disease curation process." \
    io.openshift.expose-services="8080:http" \
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

COPY . /srv/app

RUN chgrp -R 0 /srv/app \
    && chmod -R g+w /srv/app \
    && chmod a+x /srv/app/.openshift/deploy.sh \
    && chmod a+x /srv/app/scripts/entrypoint.sh \
    && chmod a+x /srv/app/scripts/awaitdb.bash
    # && pecl install xdebug-2.9.5 \
    # && docker-php-ext-enable xdebug \

RUN php artisan storage:link

USER www-data:0

CMD ["/bin/bash"]
