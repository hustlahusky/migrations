<?php

namespace Hustlahusky\Migrations;

interface StorageInterface
{
    /**
     * @return list<MigrationInfo>
     */
    public function findInstalled(): array;

    public function install(MigrationInfo $item): void;

    public function rollback(MigrationInfo $item): void;
}
