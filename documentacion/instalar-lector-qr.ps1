# Ejecutar desde PowerShell. Descarga única, sin CDN en ejecución.
$ErrorActionPreference = 'Stop'
$destino = Join-Path (Split-Path -Parent $PSScriptRoot) 'assets/js/vendor'
New-Item -ItemType Directory -Force -Path $destino | Out-Null
$base = 'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/'
$tmp = Join-Path ([System.IO.Path]::GetTempPath()) ('lector-qr-' + [guid]::NewGuid())
New-Item -ItemType Directory -Path $tmp | Out-Null
try {
    $js = Join-Path $tmp 'html5-qrcode.min.js'
    $lic = Join-Path $tmp 'LICENSE-html5-qrcode.txt'
    Invoke-WebRequest -UseBasicParsing -Uri ($base + 'html5-qrcode.min.js') -OutFile $js
    Invoke-WebRequest -UseBasicParsing -Uri ($base + 'LICENSE') -OutFile $lic
    if ((Get-Item $js).Length -lt 100000 -or -not (Select-String -Path $js -Pattern 'Html5Qrcode' -Quiet)) { throw 'Archivo JavaScript inesperado.' }
    if (-not (Select-String -Path $lic -Pattern 'Apache License' -Quiet)) { throw 'Licencia inesperada.' }
    Copy-Item $js,$lic -Destination $destino -Force
    Write-Host 'Biblioteca local instalada. Conserva ambos archivos en Git.'
    Get-FileHash (Join-Path $destino 'html5-qrcode.min.js') -Algorithm SHA256
} finally { Remove-Item -Recurse -Force $tmp }
