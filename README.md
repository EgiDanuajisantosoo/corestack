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
php artisan ttid:arch Product
```

## Default Generated Files (arch:corestack)

- app/Services/ResponseService.php
- app/Http/Controllers/Controller.php
- .gitlab/merge_request_templates/pull-request.md

## Publish Config

```bash
php artisan vendor:publish --tag=arch-support-config
```

## Publish Stubs

```bash
php artisan vendor:publish --tag=arch-support-stubs
```
