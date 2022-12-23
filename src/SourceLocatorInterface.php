<?php

namespace Hustlahusky\Migrations;

interface SourceLocatorInterface
{
    public function getMigration(MigrationInfo $info): MigrationInterface;

    public function registerMigration(?\DateTimeImmutable $time = null, string $content = ''): void;

    /**
     * @return list<MigrationInfo>
     */
    public function findSources(): array;
}
