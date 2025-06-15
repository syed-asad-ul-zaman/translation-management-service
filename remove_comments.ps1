# Script to remove single-line comments from PHP files
$phpFiles = Get-ChildItem -Path "tests\" -Filter "*.php" -Recurse

foreach ($file in $phpFiles) {
    Write-Host "Processing: $($file.FullName)"
    $content = Get-Content $file.FullName
    $newContent = @()

    foreach ($line in $content) {
        # Remove single-line comments that start with // but keep URLs and multi-line comment markers
        if ($line -match '^\s*//[^/]' -and $line -notmatch 'http://|https://') {
            # Skip this line
            continue
        } else {
            $newContent += $line
        }
    }

    $newContent | Set-Content $file.FullName
}

Write-Host "Done processing PHP test files."
