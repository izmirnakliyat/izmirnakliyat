# Kod yedegi ZIP (.gitignore ile uyumlu agir klasorler haric)
# Calistirma: powershell -ExecutionPolicy Bypass -File ".\scripts\yerel-zip-yedek.ps1"

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$stamp = Get-Date -Format "yyyy-MM-dd-HHmm"
$zipName = "mynakliyat-kod-yedek-$stamp.zip"
$outDir = Split-Path $root -Parent
$zipPath = Join-Path $outDir $zipName
$tmp = Join-Path $env:TEMP "mynakliyat-backup-$stamp"

if (Test-Path $tmp) { Remove-Item $tmp -Recurse -Force }
New-Item -ItemType Directory -Path $tmp | Out-Null

# /MIR benzeri: kod + admin; uploads/logs/cache/.git dislanir
$xd = @(
    "uploads", "logs", "cache", ".git", ".idea", ".vscode", "wp-content",
    ".snapshots", "node_modules", "_gsc_validation_2026-04-04"
)
# .gitignore ile uyum: veritabani sifreleri ZIP'e girmesin
$xf = @("*.log", "Thumbs.db", ".DS_Store", "db.php")

$robArgs = @($root, $tmp, "/E", "/NFL", "/NDL", "/NJH", "/NJS", "/nc", "/ns", "/np")
foreach ($d in $xd) { $robArgs += "/XD"; $robArgs += (Join-Path $root $d) }
foreach ($f in $xf) { $robArgs += "/XF"; $robArgs += $f }

& robocopy @robArgs
# robocopy 0-7 basarili sayilir
if ($LASTEXITCODE -ge 8) { throw "robocopy hata kodu: $LASTEXITCODE" }

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
Compress-Archive -Path (Join-Path $tmp "*") -DestinationPath $zipPath -CompressionLevel Optimal
Remove-Item $tmp -Recurse -Force

Write-Host "ZIP olusturuldu: $zipPath" -ForegroundColor Green
Write-Host "Not: config/db.php ZIP'e dahil edilmedi (.gitignore ile uyum). Veritabani ayri yedekleyin."
