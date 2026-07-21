# Corestack Architecture Validation

Dokumen ini menjadi acuan untuk memastikan package `corestack/arch-support` konsisten, bisa dipakai ulang, dan aman diterapkan ke project Laravel lain.

## 1. Naming Standard

- Package name: `corestack/arch-support`
- Root namespace package: `Corestack\\ArchSupport`
- Artisan bootstrap command: `arch:install`
- Prefix command turunan: `arch:*`

## 2. Architecture Scope

`arch:install` wajib menghasilkan struktur awal berikut di project target:

- `app/Support/ApiResponse.php`
- `app/Traits/HasApiResponse.php`
- `app/Repositories/BaseRepository.php`
- `app/Services/BaseService.php`
- `.gitlab/merge_request_templates/pull-request.md`

Semua file di atas dibuat dari stub package dengan placeholder dinamis (contoh `{{ namespace }}`, `{{ year }}`).

## 3. Rules

- Idempotent by default: file yang sudah ada tidak ditimpa.
- Overwrite hanya jika pakai `--force`.
- File harus bisa dibuat lintas OS path separator.
- Namespace file yang digenerate wajib mengikuti namespace aplikasi target (`app()->getNamespace()`).

## 4. Distribution Rules

- Package harus punya Laravel auto-discovery service provider.
- Config package harus bisa dipublish (`vendor:publish`).
- Stub package harus bisa dipublish agar bisa dikustomisasi di tiap project.

## 5. Minimum Acceptance Criteria

Sebuah release dianggap valid jika:

1. `composer require corestack/arch-support` sukses di project Laravel target.
2. `php artisan arch:install` terdaftar dan bisa dijalankan.
3. Semua file default pada poin 2 tergenerate.
4. Menjalankan command kedua kalinya tanpa `--force` tidak mengubah file existing.
5. Menjalankan command dengan `--force` menimpa file existing.

## 6. Implementation Checklist

1. `composer.json` memiliki `extra.laravel.providers`.
2. Service provider mendaftarkan command.
3. Command membaca daftar template dari config.
4. Command melakukan placeholder replacement.
5. Command menampilkan ringkasan file dibuat / diskip / ditimpa.
6. README berisi langkah install dan penggunaan.
