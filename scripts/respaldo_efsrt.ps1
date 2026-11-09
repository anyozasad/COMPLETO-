# Respaldo EFSRT - Mallqui Gym (Windows / XAMPP / Laragon)
# Incluye código PHP, assets locales y dump MySQL. No modifica la BD.
param(
    [string]$Proyecto = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path,
    [string]$MySqlDump = 'C:\xampp\mysql\bin\mysqldump.exe',
    [string]$Usuario = 'root',
    [string]$BaseDeDatos = 'gym_system',
    [string]$ArchivoCredenciales = ''
)
$ErrorActionPreference='Stop'
if (!(Test-Path $MySqlDump -PathType Leaf)) {
    throw "No existe mysqldump en: $MySqlDump. Indica la ruta correcta con -MySqlDump."
}
if (!(Test-Path (Join-Path $Proyecto 'app') -PathType Container)) {
    throw 'La ruta del proyecto no contiene la carpeta app.'
}
if (!(Test-Path (Join-Path $Proyecto 'public') -PathType Container)) {
    throw 'La ruta del proyecto no contiene la carpeta public.'
}

$fecha=Get-Date -Format 'yyyyMMdd_HHmmss'
$temporal=Join-Path ([IO.Path]::GetTempPath()) ("efstr_gym_" + [guid]::NewGuid().ToString('N'))
$salida=Join-Path $Proyecto ("RESPALDO_EFSRT_MALLQUI_GYM_$fecha.zip")
New-Item -ItemType Directory -Path $temporal | Out-Null
try {
    $sql=Join-Path $temporal 'gym_system.sql'
    $args=@(
        '--single-transaction',
        '--routines',
        '--triggers',
        '--default-character-set=utf8mb4',
        "--user=$Usuario",
        "--result-file=$sql",
        $BaseDeDatos
    )
    if ($ArchivoCredenciales -ne '') {
        if (!(Test-Path $ArchivoCredenciales -PathType Leaf)) {
            throw "No existe el archivo de credenciales configurado."
        }
        $args=@("--defaults-extra-file=$ArchivoCredenciales")+$args
    }
    & $MySqlDump @args
    if ($LASTEXITCODE -ne 0 -or !(Test-Path $sql)) {
        throw 'Falló mysqldump. No se generó un respaldo válido.'
    }
    foreach ($carpeta in @('app','public','database','docs','scripts','tests')) {
        $origen=Join-Path $Proyecto $carpeta
        if (Test-Path $origen) {
            Copy-Item -Path $origen -Destination (Join-Path $temporal $carpeta) -Recurse -Force
        }
    }
    foreach ($archivo in @('README.md','schema.sql','composer.json','composer.lock')) {
        $origen=Join-Path $Proyecto $archivo
        if (Test-Path $origen) {
            Copy-Item -Path $origen -Destination (Join-Path $temporal $archivo) -Force
        }
    }
    Compress-Archive -Path (Join-Path $temporal '*') -DestinationPath $salida -CompressionLevel Optimal -Force
    if (!(Test-Path $salida)) { throw 'No se creó el ZIP del respaldo.' }
    Write-Host "Respaldo de código, imágenes y BD guardado en: $salida" -ForegroundColor Green
    Write-Host "Archivo SQL incluido: gym_system.sql" -ForegroundColor Green
}
finally {
    Remove-Item -Path $temporal -Recurse -Force -ErrorAction SilentlyContinue
}
