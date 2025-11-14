param(
    [string]$Database = "chandusoft_test",
    [string]$BackupFile = ""
)

if (-not $BackupFile) {
    Write-Host "❌ ERROR: Backup file not specified."
    Write-Host "Usage: powershell -ExecutionPolicy Bypass -File scripts\db-restore.ps1 -Database chandusoft_test -BackupFile backups\file.sql"
    exit
}

# ✅ Change this path to match your MySQL location (you already corrected it)
$mysqlExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"

Write-Host "🔄 Restoring database '$Database' from '$BackupFile'..."

# 👉 Automatically ignore foreign key constraint errors during import
$command = @"
SET FOREIGN_KEY_CHECKS = 0;
SOURCE $BackupFile;
SET FOREIGN_KEY_CHECKS = 1;
"@

# Execute MySQL and check exit code
cmd.exe /c "`"$mysqlExe`" -u root -p $Database -e `"$command`""

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Restore failed with errors!"
    exit 1
}

Write-Host "✅ Restore completed successfully."
