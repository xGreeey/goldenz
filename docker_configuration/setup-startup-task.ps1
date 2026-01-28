# Script to create a Windows Scheduled Task that runs on startup
# Run this script ONCE as Administrator to set up automatic container startup

$scriptPath = "C:\docker-projects\goldenz_hr_system\start-containers.ps1"
$taskName = "DockerHRSystem_StartContainers"

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator', then run this script again." -ForegroundColor Yellow
    exit 1
}

# Remove existing task if it exists
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Write-Host "Removing existing task..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

# Create the scheduled task
Write-Host "Creating scheduled task..." -ForegroundColor Green

$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`""
$trigger = New-ScheduledTaskTrigger -AtLogOn
$principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" -LogonType Interactive -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RunOnlyIfNetworkAvailable:$false

Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Description "Automatically starts Docker containers for HR System on Windows startup"

Write-Host "Scheduled task created successfully!" -ForegroundColor Green
Write-Host "Task Name: $taskName" -ForegroundColor Cyan
Write-Host "The containers will now start automatically when you log in to Windows." -ForegroundColor Green
Write-Host ""
Write-Host "To test the task, you can run:" -ForegroundColor Yellow
Write-Host "  Start-ScheduledTask -TaskName `"$taskName`"" -ForegroundColor Cyan
Write-Host ""
Write-Host "To remove the task later, run:" -ForegroundColor Yellow
Write-Host "  Unregister-ScheduledTask -TaskName `"$taskName`" -Confirm:`$false" -ForegroundColor Cyan
