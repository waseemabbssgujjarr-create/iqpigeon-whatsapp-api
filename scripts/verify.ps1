$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

& "$Root\tools\php.ps1" -v
& "$Root\tools\composer.ps1" -V
node -v
npm -v

& "$Root\tools\php.ps1" artisan migrate --force
& "$Root\tools\php.ps1" artisan test
npm run build

Write-Host "Verify complete."
