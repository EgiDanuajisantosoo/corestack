<?php

namespace Corestack\ArchSupport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ArchMakeCommand extends Command
{
    protected $signature = 'ttid:arch {name} {--force : Overwrite existing files}';

    protected $description = 'Generate entity, table, service, and controller with TTID-like architecture';

    public function handle(): int
    {
        $name = Str::studly((string) $this->argument('name'));
        $table = Str::plural(Str::snake($name));
        $force = (bool) $this->option('force');

        $stubs = [
            ['Entity.stub', app_path("Models/Entity/{$name}.php")],
            ['Table.stub', app_path("Models/Table/{$name}Table.php")],
            ['Service.stub', app_path("Services/{$name}/{$name}Service.php")],
            ['Controller.stub', app_path("Http/Controllers/{$name}/{$name}Controller.php")],
        ];

        foreach ($stubs as [$stubName, $targetPath]) {
            $this->generateFile($stubName, $targetPath, $name, $table, $force);
        }

        $this->info("Semua arsitektur CRUD {$name} berhasil digenerate.");

        return self::SUCCESS;
    }

    private function generateFile(string $stubName, string $targetPath, string $className, string $tableName, bool $force): void
    {
        $stubPath = __DIR__ . '/../../../stubs/crud/' . $stubName;
        if (! File::exists($stubPath)) {
            $this->error("Stub tidak ditemukan: {$stubName}");
            return;
        }

        $alreadyExists = File::exists($targetPath);
        if ($alreadyExists && ! $force) {
            $this->line("<fg=gray>SKIPPED</> {$targetPath}");
            return;
        }

        $stub = File::get($stubPath);
        $rendered = str_replace(
            ['DummyClass', 'DummyTable'],
            [$className, $tableName],
            $stub,
        );

        File::ensureDirectoryExists(dirname($targetPath));
        File::put($targetPath, $rendered);

        $status = $alreadyExists ? 'OVERWRITTEN' : 'CREATED';
        $this->line("<info>{$status}</info> {$targetPath}");
    }
}
