#!/usr/bin/env pwsh
# PowerShell script to connect and run cleanup on production server

param(
    [string]$SshHost = "69.57.161.222",
    [string]$User = "root",
    [PSCredential]$Credential,
    [string]$ProjectPath = "/opt/insurance2026"
)

# Check if Posh-SSH is installed
if (-not (Get-Module -ListAvailable -Name Posh-SSH)) {
    Write-Host "Installing Posh-SSH module..." -ForegroundColor Yellow
    Install-Module -Name Posh-SSH -Force -AllowClobber
}

Import-Module Posh-SSH -WarningAction SilentlyContinue

if (-not $Credential) {
    $securePassword = Read-Host -AsSecureString "Enter SSH password for $User@$SshHost"
    $Credential = New-Object System.Management.Automation.PSCredential($User, $securePassword)
}

try {
    Write-Host "Connecting to $SshHost..." -ForegroundColor Cyan
    $session = New-SSHSession -ComputerName $SshHost -Credential $Credential -AcceptKey -SkipCertificateCheck -WarningAction SilentlyContinue

    if ($session.Connected) {
        Write-Host "✓ Connected successfully" -ForegroundColor Green

        # Run dry-run preview
        Write-Host "`nRunning dry-run preview..." -ForegroundColor Yellow
        $dryRunCmd = "cd $ProjectPath; php artisan dashboard:cleanup --dry-run"
        $dryRunResult = Invoke-SSHCommand -SessionId $session.SessionId -Command $dryRunCmd
        Write-Host $dryRunResult.Output

        # Ask for confirmation if dry-run succeeded
        if ($dryRunResult.ExitStatus -eq 0) {
            Write-Host "`nDry-run completed. Proceed with actual cleanup? (y/N): " -ForegroundColor Yellow -NoNewline
            $confirm = Read-Host

            if ($confirm -eq 'y' -or $confirm -eq 'Y') {
                Write-Host "Running actual cleanup..." -ForegroundColor Cyan
                $cleanupCmd = "cd $ProjectPath; php artisan dashboard:cleanup"
                $cleanupResult = Invoke-SSHCommand -SessionId $session.SessionId -Command $cleanupCmd
                Write-Host $cleanupResult.Output

                if ($cleanupResult.ExitStatus -eq 0) {
                    Write-Host "✓ Cleanup completed successfully!" -ForegroundColor Green
                }
                else {
                    Write-Host "✗ Cleanup failed" -ForegroundColor Red
                }
            }
            else {
                Write-Host "Canceled." -ForegroundColor Yellow
            }
        }

        # Disconnect
        Remove-SSHSession -SessionId $session.SessionId | Out-Null
    }
    else {
        Write-Host "✗ Failed to connect to $Host" -ForegroundColor Red
        exit 1
    }
}
catch {
    Write-Host "✗ Error: $_" -ForegroundColor Red
    exit 1
}
