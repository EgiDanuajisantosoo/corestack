# Panduan Lengkap Membangun Custom Architecture Package (Replika TTID Arch)

Dokumen ini adalah panduan *end-to-end* untuk membangun *package* arsitektur khusus Laravel (misalnya: `namasaya/arch-support`) dari nol. Package ini dirancang berdasarkan pola implementasi (seperti *ResponseService* dan *BaseController*) yang ada pada proyek *backpanel-e-taxi*.

---

## 1. Inisialisasi Package

Buat folder baru untuk package Anda di luar direktori project Laravel target (misal di `~/Development/packages/arch-support`), lalu inisialisasi menggunakan Composer.

```bash
mkdir arch-support
cd arch-support
composer init
```

Pastikan file `composer.json` dari package Anda terlihat seperti ini:

```json
{
    "name": "namasaya/arch-support",
    "description": "Standard Architecture Support Package",
    "type": "library",
    "require": {
        "php": "^8.1",
        "illuminate/support": "^10.0|^11.0",
        "illuminate/console": "^10.0|^11.0"
    },
    "autoload": {
        "psr-4": {
            "NamaSaya\\ArchSupport\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "NamaSaya\\ArchSupport\\ArchServiceProvider"
            ]
        }
    }
}
```

---

## 2. Struktur Direktori Package

Buat folder dan file dasar sehingga struktur package Anda terlihat seperti ini:

```text
arch-support/
├── composer.json
├── src/
│   ├── ArchServiceProvider.php
│   └── Console/
│       ├── Commands/
│       │   ├── ArchInstallCommand.php
│       │   └── ArchDoctorCommand.php
└── stubs/
    ├── Controller.stub
    ├── ResponseService.stub
    └── gitlab-mr.stub
```

---

## 3. Mendefinisikan Stubs (Template Dasar)

Isi file-file `.stub` dengan *boilerplate* yang diambil persis dari aplikasi *backpanel-e-taxi*.

### A. `stubs/ResponseService.stub`
```php
<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;

class ResponseService
{
    private $data;
    private $message;
    private $success;

    public function __construct($data = null)
    {
        $this->data = $data;
    }

    public function success($message = null, $responseCode = null)
    {
        $message = (empty($message)) ? 'success' : $message;
        $this->setMessage($message);
        $this->setResponseCode($responseCode);
        $this->success = true;

        return (object) $this->responseWrapper();
    }

    public function error($message = null, $responseCode = null)
    {
        $message = (empty($message)) ? 'error' : $message;
        $this->setMessage($message);
        $this->setResponseCode($responseCode);
        $this->success = false;

        return (object) $this->responseWrapper();
    }

    private function responseWrapper()
    {
        $data = (empty($this->data)) ? null : $this->data;

        $response = [
            'code'      => http_response_code(),
            'success'   => $this->success,
            'message'   => $this->message,
        ];
        
        if ($data instanceof LengthAwarePaginator) {
            $data = $data->toArray();
            $response['data'] = $data['data'];
            $response['meta']['current_page'] = $data['current_page'];
            $response['meta']['total'] = $data['total'];
            // Tambahkan field pagination meta lainnya sesuai kebutuhan
        } else {
            $response['data'] = $data;
        }

        return $response;
    }

    private function setMessage($message)
    {
        if (is_array($message)) {
            $extract = array_values($message);
            $this->message = $extract[0];
        } else {
            $this->message = $message;
        }
    }

    private function setResponseCode($responseCode)
    {
        if (!empty($responseCode) && is_numeric($responseCode)) {
            http_response_code($responseCode);
        }
    }
}
```

### B. `stubs/Controller.stub`
```php
<?php

namespace App\Http\Controllers;

use App\Services\ResponseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function responseWrapper($data = null)
    {
        return new ResponseService($data);
    }

    protected function sendSuccess($data = null, $message = null, $statusCode = null)
    {
        $data = $this->responseWrapper($data)->success($message, $statusCode);
        return response()->json($data, $data->code);
    }

    protected function sendError($data = null, $message = null, $statusCode = null)
    {
        $data = $this->responseWrapper($data)->error($message, $statusCode);
        return response()->json($data, $data->code);
    }
}
```

