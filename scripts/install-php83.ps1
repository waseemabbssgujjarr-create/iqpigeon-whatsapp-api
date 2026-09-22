$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
$tools = Join-Path $Root ".tools"
$phpDir = Join-Path $tools "php83"
New-Item -ItemType Directory -Force -Path $phpDir | Out-Null
$url = "https://windows.php.net/downloads/releases/php-8.3.33-nts-Win32-vs16-x64.zip"
$zip = Join-Path $tools "php83.zip"
Invoke-WebRequest -Uri $url -OutFile $zip -UseBasicParsing
Expand-Archive -Path $zip -DestinationPath $phpDir -Force
Remove-Item $zip -Force
$ini = Join-Path $phpDir "php.ini"
Copy-Item (Join-Path $phpDir "php.ini-development") $ini -Force
(Get-Content $ini -Raw) -replace ';extension=curl','extension=curl' -replace ';extension=fileinfo','extension=fileinfo' -replace ';extension=mbstring','extension=mbstring' -replace ';extension=openssl','extension=openssl' -replace ';extension=pdo_mysql','extension=pdo_mysql' -replace ';extension=pdo_sqlite','extension=pdo_sqlite' -replace ';extension=sqlite3','extension=sqlite3' -replace ';extension=tokenizer','extension=tokenizer' -replace ';extension=xml','extension=xml' -replace ';extension=zip','extension=zip' -replace ';extension=intl','extension=intl' -replace ';extension=sodium','extension=sodium' -replace ';extension=bcmath','extension=bcmath' | Set-Content $ini
Write-Host "PHP 8.3 installed at $phpDir"
