<?php

declare(strict_types=1);

namespace Hustlahusky\Migrations;

final class DefaultNamingStrategy implements NamingStrategyInterface
{
    private const FORMAT = 'YmdHis';
    private const PREFIX = 'm_';
    private const REGEX = '#^m_(?<timestamp>\d{14})$#';

    public function format(\DateTimeImmutable $time): string
    {
        return self::PREFIX . $time->setTimezone(new \DateTimeZone('UTC'))->format(self::FORMAT);
    }

    public function match(string $name): ?\DateTimeImmutable
    {
        $matches = [];
        \preg_match(self::REGEX, $name, $matches);

        if (!isset($matches['timestamp'])) {
            return null;
        }

        $out = \DateTimeImmutable::createFromFormat(self::FORMAT, $matches['timestamp'], new \DateTimeZone('UTC'));
        return $out instanceof \DateTimeImmutable ? $out : null;
    }
}
