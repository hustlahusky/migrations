<?php

declare(strict_types=1);

namespace Hustlahusky\Migrations;

/**
 * @param callable():bool|null $retry
 * @return bool
 */
function lock(LockInterface $lock, callable $operation, ?callable $retry = null): bool
{
    $retry ??= static fn () => false;

    while (true) {
        if ($lock->acquireLock()) {
            try {
                $operation();
            } finally {
                $lock->releaseLock();
            }
            return true;
        }

        if (!\filter_var($retry(), \FILTER_VALIDATE_BOOLEAN)) {
            break;
        }
    }

    return false;
}
