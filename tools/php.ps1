# Project-local PHP 8.3 for Laravel 13 (does not change system PHP)
$Root = Split-Path -Parent $PSScriptRoot
$PhpExe = Join-Path $Root ".tools\php83\php.exe"
if (-not (Test-Path $PhpExe)) {
    Write-Error "PHP 8.3 not installed. Run: .\scripts\setup.ps1"
    exit 1
}
& $PhpExe @args
exit $LASTEXITCODE
