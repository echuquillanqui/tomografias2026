<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseBackupService
{
    public function create(): string
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException("El motor de base de datos {$driver} aún no admite respaldos.");
        }

        $path = tempnam(sys_get_temp_dir(), 'tomografias-backup-');
        if ($path === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal del respaldo.');
        }

        $file = fopen($path, 'wb');
        if ($file === false) {
            throw new RuntimeException('No se pudo abrir el archivo temporal del respaldo.');
        }

        fwrite($file, "-- Respaldo generado por Tomografías\n-- ".now()->toDateTimeString()."\n\n");
        fwrite($file, $driver === 'mysql' ? "SET FOREIGN_KEY_CHECKS=0;\n\n" : "PRAGMA foreign_keys=OFF;\n\n");

        foreach ($this->tables($driver) as $table) {
            $quotedTable = $this->quoteIdentifier($table, $driver);
            $createSql = $this->createStatement($table, $driver);
            fwrite($file, "DROP TABLE IF EXISTS {$quotedTable};\n{$createSql};\n\n");

            foreach ($connection->table($table)->orderBy($connection->getSchemaBuilder()->getColumnListing($table)[0])->cursor() as $row) {
                $values = array_map(fn ($value) => $this->quoteValue($value), array_values((array) $row));
                fwrite($file, "INSERT INTO {$quotedTable} VALUES (".implode(', ', $values).");\n");
            }
            fwrite($file, "\n");
        }

        if ($driver === 'sqlite') {
            $objects = DB::select("SELECT sql FROM sqlite_master WHERE type IN ('index', 'trigger') AND sql IS NOT NULL ORDER BY type, name");
            foreach ($objects as $object) {
                fwrite($file, $object->sql.";\n");
            }
        }

        fwrite($file, $driver === 'mysql' ? "SET FOREIGN_KEY_CHECKS=1;\n" : "PRAGMA foreign_keys=ON;\n");
        fclose($file);

        return $path;
    }

    private function tables(string $driver): array
    {
        if ($driver === 'mysql') {
            return array_map(fn ($row) => array_values((array) $row)[0], DB::select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']));
        }

        return array_column(DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"), 'name');
    }

    private function createStatement(string $table, string $driver): string
    {
        if ($driver === 'mysql') {
            $row = (array) DB::selectOne('SHOW CREATE TABLE '.$this->quoteIdentifier($table, $driver));
            return array_values($row)[1];
        }

        return DB::table('sqlite_master')->where('type', 'table')->where('name', $table)->value('sql');
    }

    private function quoteIdentifier(string $identifier, string $driver): string
    {
        $quote = $driver === 'mysql' ? '`' : '"';
        return $quote.str_replace($quote, $quote.$quote, $identifier).$quote;
    }

    private function quoteValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return DB::connection()->getPdo()->quote((string) $value);
    }
}
