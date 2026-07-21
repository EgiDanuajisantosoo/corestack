# Panduan Lengkap Membangun Custom Architecture Package (Replika 100% TTID Arch)

Dokumen ini adalah panduan *end-to-end* untuk membangun *package* arsitektur khusus Laravel (misalnya: `namasaya/arch-support`) dari nol. Panduan ini telah diperbarui untuk mencakup **keseluruhan 10 file** yang dihasilkan oleh generator canggih ala TTID.

---

## 1. Inisialisasi Package

Buat folder baru untuk package Anda di luar direktori project Laravel target (misal di `~/Development/packages/arch-support`), lalu inisialisasi:

```bash
mkdir arch-support
cd arch-support
composer init
```

Konfigurasi `composer.json` dari package Anda:

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

Buat struktur folder berikut di dalam package Anda:

```text
arch-support/
├── composer.json
├── src/
│   ├── ArchServiceProvider.php
│   └── Console/
│       ├── Commands/
│       │   ├── ArchInstallCommand.php
│       │   ├── ArchDoctorCommand.php
│       │   └── ArchMakeCommand.php
└── stubs/
    ├── Controller.stub
    ├── ResponseService.stub
    ├── gitlab-mr.stub
    └── crud/
        ├── Entity.stub
        ├── Table.stub
        ├── Service.stub
        ├── ControllerCrud.stub
        ├── Policy.stub
        ├── StoreRequest.stub
        ├── UpdateRequest.stub
        ├── Factory.stub
        ├── Seeder.stub
        └── Migration.stub
```

---

## 3. Mendefinisikan Stubs Dasar (Setup Awal)

Buat file stubs dasar yang berjalan saat project pertama kali diinisialisasi (`arch:ttid`).

<details>
<summary><b>Klik untuk melihat isi ResponseService.stub</b></summary>

```php
<?php
namespace App\Services;
use Illuminate\Pagination\LengthAwarePaginator;

class ResponseService
{
    private $data;
    private $message;
    private $success;

    public function __construct($data = null) { $this->data = $data; }

    public function success($message = null, $responseCode = null) {
        $message = (empty($message)) ? 'success' : $message;
        $this->setMessage($message);
        $this->setResponseCode($responseCode);
        $this->success = true;
        return (object) $this->responseWrapper();
    }

    public function error($message = null, $responseCode = null) {
        $message = (empty($message)) ? 'error' : $message;
        $this->setMessage($message);
        $this->setResponseCode($responseCode);
        $this->success = false;
        return (object) $this->responseWrapper();
    }

    private function responseWrapper() {
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
        } else {
            $response['data'] = $data;
        }
        return $response;
    }

    private function setMessage($message) {
        if (is_array($message)) {
            $extract = array_values($message);
            $this->message = $extract[0];
        } else {
            $this->message = $message;
        }
    }

    private function setResponseCode($responseCode) {
        if (!empty($responseCode) && is_numeric($responseCode)) {
            http_response_code($responseCode);
        }
    }
}
```
</details>

<details>
<summary><b>Klik untuk melihat isi Controller.stub (Base Controller)</b></summary>

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

    public function responseWrapper($data = null) { return new ResponseService($data); }

    protected function sendSuccess($data = null, $message = null, $statusCode = null) {
        $data = $this->responseWrapper($data)->success($message, $statusCode);
        return response()->json($data, $data->code);
    }

    protected function sendError($data = null, $message = null, $statusCode = null) {
        $data = $this->responseWrapper($data)->error($message, $statusCode);
        return response()->json($data, $data->code);
    }
}
```
</details>

<details>
<summary><b>Klik untuk melihat isi AppService.stub</b></summary>

```php
<?php
namespace App\Services;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AppService
{
    protected $model;
    protected $guard = null;
    protected $debug;

    public function __construct(Model $model) {
        $this->model = $model;
        $this->debug = config('app.debug', false);
    }

    public function getUserAuth() { return auth()->user(); }

    protected function sendSuccess($data = null, $message = null, $statusCode = null) {
        return (new ResponseService($data))->success($message, $statusCode);
    }

    protected function sendError($data = null, $message = null, $statusCode = null) {
        return (new ResponseService($data))->error($message, $statusCode);
    }
}
```
</details>

<details>
<summary><b>Klik untuk melihat isi AppServiceInterface.stub</b></summary>

```php
<?php
namespace App\Services;

interface AppServiceInterface
{
    public function dataTable($filter);
    public function getById($id);
    public function create($data);
    public function update($id, $data);
    public function delete($id);
}
```
</details>

<details>
<summary><b>Klik untuk melihat isi AppModel.stub</b></summary>

```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use Exception;

