# Proje anlık görüntüsü — Git yokken geri alma için
# Kullanım:  cd c:\xampp\htdocs\mynakliyat
#            .\scripts\snapshot.ps1
#            .\scripts\snapshot.ps1 -Note "islem-oncesi"

param(
    [string]$Note = ""
)

$ErrorActionPreference = "Stop"
$root = Split-Path $PSScriptRoot -Parent

$name = Get-Date -Format "yyyyMMdd_HHmmss"
if ($Note) {
    $n = $Note -replace '[^\p{L}\p{N}\-_]', '' -replace '\s+', '-'
    if ($n.Length -gt 35) { $n = $n.Substring(0, 35) }
    if ($n) { $name += "_$n" }
}

$dest = Join-Path $root ".snapshots\$name"
New-Item -ItemType Directory -Path $dest -Force | Out-Null

Write-Host "Anlık goruntu: $dest"
& robocopy $root $dest /E /XD uploads wp-content cache logs .snapshots .git node_modules /NFL /NDL /NJH /NJS /nc /ns /np
$rc = $LASTEXITCODE
if ($rc -ge 8) {
    Write-Error "robocopy hata kodu: $rc"
}
Write-Host "Tamam. Geri yukleme: .\scripts\restore.ps1 -Name '$name'"
