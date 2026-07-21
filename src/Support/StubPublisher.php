<?php

namespace Corestack\ArchSupport\Support;

use Illuminate\Filesystem\Filesystem;

class StubPublisher
{
    private readonly Filesystem $files;

    public function __construct(private readonly string $stubBasePath)
    {
        $this->files = new Filesystem();
    }

    /**
     * @param array<string, string> $placeholders
     */
    public function publish(string $source, string $target, array $placeholders, bool $force = false): PublishResult
    {
        if ($source === '' || $target === '') {
            return new PublishResult('invalid', $target);
        }

        $sourcePath = $this->stubBasePath . '/' . ltrim($source, '/');
        $targetPath = base_path($target);

        if (! $this->files->exists($sourcePath)) {
            return new PublishResult('invalid', $target);
        }

        if ($this->files->exists($targetPath) && ! $force) {
            return new PublishResult('skipped', $target);
        }

        $content = $this->files->get($sourcePath);
        $rendered = str_replace(array_keys($placeholders), array_values($placeholders), $content);

        $directory = dirname($targetPath);
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $status = $this->files->exists($targetPath) ? 'overwritten' : 'created';
        $this->files->put($targetPath, $rendered);

        return new PublishResult($status, $target);
    }
}
