<?php

declare(strict_types=1);

namespace Hustlahusky\Migrations;

interface LockInterface
{
    public function acquireLock(): bool;

    public function releaseLock(): bool;
}
