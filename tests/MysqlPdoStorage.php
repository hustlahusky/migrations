<?php

declare(strict_types=1);

namespace Hustlahusky\Migrations;

final class MysqlPdoStorage implements StorageInterface, LockInterface
{
    private \PDO $pdo;
    private string $table;
    private NamingStrategyInterface $namingStrategy;
    private bool $configured = false;

    public function __construct(\PDO $pdo, string $table, NamingStrategyInterface $namingStrategy)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->namingStrategy = $namingStrategy;
    }

    public function findInstalled(): array
    {
        $this->configure();

        $stmt = $this->pdo->query(
            <<<SQL
            SELECT name, inserted_at, hash
            FROM {$this->table}
            ORDER BY inserted_at
            SQL
        );

        if (!$stmt) {
            return [];
        }

        $out = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $createdAt = $this->namingStrategy->match($row['name']);
            if (null === $createdAt) {
                throw new \RuntimeException('migration name doesnt match naming strategy');
            }

            $out[] = MigrationInfo::fromStorage(
                $row['name'],
                $createdAt,
                new \DateTimeImmutable($row['inserted_at'], new \DateTimeZone('UTC')),
                $row['hash'],
            );
        }

        return $out;
    }

    public function install(MigrationInfo $item): void
    {
        $this->configure();

        $stmt = $this->pdo->prepare(
            <<<SQL
            INSERT INTO {$this->table} (name, hash, inserted_at)
            VALUES (?, ?, convert_tz(now(), 'SYSTEM', 'UTC'))
            SQL
        );
        $stmt->execute([$item->getName(), $item->getSourceHash()]);
    }

    public function rollback(MigrationInfo $item): void
    {
        $this->configure();

        $stmt = $this->pdo->prepare(
            <<<SQL
            DELETE FROM {$this->table}
            WHERE name = ?
            SQL
        );
        $stmt->execute([$item->getName()]);
    }

    public function configure(): void
    {
        if ($this->configured) {
            return;
        }

        $this->configured = true;
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists(): void
    {
        $this->pdo->exec(
            <<<SQL
            create table if not exists {$this->table}
            (
                name        varchar(255) not null,
                inserted_at datetime     not null,
                hash        text         not null,
                primary key (name)
            )
            SQL
        );
    }

    public function acquireLock(): bool
    {
        $lockName = $this->table;

        $stmt = $this->pdo->query(
            <<<SQL
            SELECT GET_LOCK('{$lockName}', 0) AS L
            SQL
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return 1 === \filter_var($row['L'] ?? 0, \FILTER_VALIDATE_INT);
    }

    public function releaseLock(): bool
    {
        $lockName = $this->table;

        $stmt = $this->pdo->query(
            <<<SQL
            SELECT RELEASE_LOCK('{$lockName}') AS L
            SQL
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return 1 === \filter_var($row['L'] ?? 0, \FILTER_VALIDATE_INT);
    }
}
