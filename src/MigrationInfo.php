<?php

declare(strict_types=1);

namespace Hustlahusky\Migrations;

/**
 * @phpstan-import-type StatusEnum from Status
 */
final class MigrationInfo
{
    /**
     * @var non-empty-string
     */
    private string $name;

    /**
     * @var StatusEnum
     */
    private int $status;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $installedAt;
    private bool $sourceNotFound;
    private string $sourceHash;
    private string $installedHash;

    /**
     * @param non-empty-string $name
     * @param StatusEnum $status
     */
    private function __construct(
        string $name,
        \DateTimeImmutable $createdAt,
        int $status = Status::NEW,
        ?\DateTimeImmutable $installedAt = null,
        bool $sourceNotFound = false,
        string $sourceHash = '',
        string $installedHash = ''
    ) {
        $this->name = $name;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->installedAt = $installedAt;
        $this->sourceNotFound = $sourceNotFound;
        $this->sourceHash = $sourceHash;
        $this->installedHash = $installedHash;
    }

    /**
     * @param non-empty-string $name
     */
    public static function fromSource(
        string $name,
        \DateTimeImmutable $createdAt,
        string $sourceHash = ''
    ): self {
        return new self($name, $createdAt, Status::NEW, null, false, $sourceHash, '');
    }

    /**
     * @param non-empty-string $name
     */
    public static function fromStorage(
        string $name,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $installedAt,
        string $installedHash = ''
    ): self {
        return new self($name, $createdAt, Status::INSTALLED, $installedAt, true, '', $installedHash);
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return StatusEnum
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getInstalledAt(): ?\DateTimeImmutable
    {
        return $this->installedAt;
    }

    public function sourceNotFound(): bool
    {
        return $this->sourceNotFound;
    }

    public function sourceModified(): bool
    {
        return $this->status === Status::INSTALLED
            && !$this->sourceNotFound
            && $this->installedHash !== $this->sourceHash;
    }

    public function getSourceHash(): string
    {
        return $this->sourceHash;
    }

    public function setSource(self $source): void
    {
        $this->sourceNotFound = false;
        $this->sourceHash = $source->sourceHash;
    }

    public function markNew(): void
    {
        $this->status = Status::NEW;
        $this->installedAt = null;
        $this->installedHash = '';
    }

    public function markSkipped(): void
    {
        $this->status = Status::SKIPPED;
        $this->installedAt = null;
        $this->installedHash = '';
    }

    public function markInstalled(): void
    {
        $this->status = Status::INSTALLED;
        $this->installedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->installedHash = $this->sourceHash;
    }
}
