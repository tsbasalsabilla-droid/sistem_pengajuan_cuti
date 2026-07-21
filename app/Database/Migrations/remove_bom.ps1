$path = 'app\Database\Migrations\2026-07-02-000000_AddTimestampsToPengajuanCuti.php'
$content = Get-Content -Path $path -Raw
$enc = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($path, $content, $enc)
