# Fail-safe DB restore for Windows / Laragon

$DB_NAME = "chandusoft"
$DB_USER = "root"
$BACKUP_DIR = ".\storage\backups"
$ARCHIVE_DIR = ".\storage\backups\archive"

# Use latest backup by default
$BACKUP_FILE = Get-ChildItem $BACKUP_DIR -Filter *.sql | Sort-Object LastWriteTime -Descending | Select-Object -First 1

# Prompt for MySQL password
$DB_PASS = Read-Host -Prompt "Enter MySQL password for user $DB_USER" -AsSecureString
$BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($DB_PASS)
$PlainPass = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)

# Ensure archive folder exists
if (!(Test-Path $ARCHIVE_DIR)) { New-Item -ItemType Directory -Path $ARCHIVE_DIR }

# --- Step 1: Backup current DB ---
$TIMESTAMP = Get-Date -Format "yyyyMMdd_HHmmss"
$ARCHIVE_FILE = Join-Path $ARCHIVE_DIR "${DB_NAME}_backup_$TIMESTAMP.sql"

Write-Host "Creating current DB backup at $ARCHIVE_FILE ..."
cmd.exe /c "mysqldump.exe -u $DB_USER -p$PlainPass $DB_NAME > `"$ARCHIVE_FILE`""

# --- Step 2: Restore from selected backup ---
Write-Host "Restoring database '$DB_NAME' from backup file: $($BACKUP_FILE.FullName) ..."
cmd.exe /c "mysql.exe -u $DB_USER -p$PlainPass $DB_NAME < `"$($BACKUP_FILE.FullName)`""

Write-Host "Restore command executed. Check output above for errors."