### C. `stubs/gitlab-mr.stub`
Isi dengan standard *Pull Request Markdown*.
```markdown
## Description
<!--- Describe your changes in detail -->

## Related Links
<!--- Please link to the issue here. -->

## Type of change
- [ ] Fix bugs in test / code
- [ ] New feature
- [ ] Docs
- [ ] Refactor
- [ ] Other: please elaborate

## Risks
- [ ] **low**: new features or patches which does not break existing functionality
- [ ] **medium**: existing functionality in our domain is changed
- [ ] **high**: breaking contracts with other domain
```

---

## 4. Membuat Commands

### A. Install Command (Interactive Mode)
File: `src/Console/Commands/ArchInstallCommand.php`

```php
<?php

namespace NamaSaya\ArchSupport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ArchInstallCommand extends Command
{
    protected $signature = 'arch:corestack';
    protected $description = 'Generate File Arsitektur Penting (Controller, Service, GitLab Template)';

    public function handle()
    {
        $this->info('🚀 Welcome to Architecture Standard Installer');

        $installAll = $this->confirm('Install semua komponen arsitektur secara otomatis?', true);

        // 1. Controller & ResponseService
        if ($installAll || $this->confirm('Install Base Controller & ResponseService?')) {
            $this->generateFile('/../../../stubs/ResponseService.stub', app_path('Services/ResponseService.php'));
            $this->generateFile('/../../../stubs/Controller.stub', app_path('Http/Controllers/Controller.php'));
            $this->info('✅ Core Controllers ter-install.');
        }

        // 2. GitLab Template
        if ($installAll || $this->confirm('Install GitLab Merge Request Templates?')) {
            $this->generateFile('/../../../stubs/gitlab-mr.stub', base_path('.gitlab/merge_request_templates/pull-request.md'));
            $this->info('✅ GitLab Templates ter-install.');
        }

        $this->info('🎉 Proses instalasi arsitektur selesai!');
    }

    private function generateFile($stubPath, $targetPath)
    {
        $stub = file_get_contents(__DIR__ . $stubPath);
        File::ensureDirectoryExists(dirname($targetPath));
        file_put_contents($targetPath, $stub);
    }
}
```

### B. Doctor Command (Pengecekan Kepatuhan)
File: `src/Console/Commands/ArchDoctorCommand.php`

```php
<?php

namespace NamaSaya\ArchSupport\Console\Commands;

use Illuminate\Console\Command;

class ArchDoctorCommand extends Command
{
    protected $signature = 'arch:doctor';
    protected $description = 'Periksa kepatuhan kode terhadap standar arsitektur';

    public function handle()
    {
        $this->info('🩺 Menjalankan Pengecekan Arsitektur...');
        $errors = 0;

        // 1. Cek ResponseService
        if (!file_exists(app_path('Services/ResponseService.php'))) {
            $this->error('❌ [MISSING] ResponseService.php tidak ditemukan.');
            $errors++;
        } else {
            $this->line('✅ ResponseService ditemukan.');
        }

        // 2. Cek Aturan Base Controller
        $controllerPath = app_path('Http/Controllers/Controller.php');
        if (file_exists($controllerPath)) {
            $content = file_get_contents($controllerPath);
            if (!str_contains($content, 'function sendSuccess')) {
                $this->error('❌ [VIOLATION] Controller.php tidak memiliki method sendSuccess() wajib.');
                $errors++;
            } else {
                $this->line('✅ Base Controller valid.');
            }
        } else {
            $this->error('❌ [MISSING] Controller.php utama hilang.');
            $errors++;
        }

        if ($errors === 0) {
            $this->info('🎉 SELAMAT! Proyek 100% mematuhi standar arsitektur.');
        } else {
            $this->warn("⚠️ Ditemukan {$errors} masalah arsitektur. Jalankan 'php artisan arch:corestack' untuk memperbaiki.");
        }
    }
}
```

---

## 5. Mendaftarkan Service Provider

File: `src/ArchServiceProvider.php`

```php
<?php

namespace NamaSaya\ArchSupport;

use Illuminate\Support\ServiceProvider;
use NamaSaya\ArchSupport\Console\Commands\ArchInstallCommand;
use NamaSaya\ArchSupport\Console\Commands\ArchDoctorCommand;

class ArchServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ArchInstallCommand::class,
                ArchDoctorCommand::class,
            ]);
        }
    }

    public function register()
    {
        //
    }
}
```

---

