# === CONFIG ===
$DBHost = "localhost"
$DBName = "chandusoft"
$DBUser = "root"
$DBPass = ""   # leave empty if not needed
$TestDBName = "chandusoft_test"   # test DB name

# === PATH SETUP ===
$BackupDir = Join-Path (Split-Path $PSScriptRoot -Parent) "storage\backups"
$DuplicateDir = Join-Path $BackupDir "duplicate"
$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$FileName = "db-$Timestamp.sql"
$FullPath = Join-Path $BackupDir $FileName
$DuplicatePath = Join-Path $DuplicateDir $FileName

# === CREATE BACKUP DIRECTORIES ===
foreach ($dir in @($BackupDir, $DuplicateDir)) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir | Out-Null
    }
}

Write-Host "Backing up database '$DBName' to $FullPath ..."

# === RUN BACKUP ===
if ($DBPass -ne "") {
    $backupCmd = "mysqldump -h $DBHost -u $DBUser -p$DBPass $DBName > `"$FullPath`""
} else {
    $backupCmd = "mysqldump -h $DBHost -u $DBUser $DBName > `"$FullPath`""
}
cmd /c $backupCmd

# === VERIFY RESULT ===
if ((Test-Path $FullPath) -and ((Get-Item $FullPath).Length -gt 0)) {
    Write-Host "✅ Backup completed successfully!"
    Write-Host "File saved to: $FullPath"

    # --- Create duplicate copy ---
    Copy-Item -Path $FullPath -Destination $DuplicatePath -Force
    Write-Host "✅ Duplicate backup created at: $DuplicatePath"

    # === RESTORE TO TEST DATABASE ===
    Write-Host "`n🔄 Restoring to test database '$TestDBName' ..."

    $dropCreateSQL = @"
DROP DATABASE IF EXISTS $TestDBName;
CREATE DATABASE $TestDBName;
"@

    $dropCreateCmd = if ($DBPass -ne "") {
        "mysql -h $DBHost -u $DBUser -p$DBPass -e `"$dropCreateSQL`""
    } else {
        "mysql -h $DBHost -u $DBUser -e `"$dropCreateSQL`""
    }
    Invoke-Expression $dropCreateCmd

    # --- Import backup into test DB ---
    if ($DBPass -ne "") {
        $importCmd = "mysql -h $DBHost -u $DBUser -p$DBPass $TestDBName < `"$FullPath`""
    } else {
        $importCmd = "mysql -h $DBHost -u $DBUser $TestDBName < `"$FullPath`""
    }
    cmd /c $importCmd

    Write-Host "✅ Test database '$TestDBName' is now synchronized."

    # === OPTIONAL: Delete old backups (keep last 10) ===
    $allBackups = Get-ChildItem $BackupDir -File | Sort-Object LastWriteTime -Descending
    $oldBackups = $allBackups | Select-Object -Skip 10
    foreach ($b in $oldBackups) {
        Remove-Item $b.FullName -Force
        Write-Host "🗑 Deleted old backup: $($b.Name)"
    }

} else {
    Write-Host "❌ Backup failed or file is empty!"
    Remove-Item $FullPath -ErrorAction SilentlyContinue
}
