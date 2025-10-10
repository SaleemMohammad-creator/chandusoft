# -----------------------
# DB Backup Script (Windows/Laragon)
# -----------------------
 
# Database credentials
$dbName = "chandusoft"
$dbUser = "root"
$dbPass = ""          # leave empty for Laragon default
$dbHost = "localhost"
 
# MySQL dump executable path
$mysqldumpPath = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe"
 
# Backup folder
$backupDir = "C:\laragon\www\chandusoft\storage\backups"
if (!(Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir
}
 
# Backup filename
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupFile = "$backupDir\db-$timestamp.sql"
 
# Run mysqldump
try {
    if ($dbPass -eq "") {
        & "$mysqldumpPath" -h $dbHost -u $dbUser $dbName | Out-File -Encoding UTF8 $backupFile
    } else {
        & "$mysqldumpPath" -h $dbHost -u $dbUser -p$dbPass $dbName | Out-File -Encoding UTF8 $backupFile
    }
 
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Database backup successful: $backupFile"
    } else {
        Write-Host "❌ Database backup failed!"
    }
}
catch {
    Write-Host "❌ Error running mysqldump: $_"
}