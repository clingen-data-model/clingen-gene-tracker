# Running unit tests

## setup

This is currently a bit more complicated than it probably should be, and would be a good target
for "cleanup".

The "default" install from following the README doesn't include composer development dependencies,
so before starting, you need to shell into the app container and run `composer install` to get
those other dependencies.

Also, before running tests, it would behoove you to run `php artisan config:clear`, since it appears
that the cacheing is keeping something about the default database that makes testing glacial by
not recognizing that we want to use an sqlite in-memory database for testing.

This repo uses an actual mysql database (by default, named `testing`) for test runs, rather than an
in-memory sqlite database. So it has to be set up before testing will work. The db itself is
created in the `docker compose` process if run with the default `docker-compose.yml` file because
it sets `DB_DATABASE_TEST` (which is referenced under `.docker/mysql/db-init`, but that does not
create the schema and seed initial data. For that, you need to go into the container and run
something like:

```
php artisan migrate:fresh --database=testing --seed --drop-views
```

## from command line

Two options:

1. `php artisan test` (note: this command won't be there if you didn't run `composer install` as instructed...)
2. `vendor/bin/phpunit -c phpunit.xml`: this will give you the option to add additional options,
   filter which tests get run, etc.

## from IDE

You are mostly on your own here, but the .vscode directory gives some config that may help the
"PHPUnit Text Explorer" extension be able to run the tests in the docker container while you're using
vscode on your docker host.