class AppModel extends Model
{
    protected $keyType = 'string';
    protected $keyIsUuid = true;
    protected $uuidVersion = 4;
    protected $guarded = [];
    public $incrementing = false;
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model): void {
            if ($model->keyIsUuid && empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = $model->generateUuid();
            }
        });
    }

    protected function generateUuid(): string
    {
        switch ($this->uuidVersion) {
            case 1: return Uuid::uuid1()->toString();
            case 4: return Uuid::uuid4()->toString();
        }
        throw new Exception("UUID version [{$this->uuidVersion}] not supported.");
    }
}
```
</details>

<details>
<summary><b>Klik untuk melihat isi ApiController.stub</b></summary>

```php
<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
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
</details>

---

## 4. Mendefinisikan Stubs CRUD (10 File Generator)

Ini adalah replika dari generator canggih TTID. Masukkan file-file ini ke dalam folder `stubs/crud/`.

**1. `Entity.stub`**
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
    protected $fillable = ['name'];
}
```

**2. `Table.stub`**
```php
<?php
namespace App\Models\Table;
use App\Models\Entity\{{ class }};

class {{ class }}Table extends {{ class }}
{
    // Scopes dan logika query database spesifik ditaruh disini
}
```

**3. `Service.stub`**
```php
<?php
namespace App\Services\{{ class }};
use App\Models\Table\{{ class }}Table;
use App\Services\AppService;
use App\Services\AppServiceInterface;

class {{ class }}Service extends AppService implements AppServiceInterface
{
    public function __construct({{ class }}Table $model) { parent::__construct($model); }

    public function dataTable($filter) {
        return {{ class }}Table::datatable($filter)->paginate($filter->entries ?? 15);
    }

    public function getById($id) { return {{ class }}Table::findOrFail($id); }

    public function create($data) {
        return {{ class }}Table::create(['name' => $data['name']]);
    }

    public function update($id, $data) {
        $row = {{ class }}Table::findOrFail($id);
        $row->update(['name' => $data['name']]);
        return $row;
    }

    public function delete($id) {
        $row = {{ class }}Table::findOrFail($id);
        $row->delete();
        return $row;
    }
}
```

**4. `ControllerCrud.stub`**
```php
<?php
namespace App\Http\Controllers\{{ class }};
use App\Http\Controllers\ApiController;
use App\Http\Requests\{{ class }}\Store{{ class }}Request;
use App\Http\Requests\{{ class }}\Update{{ class }}Request;
use App\Services\{{ class }}\{{ class }}Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class {{ class }}Controller extends ApiController
{
    protected {{ class }}Service $service;

    public function __construct({{ class }}Service $service, Request $request) {
        $this->service = $service;
        parent::__construct($request);
    }

    public function index(Request $request) {
        $data = $this->service->dataTable($request);
        return $this->sendSuccess($data, null, 200);
    }

    public function store(Store{{ class }}Request $request) {
        $data = $this->service->create($request);
        return $this->sendSuccess($data, null, 201);
    }

    public function show(string $id) {
        $datum = $this->service->getById($id);
        return $this->sendSuccess($datum, null, 200);
    }

    public function update(Update{{ class }}Request $request, string $id) {
        $datum = $this->service->update($id, $request);
        return $this->sendSuccess($datum, null, 200);
    }

    public function destroy(string $id) {
        $datum = $this->service->delete($id);
        return $this->sendSuccess($datum, null, 200);
    }
}
```

**5. `Policy.stub`**
```php
<?php
namespace App\Policies;
use App\Models\Entity\User;
use App\Models\Entity\{{ class }};
use Illuminate\Auth\Access\HandlesAuthorization;

class {{ class }}Policy
{
    use HandlesAuthorization;
    public function viewAny(User $user) { return true; }
    public function view(User $user, {{ class }} $data) { return true; }
    public function create(User $user) { return true; }
    public function update(User $user, {{ class }} $data) { return true; }
    public function delete(User $user, {{ class }} $data) { return true; }
}
```

**6. `StoreRequest.stub` & 7. `UpdateRequest.stub`** *(Isi sama untuk awal)*
```php
<?php
namespace App\Http\Requests\{{ class }};
use Illuminate\Foundation\Http\FormRequest;

class Store{{ class }}Request extends FormRequest
{
    public function authorize() { return true; }
    public function rules() { return ['name' => 'required|string|max:255']; }
}
```
*(Ganti nama class menjadi `Update{{ class }}Request` untuk stub ke-7)*

**8. `Factory.stub` & 9. `Seeder.stub`**
```php
<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;

class {{ class }}Factory extends Factory {
    public function definition() { return []; }
}
```
```php
<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class {{ class }}Seeder extends Seeder {
    public function run() { }
}
```

**10. `Migration.stub`**
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('{{ table }}', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down() {
        Schema::dropIfExists('{{ table }}');
    }
};
```

---

## 5. Membuat Commands di Package

### A. ArchInstallCommand (Setup Awal)
File: `src/Console/Commands/ArchInstallCommand.php`
*(Ubah command ini untuk meng-copy ResponseService, BaseController, AppService, AppModel, dll)*

