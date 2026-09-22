# One-time / refresh local setup for iqpigeon-whatsapp-api
$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

if (-not (Test-Path "$Root\.tools\php83\php.exe")) {
    Write-Host "Installing PHP 8.3 to .tools/php83..."
    & "$Root\scripts\install-php83.ps1"
}

$php = "$Root\.tools\php83\php.exe"
$composer = "$Root\.tools\composer.phar"

if (-not (Test-Path $composer)) {
    Invoke-WebRequest "https://getcomposer.org/download/latest-stable/composer.phar" -OutFile $composer
}

& $php $composer install --no-interaction
npm install

if (-not (Test-Path "$Root\.env")) {
    Copy-Item "$Root\.env.example" "$Root\.env"
    & $php artisan key:generate
}

if (Test-Path "$Root\docker-compose.yml") {
    docker compose up -d
}

& $php artisan migrate --seed --force
Write-Host "Setup complete. Run .\scripts\dev.ps1"
