<?php

declare(strict_types=1);

namespace Hustlahusky\Migrations;

interface MigrationInterface
{
    public function up(): void;

    public function down(): void;
}
