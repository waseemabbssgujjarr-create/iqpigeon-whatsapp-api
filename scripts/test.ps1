$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root
& "$Root\tools\php.ps1" artisan test
exit $LASTEXITCODE
