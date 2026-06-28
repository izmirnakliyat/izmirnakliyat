# cPanel File Manager veya FTP icin zip uretir (.env ve buyuk/hassas klasorler dahil edilmez).
# Kullanim (PowerShell, proje kokunden veya scripts klasorunden):
#   .\scripts\package-cpanel-upload.ps1
# Medya ile birlikte (uploads + wp-content cok buyuyebilir):
#   .\scripts\package-cpanel-upload.ps1 -IncludeUploads
# Cikti yolu:
#   .\scripts\package-cpanel-upload.ps1 -OutputDir "D:\Yedekler"

param(
    [switch]$IncludeUploads,
    [string]$OutputDir = ""
)

$ErrorActionPreference = 'Stop'
$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path

$excludeDirs = [System.Collections.Generic.List[string]]::new()
foreach ($d in @('.git', '.snapshots', 'logs', 'cache', 'node_modules', 'vendor', '.cursor')) {
    [void]$excludeDirs.Add($d)
}
if (-not $IncludeUploads) {
    foreach ($d in @('uploads', 'wp-content')) {
        [void]$excludeDirs.Add($d)
    }
}

$stagingName = 'mynakliyat-cpanel-staging-' + [guid]::NewGuid().ToString('n')
$Staging = Join-Path $env:TEMP $stagingName
New-Item -ItemType Directory -Path $Staging -Force | Out-Null

$robocopyArgs = @(
    $ProjectRoot, $Staging, '/E',
    '/XF', '.env',
    '/NFL', '/NDL', '/NJH', '/NJS', '/NP',
    '/XD'
) + $excludeDirs

& robocopy @robocopyArgs
$rc = $LASTEXITCODE
# robocopy: 0-7 basari (1 = dosya kopyalandi); 8+ hata
if ($rc -ge 8) {
    Remove-Item -LiteralPath $Staging -Recurse -Force -ErrorAction SilentlyContinue
    throw "robocopy hata kodu: $rc"
}

$envExample = Join-Path $ProjectRoot '.env.example'
if (Test-Path -LiteralPath $envExample) {
    Copy-Item -LiteralPath $envExample -Destination (Join-Path $Staging 'CPANEL-ENV-ORNEGI.txt') -Force
}

$readme = @"
cPanel yukleme (kisa)
=====================
1) Zip acilir; TUM icerigi public_html (veya subdomain Document Root) icine atin — fazladan ic klasor birakmayin.
2) MySQL: veritabani + kullanici olustur, phpMyAdmin ile SQL yedeğini ice aktarin.
3) CPANEL-ENV-ORNEGI.txt icerigini kopyalayip sunucuda .env adiyla kaydedin (dosya adi tam .env olmali); DB bilgilerini doldurun.
4) uploads/ klasoru zip'te yoksa: yerelden FTP ile yukleyin veya -IncludeUploads ile yeniden zip alin.
5) SSL acik olsun; PHP 8.1+ secin.

Sunucuda (SSH varsa): php scripts/verify-deploy.php --strict
"@
Set-Content -Path (Join-Path $Staging 'CPANEL-ADIMLAR.txt') -Value $readme.Trim() -Encoding UTF8

$destDir = if ($OutputDir -ne '') { $OutputDir } else { [Environment]::GetFolderPath('Desktop') }
if (-not (Test-Path -LiteralPath $destDir)) {
    New-Item -ItemType Directory -Path $destDir -Force | Out-Null
}
$zipName = 'mynakliyat-cpanel-' + (Get-Date -Format 'yyyyMMdd-HHmm') + '.zip'
$zipPath = Join-Path $destDir $zipName
if (Test-Path -LiteralPath $zipPath) {
    Remove-Item -LiteralPath $zipPath -Force
}

Compress-Archive -Path (Join-Path $Staging '*') -DestinationPath $zipPath -Force
Remove-Item -LiteralPath $Staging -Recurse -Force

Write-Host "Zip hazir: $zipPath"
if (-not $IncludeUploads) {
    Write-Host "Not: uploads/ ve wp-content/ haric tutuldu. Medya icin FTP veya -IncludeUploads kullanin."
}
exit 0
