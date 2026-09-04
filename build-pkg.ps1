# Confeziona pkg_wmacommunication (componente + modulo) per Joomla 6.
# Uso:  da PowerShell, in questa cartella (radice del repo)
#       .\build-pkg.ps1
# Output: .\_build\pkg_wmacommunication.zip
#
# Nota: la GitHub Action (.github/workflows/release.yml) fa lo stesso lavoro
# automaticamente a ogni release pubblicata.

$ErrorActionPreference = 'Stop'

$comp  = Join-Path $PSScriptRoot 'com_wmacommunication'
$mod   = Join-Path $PSScriptRoot 'mod_wmacommunication'
$pkg   = Join-Path $PSScriptRoot 'pkg_wmacommunication'
$build = Join-Path $PSScriptRoot '_build'
$stage = Join-Path $build '_pkg_stage'

foreach ($p in @($comp, $mod, $pkg)) {
    if (-not (Test-Path $p)) { throw "Cartella mancante: $p" }
}

if (Test-Path $build) { Remove-Item $build -Recurse -Force }
New-Item -ItemType Directory -Path $stage -Force | Out-Null

# 1) ZIP del componente (il manifest deve stare alla radice dello zip)
Compress-Archive -Path (Join-Path $comp '*') `
    -DestinationPath (Join-Path $stage 'com_wmacommunication.zip') -Force

# 2) ZIP del modulo
Compress-Archive -Path (Join-Path $mod '*') `
    -DestinationPath (Join-Path $stage 'mod_wmacommunication.zip') -Force

# 3) File del pacchetto: manifest + script + lingue + i due zip
Copy-Item (Join-Path $pkg 'pkg_wmacommunication.xml') $stage
Copy-Item (Join-Path $pkg 'script.php')               $stage
Copy-Item (Join-Path $pkg 'language')                 $stage -Recurse

# 4) ZIP del pacchetto
Compress-Archive -Path (Join-Path $stage '*') `
    -DestinationPath (Join-Path $build 'pkg_wmacommunication.zip') -Force

Remove-Item $stage -Recurse -Force

Write-Host ''
Write-Host 'Pronto:' -ForegroundColor Green
Write-Host ('  ' + (Join-Path $build 'pkg_wmacommunication.zip'))
Write-Host ''
Write-Host 'Installa questo unico file da Sistema -> Installa estensioni.'
