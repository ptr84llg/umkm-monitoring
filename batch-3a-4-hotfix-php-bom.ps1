Set-Location "C:\laragon\www\umkm-monitoring"

New-Item -ItemType Directory -Force -Path "backups\batch-3a-4-hotfix-php-bom" | Out-Null

$files = @(
    "app\Http\Controllers\Api\Internal\RegionApiController.php",
    "routes\api-internal.php"
)

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

foreach ($file in $files) {
    if (Test-Path $file) {
        $backupName = ($file -replace '[\\/:*?"<>|]', '_')
        Copy-Item $file "backups\batch-3a-4-hotfix-php-bom\$backupName" -Force

        $content = [System.IO.File]::ReadAllText((Resolve-Path $file))

        # Remove UTF-8 BOM if present
        $content = $content.TrimStart([char]0xFEFF)

        # Remove any whitespace/output before opening PHP tag
        $index = $content.IndexOf("<?php")
        if ($index -gt 0) {
            $content = $content.Substring($index)
        }

        # Normalize accidental leading blank lines
        $content = $content -replace "^\s*<\?php", "<?php"

        [System.IO.File]::WriteAllText((Resolve-Path $file), $content, $utf8NoBom)

        Write-Host "Fixed UTF-8 no BOM and leading whitespace:" $file
    } else {
        Write-Host "File not found:" $file
    }
}

Write-Host "Hotfix selesai. Backup: backups\batch-3a-4-hotfix-php-bom"
