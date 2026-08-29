param(
    [string]$ProjectRoot = $PSScriptRoot
)

$ErrorActionPreference = 'Stop'

$pluginSlug = 'htgithubforwp'
$sourceDirectory = Join-Path $ProjectRoot "codigo\$pluginSlug"
$mainFile = Join-Path $sourceDirectory "$pluginSlug.php"
$distributionDirectory = Join-Path $ProjectRoot 'dist'
$packagePath = Join-Path $distributionDirectory "$pluginSlug.zip"

if (-not (Test-Path -LiteralPath $sourceDirectory -PathType Container)) {
    throw "Diretório-fonte obrigatório não encontrado: $sourceDirectory"
}

if (-not (Test-Path -LiteralPath $mainFile -PathType Leaf)) {
    throw "Arquivo principal obrigatório não encontrado: $mainFile"
}

New-Item -ItemType Directory -Path $distributionDirectory -Force | Out-Null
$temporaryPackage = Join-Path ([System.IO.Path]::GetTempPath()) ("$pluginSlug-" + [guid]::NewGuid().ToString('N') + '.zip')

try {
    Compress-Archive -Path (Join-Path $sourceDirectory '*') -DestinationPath $temporaryPackage -CompressionLevel Optimal

    $entries = @(tar -tf $temporaryPackage)
    if ($LASTEXITCODE -ne 0) {
        throw 'Não foi possível validar o conteúdo do pacote.'
    }
    if ($entries -notcontains "$pluginSlug.php") {
        throw "O ZIP não contém $pluginSlug.php na raiz."
    }
    if ($entries | Where-Object { $_ -match '^(?:htgithubforwp[-_.]?\d|humanitastech-github)' }) {
        throw 'O ZIP contém diretório versionado ou slug legado.'
    }

    Move-Item -LiteralPath $temporaryPackage -Destination $packagePath -Force
}
finally {
    if (Test-Path -LiteralPath $temporaryPackage) {
        Remove-Item -LiteralPath $temporaryPackage -Force
    }
}

Write-Output $packagePath
