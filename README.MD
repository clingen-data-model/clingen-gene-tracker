# ClinGen Curation Tracker

This project attempts to track curations being conducted by 
curators working for a Gene Clinicians Expert Panel (GCEP).

## Installation
### Prerequisites
You must have the following to stand up the application locally
* Docker
* (if on a mac, you'll also need docker-deskop or colima-- for instance as in [macos-setup.md](macos-setup.md))

### Setup DOCKER_USER variable (probably only once ever)

To handle docker permissions issues in bind mounts, this project uses the convention of running
containers using the `DOCKER_USER` (formatted like `$UID:$GID`) as the user for the container.
You could set this in the `.env` file (described later on), or to avoid having to do this in other
projects that use the same convention, you can add a line like `export DOCKER_USER=$(id -u):$(id -g)`
or `export DOCKER_USER=${UID}:${GID}` to your `.zshrc`, `.bashrc` or equivalant in your home directory.

### Setup other configurable options (probably don't need to re-do often)

This project uses the convention that Laravel uses with most configurable options definable in
a `.env` file in the project root. There is an `.env.example` file to guide this...

```bash
cp .env.example .env
```

Then edit as needed... maybe you'll want to change the things that are defined as `changeme`...

### Seeding the database

Not necessarily "one-time setup", but probably not something that needs doing frequently. This needs
to be done at least once before anything will work, though.

Right now, seeding via Laravel has accumulated some technical debt, so the easiest way to do this
is from an existing database dump.

The database uses a standard mysql docker image, which runs sql or sql.gz files places in a certain
directory, but only if the container volume hasn't been initialized before. So if you have an existing
docker volume, you may need to remove it.

```bash
docker volume rm clingen-gene-tracker_db
```

Your database dump file (as sql or compressed as sql.gz) needs to go in the subdirectory
`.docker/mysql/db-init/` under the project root. `*.sql.gz` files in this directory are .gitignore-ed
to help avoid unintential committing of database dump files.

Then just start the database container with:

```bash
docker compose up -d db
```

This will take a minute or so-- if you watch things with `docker compose logs -f`, you'll see a
local-only server started for initialization, then it should restart listening on the docker network.

### Populate `vendor/` directory with dependencies

If you have php and composer on your host system, you could just run `composer app install`, but in
the name of reproducibility, it is probably better to run this using the php and composer versions
in the container.

**Note**: You may not need to do this initially-- the entrypoint script should take care
of running this if the vendor directory hasn't been populated. But you would need to do this whenever
dependencies get updated. This is the set of commands to run if you get errors about missing
dependencies in the PHP code.

```bash
docker compose run --no-deps --rm -it --entrypoint composer app install --no-interaction --no-plugins --no-scripts --prefer-dist --no-dev --no-suggest
docker compose run --no-deps --rm -it --entrypoint composer app dump-autoload
```

## Running

Once everything above is setup, you should be able to just run the following:

```bash
docker compose up -d
```

After some initialization (which you can watch using `docker compose logs -f`), the app should
start responding to requests at the port given by `APP_PORT` in the `.env` file. By default,
this will be reachable at `http://localhost:8012`.

## Accessing container services

The `docker-compose.yml` only exposes the nginx container to the host. The primary reason for this is
to prevent various containers in different project (e.g., mysql or redis containers) from stepping
on each others' toes by trying to open the same port. Your options for getting to those services are:

* running `docker compose exec -it db`, then using the command line utilities there to access data
* running a one-off container with `docker compose run` and a `socat` container to be on that network
  and forward from this container to the one you're trying to access. This temporary port forwarding
  is left as an exercise to the reader.

## Frontend (this section needs to be re-worked since the frontend is run by default in the container)

To work on the front end client you will need:
* node
* npm

To build and watch client-side assets for development:
```
npm install
npm run serve

```
Not that this does not enable hot module replacement. You must shift+reload to see updates in the browser.

Client builds should be committed to the repository.  To build client-side assets for deployment:
```
npm run build
```

## Terms

* **Gene** - A gene. Usually represented by a [HGNC](https://www.genenames.org/) gene symbol.
* **Phenotype** - The set of observable characteristics of an individual resulting from the interaction of its genotype with the environment.
* **Disease Entity** - A disease of clinical interest. May be composed of many phenotypes.
* **Curation Curation** - A gene & disease entity pair.
* **ExpertPanel** - AKA GCEP. A group of experts consulting on the curation of a curation curation.
* **WorkingGroup** - A group working on genetic disorders. Has many ExpertPanels.

## Backend Architecture
The backend of this application is mostly idiomatic Laravel.  Requests are routed to controllers which either do the work themselves or delegate to jobs (often run synchronously).

### Model Revisions
Revision of models is tracked using the [venturecraft/revisionable](https://github.com/VentureCraft/revisionable) package.  This package stores a record in the revisions table for every create, update, and delete operation on a model.  records in the `revisions` table can be used to try to figure what happened when and who did it.

### Admin dashboard
The admin dashboard is built using the [Laravel Backpack](https://backpackforlaravel.com/) library.  Users with admin or programmer roles have access to the admin dashboard and can perform various tasks.

### Roles & Permissions
System roles and permissions in the GT are handled by [Spatie's laravel-permissions](https://github.com/spatie/laravel-permission/) package.

See [Roles & Permissions docs](/documentation/roles-and-permissions.md) for details

### Expert Panel Membership
Users can be members of 0 or more expert panels.

Expert panel access options are fairly rudimentary.  A user can have 0-3 options levels for the expert panel, though several overlap:
* Curator - allows the user to create & update curations they have created for the group.
* Edit Curations - allows the user to create & update any curation in the group.
* Coordinator - allows the user to create & update any curation in the group AND allows downloading of exports AND indicates this user will recieve notifiations about the expert panel and their curations.

Users can be assigned to expert panels and granted access on their edit screen in the admin section.

### Clingen DataExchange Integration
The ClinGen Data Exchange (DX) is a [Kafka](https://kafka.apache.org/) message broker run by the Broad Grant, hosted on https://conflluent.io.

The GT integrates with the DX to publish messages about pre-curation records on the `gt-precuration-events` topic. See the [examples](public/data-exchange/gt-precuration-events-example.json) and a [json schema](public/data-exchange/gt-precuration-events-schema.json) details about `gt-precuration-events`

The GT consumes data from the following topics:
* `gene-validity-events` - Messages GCI gene curations are used to link GT pre-curations to GCI gene curations and keep the two synchronized.
* `mondo-notifications` - Although the GT is monitoring the current state of MonDO to notify coordinators about changes, it also consumes messages from the `mondo-notifications` topic produced by the Broad team.  Messages from this topic are used to keep coordinators up to date on proposed changes to the MonDO terms.

For details about the implementation of the integration see the [DX implementation docs](/documentation/dx-integration.md).

### Notifications
Most notifications in the GT are "digestible" meaning they implement `App\Notifications\DigestibleNotificationInterface` and are sent in a weekly digest format.

Digestible notifications are stored to the database when they are "sent".  Once a week `App\Console\Commands\SendNotificationDigest` is invoked by the Laravel task scheduler which aggregates pending notifications for each user and sends them via email.

### External API
An external API has been added to allow third parties to request data from the GT.  This was created to facilitate the GCI's integration with the GT.

Clients of the external api must have an entry in the api_clients table and have been granted a personal access token.

Clients can be created in one of two ways:
1. by running `php artisan api-client:create <client-name> <client-email>` command.  This will print the new client id.
2. Using the [admin UI](https://gene-tracker.clinicalgenome.org/admin/api-client)

Tokens can be created for the client by 
1. running `php artisan api-client:create-token <client-id> <token-name>`.  
2. clicking the 'Create access token' link for the api-client in the [admin UI](https://gene-tracker.clinicalgenome.org/admin/api-client).

Tokens will only be displayed immediately after creation.  The token should be copied and securely conveyed to the client.  There is not limit to the number of tokens a an api-client can have.

api-clients must include their access token in the header of requests:

```
curl -i -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" https://gene-tracker.clinicalgenome.org/api/v1/pre-curations/10
```

Currently only two endpoints are supported:
* GET - `pre-curations` - Returns a paginated list of pre-curation summaries.
* GET - `pre-curations/[pre-curation-id]` - Returns a complete pre-curation record where `[pre-curation-id]`is id either:
    * The numeric id of the GT pre-curation record
    * The uuid of the GT pre-curation record
    * The gci gdm-uuid of the curation associated to the pre-curation

Full OpenApi documentation for the external API can be found at the api root: https://gene-tracker.clinicalgenome.org/api/v1/.  Note that authentication is required to access the API documentation.

### Other GCI integration points
Precurations can be accessed at their detail screen by using any of the followin ids in the URL:
  * numeric id
  * gt uuid
  * gdm uuid

A button as been added to the MonDO tab in the curation edit screen with the label 'Complete PreCuration and Go to GCI'.  Clicking this button will:
  * Store any changes made to the pre-curation
  * Set the status to 'Precuration Complete'
  * Redirect the the user to the GCI with relevant info from the pre-curation to start a new GCI curation record.
For the button is disabled unless the following requirements are met:
  * The pre-curation has gene, disease, and moi.
  * The pre-curation does not have a gdm_uuid associated with it.

NOTE: this feature is currently turned off.  To turn the feature on set the `SEND_TO_GCI_ENABLED` environment variable to `true`.  This can be done in the web-console or via the OpenShift command-line client: `oc set env dc/app SEND_TO_GCI_ENABLED=true`

### Data integrations
#### OMIMs
A copy of all OMIM phenotypes is updated daily at 3am by a scheduled task that runs the `App\Console\Commands\UpdateOmimData` command followed by `App\Console\Commands\UpdateOmimMovedAndRemoved`.  These commands can also be run manaully from the command line: `php artisan omim:update-data` and `php artisan omim:check-moved-and-removed` respectively.

Phenotypes are linked to genes in the database.

Updates to OMIM phenotype status or nomenclature will trigger notifications to coordinators if those phenotypes are connected to any pre-curations in the GT

An api key is needed to access the OMIM downloads and API.  Request a key at https://omim.org/api.  The key should be set in as the `OMIM_API_KEY` environment variable.

Note that OMIM revokes access regularly (annually, I think).  You will need to fill out the form linked above and with the same email address and access will be re-granted for the same code.

#### HGNC
A copy of HGNC data is updated daily at 1am by a scheduled task that runs `App\Hgnc\Artisan\ImportHgncCustomDownload`. 

#### MonDO
A copy of all MonDO terms is updated daily at 2am by a scheduld task that runs `App\Console\Commands\Mondo\UpdateMondoData`.

Updates to disease nomenclature are stored to the database to be sent with the weekly digest.  Notifications about term obsoletion are sent to coordinators immediately.

## Frontend Architecture
The frontend client is built using [Vue 2](https://v2.vuejs.org/) with [vue-router v3](https://v3.router.vuejs.org/) for client side routing, vuex v3](https://v3.vuex.vuejs.org/) as a global store, and the [bootstrap-vue](https://bootstrap-vue.org/) component library.

Bundling is done with webpack & LaravelMix.

Find js and css in `resources/assets/js` and `resources/assets/css` respectively.

## DevOps
The demo and production instances of the GPM are hosted on UNC's [Cloudapps OpenShift cluster](https://console.cloudapps.unc.edu) in the `dept-genetracker` project.  OpenShift is RedHat's value-add to the Kubernetes open source project.  You're better off referencing Kubernetes documentation for anything that is not a proprietary OpenShift thing (i.e. Builds, BuildConfigs, etc.).

### Architecture
At a high level, the project is composed of: 
* MySQL server: persistent store for the application. Based on the [jward3/openshift-mysql](https://hub.docker.com/repository/docker/jward3/php) image.
* Redis server: application cache, and queue
* Laravel app, running in three "roles", web app, scheduled task runner, and queue worker. Based on the [jward3/php](https://hub.docker.com/repository/docker/jward3/php) image.
* A cronjob that backs up the database and writes to a persistent volume.
* A cronjob that cleans database backups.

This repository is built into a docker image via the *app* build config and stored in the app ImageStream.

The application image is deployed by three separate DeploymentConfigs to use the Laravel app in different contexts:
* `app` - Runs an apache web server with php.  This is deployment of the web-accessible app.
* `schuduler` - Runs `php artisan schudule:run` every minute to ensure scheduled tasks are executed. See the [Laravel scheduled docs](https://laravel.com/docs/8.x/scheduling) for details.
* `queue` - Starts a queue worker `php artisan queue:work` which processes jobs queued to the redis DeploymentConfig.  See https://laravel.com/docs/8.x/queues for details on queued jobs

The `CMD` for the image runs `.docker/start.sh` which runs the appropriate command based on the `CONTAINER_ROLE` environment variable.  Valid container roles include  `app`, `queue`, and `scheduler`.

### Topics
* [Updating SSL Certificates](documentation/ssl-cert-updated.md)


## Other Resources

* <b>[OMIM](https://www.omim.org/)</b> - Online Mendelian Inheritance in Man catalog of genes and genetic disorders
* <b>[MONDO](https://www.ebi.ac.uk/ols/ontologies/mondo)</b> - Monarch Disease Ontology: ontology of disease ontologies
* <b>[GCI](https://curation.clinicalgenome.org/)</b> - DB and interface for ClinGen Curators to manage classification of curation curations