## 6. Cara Implementasi di Project Lain (Testing Lokal)

Jika Anda ingin mencoba mengimplementasikan package ini ke project Laravel kosong tanpa harus menguploadnya ke internet, lakukan langkah berikut:

1. Pergi ke *root* project Laravel target.
2. Tambahkan block repository `path` di `composer.json` (Project Target):
```json
"repositories": [
    {
        "type": "path",
        "url": "../arch-support"
    }
]
```
*(Sesuaikan `url` dengan path aktual di komputer Anda)*
3. Jalankan `composer require namasaya/arch-support`
4. Uji command interaktif: **`php artisan arch:corestack`**
5. Uji kepatuhan: **`php artisan arch:doctor`**

---

## 7. Membuat Generator CRUD (Ttid Arch Make)

Selain *Base Architecture*, TTID Architecture memisahkan konsep Model menjadi `Entity` dan `Table`, serta menggunakan pola Service-Controller. 
Berikut cara membuat command seperti `php artisan ttid:arch {name}`.

### A. Buat Folder Stubs untuk CRUD
Di dalam folder `stubs/`, buat folder baru `crud/` dan isi dengan template berikut:

**1. `stubs/crud/Entity.stub`**
```php
<?php
namespace App\Models\Entity;
use App\Models\AppModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class {{ class }} extends AppModel
{
    use HasFactory, SoftDeletes;
    protected $table = '{{ table }}';
    protected $fillable = [];
}
```

**2. `stubs/crud/Table.stub`**
```php
<?php
namespace App\Models\Table;
use App\Models\Entity\{{ class }};

class {{ class }}Table extends {{ class }}
{
    //
}
```

**3. `stubs/crud/Service.stub`**
```php
<?php
namespace App\Services\{{ class }};
use App\Models\Table\{{ class }}Table;
use App\Services\AppService;
use App\Services\AppServiceInterface;

class {{ class }}Service extends AppService implements AppServiceInterface
{
    public function __construct({{ class }}Table $model)
    {
        parent::__construct($model);
    }

    public function dataTable($filter)
    {
        return {{ class }}Table::datatable($filter)->paginate($filter->entries ?? 15);
    }
}
```

**4. `stubs/crud/Controller.stub`**
```php
<?php
namespace App\Http\Controllers\{{ class }};
use App\Http\Controllers\ApiController;
use App\Services\{{ class }}\{{ class }}Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class {{ class }}Controller extends ApiController
{
    protected {{ class }}Service $service;

    public function __construct({{ class }}Service $service, Request $request)
    {
        $this->service = $service;
        parent::__construct($request);
    }
}
```

### B. Buat Command Generator
File: `src/Console/Commands/ArchMakeCommand.php`

```php
<?php
namespace NamaSaya\ArchSupport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ArchMakeCommand extends Command
{
    protected $signature = 'ttid:arch {name}';
    protected $description = 'Make model with architecture from Transtrack';

    public function handle()
    {
        $name = $this->argument('name'); // e.g. Product
        $table = Str::plural(Str::snake($name)); // e.g. products

        // 1. Generate Entity
        $this->generateFile('Entity.stub', app_path("Models/Entity/{$name}.php"), $name, $table);
        
        // 2. Generate Table
        $this->generateFile('Table.stub', app_path("Models/Table/{$name}Table.php"), $name, $table);
        
        // 3. Generate Service
        $this->generateFile('Service.stub', app_path("Services/{$name}/{$name}Service.php"), $name, $table);
        
        // 4. Generate Controller
        $this->generateFile('Controller.stub', app_path("Http/Controllers/{$name}/{$name}Controller.php"), $name, $table);

        $this->info("✅ Seluruh arsitektur CRUD {$name} berhasil digenerate!");
    }

    private function generateFile($stubName, $targetPath, $className, $tableName)
    {
        $stub = file_get_contents(__DIR__ . '/../../../stubs/crud/' . $stubName);
        $stub = str_replace(['{{ class }}', '{{ table }}'], [$className, $tableName], $stub);
        
        File::ensureDirectoryExists(dirname($targetPath));
        file_put_contents($targetPath, $stub);
        
        $this->line("File : {$targetPath} created");
    }
}
```
*Jangan lupa daftarkan `ArchMakeCommand::class` ke dalam `ArchServiceProvider.php` (di dalam array `$this->commands([])`).*
