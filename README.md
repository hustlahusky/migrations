<div align="center">

# hustlahusky/migrations

Framework-agnostic, small migrations library

[GitHub][link-github] •
[Packagist][link-packagist] •
[Installation](#installation) •
[Usage](#usage)

</div>

## Installation

Via Composer

```bash
$ composer require hustlahusky/migrations
```

## Usage

```php
use Hustlahusky\Migrations\DefaultNamingStrategy;
use Hustlahusky\Migrations\Migrator;
use Hustlahusky\Migrations\PhpSourceLocator;
use Hustlahusky\Migrations\SourceLocatorInterface;
use function Hustlahusky\Migrations\lock;

/**
 * @var \PDO $pdo
 */

$namingStrategy = new DefaultNamingStrategy();
$sourceLocator = new PhpSourceLocator(MIGRATIONS_DIR, $namingStrategy);

// Look at tests/MysqlPdoStorage.php for storage implementation example
$storage = new MysqlPdoStorage($pdo, 'migrations', $namingStrategy);
$migrator = new Migrator($storage, $sourceLocator);

// Create migration file
$sourceLocator->registerMigration(
    new \DateTimeImmutable(),
    <<<PHP
    <?php

    declare(strict_types=1);
    
    namespace App\Migrations;
    
    use Hustlahusky\Migrations\MigrationInterface;
    
    return new class implements MigrationInterface {
        public function up(): void
        {
        }
        
        public function down(): void
        {
        }
    };
    PHP
);

// Install all new migrations
lock($storage, static fn () => $migrator->install(0));

// Rollback all installed migrations
lock($storage, static fn () => $migrator->rollback(0));

// Rollback last installed migration
lock($storage, static fn () => $migrator->rollback(1));

// List migrations
foreach ($migrator->getIterator() as $info) {
    echo $info->getName()
        . "\t" . $info->getStatus()
        . "\t" . $info->getCreatedAt()->format('Y-m-d H:i:s')
        . "\t" . (null !== $info->getInstalledAt() ? $info->getInstalledAt()->format('Y-m-d H:i:s') : '')
        . "\t" . ($info->sourceModified() ? 'modified' : '')
        . "\t" . ($info->sourceNotFound() ? 'notfound' : '')
        . \PHP_EOL;
}
```

## Credits

- [Constantine Karnaukhov][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [license file](LICENSE.md) for more information.

[link-github]: https://github.com/hustlahusky/migrations
[link-packagist]: https://packagist.org/packages/hustlahusky/migrations
[link-author]: https://github.com/hustlahusky
[link-contributors]: ../../contributors
