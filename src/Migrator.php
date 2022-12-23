<?php

declare(strict_types=1);

namespace Hustlahusky\Migrations;

/**
 * @implements \IteratorAggregate<MigrationInfo>
 */
final class Migrator implements \IteratorAggregate
{
    private StorageInterface $storage;
    private SourceLocatorInterface $sourceLocator;

    /**
     * @var list<MigrationInfo>|null
     */
    private ?array $list = null;

    public function __construct(StorageInterface $storage, SourceLocatorInterface $sourceLocator)
    {
        $this->storage = $storage;
        $this->sourceLocator = $sourceLocator;
    }

    public function install(int $count): int
    {
        $count = \max($count, 0);
        $installed = 0;

        $generator = function () {
            foreach ($this->getIterator() as $item) {
                if (Status::NEW === $item->getStatus()) {
                    yield $item;
                }
            }
        };

        /** @var MigrationInfo $item */
        foreach ($generator() as $item) {
            $migration = $this->sourceLocator->getMigration($item);

            $migration->up();
            $this->storage->install($item);
            $item->markInstalled();

            $installed++;

            if (0 < $count && $installed === $count) {
                break;
            }
        }

        return $installed;
    }

    public function rollback(int $count): int
    {
        $count = \max($count, 0);
        $reverted = 0;

        $generator = function () {
            $list = $this->listMigrations();

            for ($i = \count($list) - 1; $i >= 0; $i--) {
                $item = $list[$i];

                if (Status::SKIPPED === $item->getStatus()) {
                    $item->markNew();
                    continue;
                }

                if (Status::INSTALLED === $item->getStatus()) {
                    yield $item;
                }
            }
        };

        /** @var MigrationInfo $item */
        foreach ($generator() as $item) {
            $migration = $this->sourceLocator->getMigration($item);

            $migration->down();
            $this->storage->rollback($item);
            $item->markNew();

            $reverted++;

            if (0 < $count && $reverted === $count) {
                break;
            }
        }

        return $reverted;
    }

    /**
     * @return \Generator<MigrationInfo>
     */
    public function getIterator(): \Generator
    {
        return yield from $this->listMigrations();
    }

    /**
     * @return list<MigrationInfo>
     */
    private function listMigrations(): array
    {
        if (null !== $this->list) {
            return $this->list;
        }

        $out = [];

        foreach ($this->storage->findInstalled() as $installed) {
            $out[$installed->getName()] = $installed;
        }

        foreach ($this->sourceLocator->findSources() as $source) {
            if (isset($out[$source->getName()])) {
                $out[$source->getName()]->setSource($source);
            } else {
                $out[$source->getName()] = $source;
            }
        }

        \uasort(
            $out,
            static fn (MigrationInfo $left, MigrationInfo $right) => $left->getCreatedAt() <=> $right->getCreatedAt(),
        );

        $this->list = [];
        $prevItem = null;
        foreach ($out as $item) {
            $this->list[] = $item;

            if (
                null !== $prevItem
                && Status::INSTALLED === $item->getStatus()
                && Status::INSTALLED !== $prevItem->getStatus()
            ) {
                $prevItem->markSkipped();
            }

            $prevItem = $item;
        }

        return $this->list;
    }
}