```php
<?php
namespace NamaSaya\ArchSupport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ArchInstallCommand extends Command
{
    protected $signature = 'arch:ttid';
    protected $description = 'Generate Base Files (Controllers, Services, Models, dll)';

    public function handle()
    {
        $this->info('🚀 Welcome to Architecture Standard Installer');

        $installAll = $this->confirm('Install semua komponen dasar arsitektur?', true);

        if ($installAll || $this->confirm('Install Base Classes?')) {
            $this->generateFile('ResponseService.stub', app_path('Services/ResponseService.php'));
            $this->generateFile('Controller.stub', app_path('Http/Controllers/Controller.php'));
            $this->generateFile('AppService.stub', app_path('Services/AppService.php'));
            $this->generateFile('AppServiceInterface.stub', app_path('Services/AppServiceInterface.php'));
            $this->generateFile('AppModel.stub', app_path('Models/AppModel.php'));
            $this->generateFile('ApiController.stub', app_path('Http/Controllers/ApiController.php'));
            
            $this->info('✅ Base Classes ter-install.');
        }

        if ($installAll || $this->confirm('Install GitLab Merge Request Templates?')) {
            $this->generateFile('gitlab-mr.stub', base_path('.gitlab/merge_request_templates/pull-request.md'));
            $this->info('✅ GitLab Templates ter-install.');
        }
    }

    private function generateFile($stubName, $targetPath)
    {
        $stub = file_get_contents(__DIR__ . '/../../../stubs/' . $stubName);
        File::ensureDirectoryExists(dirname($targetPath));
        if (!File::exists($targetPath)) {
            file_put_contents($targetPath, $stub);
            $this->line("File : {$targetPath} created");
        } else {
            $this->warn("File : {$targetPath} already exists");
        }
    }
}
```

### B. ArchMakeCommand (Generator 10 File)
File: `src/Console/Commands/ArchMakeCommand.php`

```php
<?php
namespace NamaSaya\ArchSupport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ArchMakeCommand extends Command
{
    protected $signature = 'corestack:arch {name}';
    protected $description = 'Make model with architecture from TTID';

    public function handle()
    {
        $name = $this->argument('name');
        $table = Str::plural(Str::snake($name));

        // 1. Models
        $this->generateFile('Entity.stub', app_path("Models/Entity/{$name}.php"), $name, $table);
        $this->generateFile('Table.stub', app_path("Models/Table/{$name}Table.php"), $name, $table);
        
        // 2. Service & Controller
        $this->generateFile('Service.stub', app_path("Services/{$name}/{$name}Service.php"), $name, $table);
        $this->generateFile('ControllerCrud.stub', app_path("Http/Controllers/{$name}/{$name}Controller.php"), $name, $table);
        
        // 3. Security & Validation
        $this->generateFile('Policy.stub', app_path("Policies/{$name}Policy.php"), $name, $table);
        $this->generateFile('StoreRequest.stub', app_path("Http/Requests/{$name}/Store{$name}Request.php"), $name, $table);
        $this->generateFile('UpdateRequest.stub', app_path("Http/Requests/{$name}/Update{$name}Request.php"), $name, $table);
        
        // 4. Database & Testing
        $this->generateFile('Factory.stub', base_path("database/factories/{$name}Factory.php"), $name, $table);
        $this->generateFile('Seeder.stub', base_path("database/seeders/{$name}Seeder.php"), $name, $table);
        
        $datePrefix = date('Y_m_d_His');
        $this->generateFile('Migration.stub', base_path("database/migrations/{$datePrefix}_create_{$table}_table.php"), $name, $table);

        $this->info("✅ 10 File Arsitektur CRUD {$name} berhasil digenerate!");
    }

    private function generateFile($stubName, $targetPath, $className, $tableName)
    {
        $stub = file_get_contents(__DIR__ . '/../../../stubs/crud/' . $stubName);
        $stub = str_replace(['{{ class }}', '{{ table }}'], [$className, $tableName], $stub);
        
        File::ensureDirectoryExists(dirname($targetPath));
        if (!File::exists($targetPath)) {
            file_put_contents($targetPath, $stub);
            $this->line("File : {$targetPath} created");
        } else {
            $this->warn("File : {$targetPath} already exists");
        }
    }
}
```

---

## 6. Mendaftarkan Service Provider

File: `src/ArchServiceProvider.php`

```php
<?php
namespace NamaSaya\ArchSupport;

use Illuminate\Support\ServiceProvider;
use NamaSaya\ArchSupport\Console\Commands\ArchInstallCommand;
use NamaSaya\ArchSupport\Console\Commands\ArchDoctorCommand;
use NamaSaya\ArchSupport\Console\Commands\ArchMakeCommand;

class ArchServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ArchInstallCommand::class,
                ArchDoctorCommand::class,
                ArchMakeCommand::class, // Daftarkan Generator disini
            ]);
        }
    }
}
```

---

Sekarang *Package* ini 100% mereplikasi arsitektur yang Anda butuhkan. Di project mana pun, Anda tinggal menggunakan `php artisan corestack:arch Produk` dan semuanya akan tercipta sempurna!
