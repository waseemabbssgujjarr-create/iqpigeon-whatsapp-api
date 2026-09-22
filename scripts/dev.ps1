$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root
$env:Path = "$Root;$Root\tools;" + $env:Path
Write-Host "Starting Laravel + Vite (Ctrl+C to stop)..."
Start-Process -NoNewWindow -FilePath "$Root\.tools\php83\php.exe" -ArgumentList "artisan","serve"
npm run dev
