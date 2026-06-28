# My Nakliyat - ilk Git commit (SEO/GSC calismalari yedegi)
# Git yuklu degilse: https://git-scm.com/download/win
# Calistirma: PowerShell'de proje kokunden veya:
#   powershell -ExecutionPolicy Bypass -File ".\scripts\git-ilk-yedek.ps1"

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

$git = Get-Command git -ErrorAction SilentlyContinue
if (-not $git) {
    $candidates = @(
        "${env:ProgramFiles}\Git\bin\git.exe",
        "${env:ProgramFiles(x86)}\Git\bin\git.exe"
    )
    foreach ($c in $candidates) {
        if (Test-Path $c) { $env:Path = "$(Split-Path $c -Parent);$env:Path"; break }
    }
    $git = Get-Command git -ErrorAction SilentlyContinue
}
if (-not $git) {
    Write-Host "Git bulunamadi. Yukleyin: https://git-scm.com/download/win" -ForegroundColor Red
    Write-Host "Alternatif: scripts\yerel-zip-yedek.ps1 ile ZIP yedek alin." -ForegroundColor Yellow
    exit 1
}

if (-not (Test-Path ".git")) {
    git init
    Write-Host "Git deposu olusturuldu." -ForegroundColor Green
}

git add -A
git status
$msg = "Yedek: SEO ve Search Console duzenlemeleri ($(Get-Date -Format 'yyyy-MM-dd HH:mm'))"
git commit -m $msg
if ($LASTEXITCODE -ne 0) {
    Write-Host "Commit atlanmis olabilir (degisiklik yok veya hata). git status ile kontrol edin." -ForegroundColor Yellow
    exit $LASTEXITCODE
}
Write-Host "Tamam. Son commit:" -ForegroundColor Green
git log -1 --oneline
