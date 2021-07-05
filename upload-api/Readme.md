# DDB Cover Service Upload

Upload service for DDB Cover Service

## Development

### Code style

The project follows the [PSR12](https://www.php-fig.org/psr/psr-12/) and
[Symfony](https://symfony.com/doc/current/contributing/code/standards.html) code
styles. The PHP CS Fixer tool is automatically installed. To check if your code
matches the expected code syntax you can run `composer check-coding-standards`,
to fix code style errors you can run `composer apply-coding-standards`

### Psalm static analysis

Where using [Psalm](https://psalm.dev/) for static analysis. To run
psalm do

```shell
docker compose exec phpfpm composer install
docker compose exec phpfpm ./vendor/bin/psalm
```

Psalm is set to level 3, with a baseline file to suppress errors in existing code.

### Tests

The application has a test suite consisting of unit tests.

* To run the unit tests located in `/tests` you can run `vendor/bin/phpunit`

Both bugfixes and added features should be supported by matching tests.

### Doctrine Migrations

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

