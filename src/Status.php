<?php

declare(strict_types=1);

namespace Hustlahusky\Migrations;

/**
 * @phpstan-type StatusEnum = Status::*
 */
final class Status
{
    /**
     * Applies to the migration that was not installed, but there exists the installed one after creation this.
     */
    public const SKIPPED = -1;

    /**
     * Applies to the migration that awaits for installation.
     */
    public const NEW = 0;

    /**
     * Applies to the migration that was installed.
     */
    public const INSTALLED = 1;
}
