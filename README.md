# Corestack Arch Support

Reusable Laravel architecture package to bootstrap common project structure.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Install

```bash
composer require corestack/arch-support
```

## Usage

Install base architecture files in the current Laravel project (interactive mode):

```bash
php artisan arch:corestack
```

Install all without prompt:

```bash
php artisan arch:corestack --all
```

Overwrite existing generated files:

```bash
php artisan arch:corestack --force
```

Run architecture compliance checks:

```bash
php artisan arch:doctor
```

Generate CRUD architecture structure:

```bash
php artisan corestack:arch Produk
```

## Default Generated Files (arch:corestack)

- app/Services/ResponseService.php
- app/Http/Controllers/Controller.php
- app/Services/AppService.php
- app/Services/AppServiceInterface.php
- app/Models/AppModel.php
- app/Http/Controllers/ApiController.php
- .gitlab/merge_request_templates/pull-request.md

## CRUD Generator Output (corestack:arch)

- app/Models/Entity/Produk.php
- app/Models/Table/ProdukTable.php
- app/Services/Produk/ProdukService.php
- app/Http/Controllers/Produk/ProdukController.php
- app/Policies/ProdukPolicy.php
- app/Http/Requests/Produk/StoreProdukRequest.php
- app/Http/Requests/Produk/UpdateProdukRequest.php
- database/factories/ProdukFactory.php
- database/seeders/ProdukSeeder.php
- database/migrations/*_create_produks_table.php

## Publish Config

```bash
php artisan vendor:publish --tag=arch-support-config
```

## Publish Stubs

```bash
php artisan vendor:publish --tag=arch-support-stubs
```
