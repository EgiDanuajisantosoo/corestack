<?php

namespace Corestack\ArchSupport\Console\Commands;

use Corestack\ArchSupport\Support\StubPublisher;
use Illuminate\Console\Command;

class ArchInstallCommand extends Command
{
    protected $signature = 'arch:corestack {--force : Overwrite existing files} {--all : Install all components without prompts}';

    protected $description = 'Generate architecture files (Controller, ResponseService, and GitLab template)';

    public function __construct(private readonly StubPublisher $publisher)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Welcome to Corestack Architecture Standard Installer');

        $force = (bool) $this->option('force');
        $installAll = (bool) $this->option('all');

        if (! $installAll) {
            $installAll = $this->confirm('Install semua komponen arsitektur secara otomatis?', true);
        }

        $created = 0;
        $overwritten = 0;
        $skipped = 0;

        if ($installAll || $this->confirm('Install Base Controller dan ResponseService?', true)) {
            [$created, $overwritten, $skipped] = $this->publishSet(
                config('arch-support.install_sets.core', []),
                $force,
                $created,
                $overwritten,
                $skipped,
            );
            $this->info('Core controller and response service installed.');
        }

        if ($installAll || $this->confirm('Install GitLab Merge Request Templates?', true)) {
            [$created, $overwritten, $skipped] = $this->publishSet(
                config('arch-support.install_sets.gitlab', []),
                $force,
                $created,
                $overwritten,
                $skipped,
            );
            $this->info('GitLab merge request template installed.');
        }

        $this->newLine();
        $this->info("Done. created={$created}, overwritten={$overwritten}, skipped={$skipped}");

        return self::SUCCESS;
    }

    /**
     * @param array<int, array{source:string,target:string}> $templates
     * @return array{0:int,1:int,2:int}
     */
    private function publishSet(array $templates, bool $force, int $created, int $overwritten, int $skipped): array
    {
        foreach ($templates as $template) {
            $result = $this->publisher->publish(
                source: (string) ($template['source'] ?? ''),
                target: (string) ($template['target'] ?? ''),
                placeholders: [],
                force: $force,
            );

            if ($result->status === 'created') {
                $created++;
                $this->line("<info>CREATED</info> {$result->target}");
                continue;
            }

            if ($result->status === 'overwritten') {
                $overwritten++;
                $this->line("<comment>OVERWRITTEN</comment> {$result->target}");
                continue;
            }

            if ($result->status === 'skipped') {
                $skipped++;
                $this->line("<fg=gray>SKIPPED</> {$result->target}");
                continue;
            }

            $this->warn("INVALID: {$result->target}");
        }

        return [$created, $overwritten, $skipped];
    }
}
