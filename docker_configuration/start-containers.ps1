# PowerShell script to start Docker containers automatically
# This script waits for Docker to be ready, then starts the containers

$projectPath = "C:\docker-projects\goldenz_hr_system"
$maxWaitTime = 120 # Maximum wait time in seconds
$waitInterval = 5 # Check every 5 seconds

# Function to check if Docker is running
function Test-DockerRunning {
    try {
        $result = docker info 2>&1
        if ($LASTEXITCODE -eq 0) {
            return $true
        }
        return $false
    } catch {
        return $false
    }
}

# Wait for Docker to be ready
Write-Host "Waiting for Docker to be ready..." -ForegroundColor Yellow
$elapsed = 0
while (-not (Test-DockerRunning)) {
    if ($elapsed -ge $maxWaitTime) {
        Write-Host "Docker did not become ready within $maxWaitTime seconds. Exiting." -ForegroundColor Red
        exit 1
    }
    Start-Sleep -Seconds $waitInterval
    $elapsed += $waitInterval
    Write-Host "Still waiting... ($elapsed seconds)" -ForegroundColor Gray
}

Write-Host "Docker is ready! Starting containers..." -ForegroundColor Green

# Change to project directory and start containers
Set-Location $projectPath
docker-compose up -d

if ($LASTEXITCODE -eq 0) {
    Write-Host "Containers started successfully!" -ForegroundColor Green
} else {
    Write-Host "Failed to start containers." -ForegroundColor Red
    exit 1
}
