<?php

namespace Corestack\ArchSupport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ArchDoctorCommand extends Command
{
    protected $signature = 'arch:doctor';

    protected $description = 'Periksa kepatuhan kode terhadap standar arsitektur';

    public function handle(): int
    {
        $this->info('Menjalankan pengecekan arsitektur...');

        $errors = 0;

        $requiredFiles = [
            app_path('Services/ResponseService.php') => 'ResponseService.php',
            app_path('Http/Controllers/Controller.php') => 'Controller.php',
            app_path('Services/AppService.php') => 'AppService.php',
            app_path('Services/AppServiceInterface.php') => 'AppServiceInterface.php',
            app_path('Models/AppModel.php') => 'AppModel.php',
            app_path('Http/Controllers/ApiController.php') => 'ApiController.php',
        ];

        foreach ($requiredFiles as $path => $label) {
            if (! File::exists($path)) {
                $this->error("[MISSING] {$label} tidak ditemukan.");
                $errors++;
                continue;
            }

            $this->line("OK: {$label} ditemukan.");
        }

        $controllerPath = app_path('Http/Controllers/Controller.php');
        if (File::exists($controllerPath)) {
            $content = File::get($controllerPath);

            if (! str_contains($content, 'function sendSuccess')) {
                $this->error('[VIOLATION] Controller.php tidak memiliki method sendSuccess() wajib.');
                $errors++;
            }

            if (! str_contains($content, 'function sendError')) {
                $this->error('[VIOLATION] Controller.php tidak memiliki method sendError() wajib.');
                $errors++;
            }

            if (! str_contains($content, 'ResponseService')) {
                $this->error('[VIOLATION] Controller.php tidak menggunakan ResponseService.');
                $errors++;
            }
        }

        if ($errors === 0) {
            $this->info('SELAMAT! Proyek mematuhi standar arsitektur.');
            return self::SUCCESS;
        }

        $this->warn("Ditemukan {$errors} masalah arsitektur. Jalankan 'php artisan arch:corestack' untuk memperbaiki.");

        return self::FAILURE;
    }
}
