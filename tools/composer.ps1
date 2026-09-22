# Project-local Composer (uses .tools/php83)
$Root = Split-Path -Parent $PSScriptRoot
$PhpExe = Join-Path $Root ".tools\php83\php.exe"
$ComposerPhar = Join-Path $Root ".tools\composer.phar"
if (-not (Test-Path $PhpExe) -or -not (Test-Path $ComposerPhar)) {
    Write-Error "Toolchain missing. Run: .\scripts\setup.ps1"
    exit 1
}
& $PhpExe $ComposerPhar @args
exit $LASTEXITCODE
