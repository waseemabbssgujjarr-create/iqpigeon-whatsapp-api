# Phase 1 bootstrap — requires PHP 8.3+ and Composer on PATH
$ErrorActionPreference = "Stop"

$phpVersion = (php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
if ([version]$phpVersion -lt [version]"8.3") {
    Write-Error "PHP 8.3+ required for Laravel 13 (found $phpVersion). Install PHP 8.3 NTS and retry."
}

if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
    Write-Error "Composer not found on PATH."
}

$root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
Set-Location $root

$hasArtisan = Test-Path ".\artisan"
if (-not $hasArtisan) {
    Write-Host "Creating Laravel 13 project in $root ..."
    $temp = Join-Path $env:TEMP "iqp-laravel-scaffold"
    if (Test-Path $temp) { Remove-Item -Recurse -Force $temp }
    composer create-project laravel/laravel:^13.0 $temp --no-interaction
    Get-ChildItem $temp | Move-Item -Destination $root -Force
    Remove-Item -Recurse -Force $temp
}

Write-Host "Installing Breeze (Inertia + React + TypeScript) ..."
composer require laravel/breeze --dev --no-interaction
php artisan breeze:install react --typescript --no-interaction

Write-Host "Installing npm dependencies ..."
npm install
npm run build

if (-not (Test-Path ".env")) {
    Copy-Item .env.example .env
    php artisan key:generate
}

Write-Host "Done. Configure .env (DB, Redis), run migrations when added, then: php artisan serve && npm run dev"
