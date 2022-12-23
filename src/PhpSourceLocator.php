<?php

declare(strict_types=1);

namespace Hustlahusky\Migrations;

final class PhpSourceLocator implements SourceLocatorInterface
{
    private string $directory;
    private NamingStrategyInterface $namingStrategy;
    private string $hashAlgorithm;

    public function __construct(
        string $directory,
        NamingStrategyInterface $namingStrategy,
        string $hashAlgorithm = 'sha512'
    ) {
        $this->directory = \rtrim($directory, '/\\');
        $this->namingStrategy = $namingStrategy;
        $this->hashAlgorithm = $hashAlgorithm;
    }

    public function getMigration(MigrationInfo $info): MigrationInterface
    {
        $filename = \sprintf('%s/%s.php', $this->directory, $info->getName());

        $result = include $filename;

        if (\is_callable($result)) {
            $result = $result();
        }

        if ($result instanceof MigrationInterface) {
            return $result;
        }

        throw new \RuntimeException('migration not found');
    }

    public function registerMigration(?\DateTimeImmutable $time = null, string $content = ''): void
    {
        $filename = \sprintf(
            '%s/%s.php',
            $this->directory,
            $this->namingStrategy->format($time ?? new \DateTimeImmutable()),
        );

        if (\file_exists($filename)) {
            throw new \RuntimeException('migration already exists');
        }

        if (
            !\is_dir($this->directory) &&
            !\mkdir($this->directory, 0777, true) &&
            !\is_dir($this->directory)
        ) {
            throw new \RuntimeException(\sprintf('Migrations directory "%s" cannot be created.', $this->directory));
        }

        $handle = \fopen($filename, 'wb');
        if (false === $handle) {
            throw new \RuntimeException('migration cannot be created.');
        }

        \fwrite($handle, $content);
        \fclose($handle);
    }

    public function findSources(): array
    {
        $iterator = new \FilesystemIterator($this->directory);

        $list = [];

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if ('php' !== $file->getExtension()) {
                continue;
            }

            $name = $file->getBasename('.php');
            if ('' === $name) {
                continue;
            }

            $createdAt = $this->namingStrategy->match($name);
            if (null === $createdAt) {
                continue;
            }

            $hash = \hash_file($this->hashAlgorithm, $file->getPathname());

            $list[] = MigrationInfo::fromSource($name, $createdAt, \is_string($hash) ? $hash : '');
        }

        return $list;
    }
}
