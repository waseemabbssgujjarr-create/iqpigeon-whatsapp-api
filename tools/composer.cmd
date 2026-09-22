@echo off
set "ROOT=%~dp0.."
set "PHP=%ROOT%\.tools\php83\php.exe"
set "COMPOSER=%ROOT%\.tools\composer.phar"
"%PHP%" "%COMPOSER%" %*
