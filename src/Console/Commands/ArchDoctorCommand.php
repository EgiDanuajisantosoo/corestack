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

        if (! File::exists(app_path('Services/ResponseService.php'))) {
            $this->error('[MISSING] ResponseService.php tidak ditemukan.');
            $errors++;
        } else {
            $this->line('OK: ResponseService ditemukan.');
        }

        $controllerPath = app_path('Http/Controllers/Controller.php');
        if (! File::exists($controllerPath)) {
            $this->error('[MISSING] Controller.php utama hilang.');
            $errors++;
        } else {
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

            if ($errors === 0) {
                $this->line('OK: Base Controller valid.');
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
