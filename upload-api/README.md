# DDB Cover Service Upload

[![Github](https://img.shields.io/badge/source-danskernesdigitalebibliotek/ddb--cover--service--upload-blue?style=flat-square)](https://github.com/danskernesdigitalebibliotek/ddb-cover-service-upload)
[![Tag](https://img.shields.io/github/v/tag/danskernesdigitalebibliotek/ddb-cover-service-upload?sort=semver&style=flat-square)](https://github.com/danskernesdigitalebibliotek/ddb-cover-service-upload/tags)
[![Build Status](https://img.shields.io/github/workflow/status/danskernesdigitalebibliotek/ddb-cover-service-upload/Review?label=CI&logo=github&style=flat-square)](https://github.com/danskernesdigitalebibliotek/ddb-cover-service-upload/actions?query=workflow%3A%22Review%22)
[![Codecov Code Coverage](https://img.shields.io/codecov/c/gh/danskernesdigitalebibliotek/ddb-cover-service-upload?label=codecov&logo=codecov&style=flat-square)](https://codecov.io/gh/danskernesdigitalebibliotek/ddb-cover-service-upload)
[![Read License](https://img.shields.io/packagist/l/danskernesdigitalebibliotek/ddb-cover-service-upload.svg?style=flat-square&colorB=darkcyan)](https://github.com/danskernesdigitalebibliotek/ddb-cover-service-upload/blob/master/LICENSE.txt)

Upload service for DDB Cover Service.

This is a Symfony 6 project based on the [Api-platform
framework](https://github.com/api-platform/api-platform).  Please see the
[Api-platform documentation](https://api-platform.com/docs/) for a basic
understanding of concepts and structure.

## Doctrine Migrations

The project uses [Doctrine
Migrations](https://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html)
to handle updates to the database schema. Any changes to the schema should have
a matching migration. If you make changes to the entity model you should run
`bin/console doctrine:migrations:diff` to generate a migration with the
necessary `sql` statements. Review the migration before executing it with
`bin/console doctrine:migrations:migrate`

After changes to the entity model and migrations always run `bin/console
doctrine:schema:validate` to ensure that mapping is correct and database schema
is in sync with the current mapping file(s).

## Development Setup

A `docker-compose.yml` file with a PHP 7.4 image is included in this project.
To install the dependencies you can run

```shell
docker compose up -d
docker compose exec phpfpm composer install
```

### Unit Testing

A PhpUnit/Mockery setup is included in this library. To run the unit tests:

```shell
docker compose exec phpfpm composer install
docker compose exec phpfpm ./vendor/bin/phpunit
```

### Psalm static analysis

We are using [Psalm](https://psalm.dev/) for static analysis. To run
psalm do

```shell
docker compose exec phpfpm composer install
docker compose exec phpfpm ./vendor/bin/psalm
```

### Check Coding Standard

The following command let you test that the code follows
the coding standard for the project.

* PHP files (PHP-CS-Fixer)

    ```shell
    docker compose exec phpfpm composer check-coding-standards
    ```

* Markdown files (markdownlint standard rules)

    ```shell
    docker run -it --rm -v "$PWD":/app -w /app node:16 yarn install
    docker run -it --rm -v "$PWD":/app -w /app node:16 yarn check-coding-standards
    ```

### Apply Coding Standards

To attempt to automatically fix coding style

* PHP files (PHP-CS-Fixer)

    ```sh
    docker compose exec phpfpm composer apply-coding-standards
    ```

* Markdown files (markdownlint standard rules)

    ```shell
    docker run -it --rm -v "$PWD":/app -w /app node:16 yarn install
    docker run -it --rm -v "$PWD":/app -w /app node:16 yarn apply-coding-standards
    ```

## CI

Github Actions are used to run the test suite and code style checks on all PR's.

If you wish to test against the jobs locally you can install [act](https://github.com/nektos/act).
Then do:

```sh
act -P ubuntu-latest=shivammathur/node:latest pull_request
```

## Versioning

We use [SemVer](http://semver.org/) for versioning.
For the versions available, see the
[tags on this repository](https://github.com/itk-dev/openid-connect/tags).

## License

This project is licensed under the AGPL-3.0 License - see the
[LICENSE.md](LICENSE.md) file for details
