@echo off
REM PHPStan analiz — baseline aktif. Yeni hata varsa 1 ile çıkar.
REM Kullanım:  tools\run-phpstan.bat
REM Baseline yenilemek:  tools\run-phpstan.bat --generate-baseline phpstan-baseline.neon

setlocal
cd /d "%~dp0\.."
"C:\xampp\php\php.exe" -d memory_limit=1024M tools\phpstan.phar analyse --no-progress %*
exit /b %ERRORLEVEL%
