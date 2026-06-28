# Anlık görüntüden geri yükleme (üzerine yazar — önce yeni snapshot alın)
# Kullanım: .\scripts\restore.ps1 -Name "20260404_153000"
# Listele:   Get-ChildItem .snapshots -Directory | Select-Object Name

param(
    [Parameter(Mandatory = $true)]
    [string]$Name,
    [switch]$Force
)

$ErrorActionPreference = "Stop"
$root = Split-Path $PSScriptRoot -Parent
$src = Join-Path $root ".snapshots\$Name"

if (-not (Test-Path $src)) {
    Write-Error "Bulunamadi: $src — Get-ChildItem .snapshots ile isimleri kontrol edin."
}

if (-not $Force) {
    Write-Host "UYARI: $src -> $root (ustune yazar). Onay icin: -Force ekleyin"
    exit 1
}

& robocopy $src $root /E /XD uploads wp-content cache logs .snapshots .git node_modules /NFL /NDL /NJH /NJS /nc /ns /np
$rc = $LASTEXITCODE
if ($rc -ge 8) {
    Write-Error "robocopy hata kodu: $rc"
}
Write-Host "Geri yukleme tamam."
