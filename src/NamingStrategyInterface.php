<?php

declare(strict_types=1);

namespace Hustlahusky\Migrations;

interface NamingStrategyInterface
{
    public function format(\DateTimeImmutable $time): string;

    public function match(string $name): ?\DateTimeImmutable;
}
