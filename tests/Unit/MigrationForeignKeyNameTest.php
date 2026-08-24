<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class MigrationForeignKeyNameTest extends TestCase
{
    private const MYSQL_IDENTIFIER_LIMIT = 64;

    #[DataProvider('applicationMigrationProvider')]
    public function test_application_foreign_keys_have_explicit_short_names(string $migration): void
    {
        $contents = file_get_contents($migration);

        preg_match_all('/->foreignId\([^;]+?->constrained\([^;]+?;/', $contents, $foreignIds);

        foreach ($foreignIds[0] as $definition) {
            $this->assertStringContainsString(
                'indexName:',
                $definition,
                basename($migration).' contains a foreign key without an explicit name.'
            );
        }

        preg_match_all("/(?:indexName:\\s*|->foreign\\([^,]+,\\s*)['\"]([^'\"]+)['\"]/", $contents, $names);

        foreach ($names[1] as $name) {
            $this->assertLessThanOrEqual(
                self::MYSQL_IDENTIFIER_LIMIT,
                strlen($name),
                sprintf('The foreign key name "%s" exceeds MySQL\'s identifier limit.', $name)
            );
        }
    }

    public static function applicationMigrationProvider(): iterable
    {
        $directory = dirname(__DIR__, 2).'/database/migrations';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            // Spatie's published migration builds its constraint names from configurable
            // table and column names, so only audit application-owned definitions here.
            if (str_contains($contents, "config('permission.")) {
                continue;
            }

            yield $file->getFilename() => [$file->getPathname()];
        }
    }
}
