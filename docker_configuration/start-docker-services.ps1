# Startup script for Docker Compose services
# This script waits for Docker to be ready and starts all services

$projectPath = "c:\docker-projects\goldenz_hr_system"
$maxRetries = 30
$retryDelay = 2

# Wait for Docker to be available
Write-Host "Waiting for Docker to be ready..." -ForegroundColor Yellow
$retryCount = 0
while ($retryCount -lt $maxRetries) {
    try {
        $dockerInfo = docker info 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "Docker is ready!" -ForegroundColor Green
            break
        }
    } catch {
        # Docker not ready yet
    }
    
    if ($retryCount -eq $maxRetries - 1) {
        Write-Host "Docker did not become ready in time. Please start Docker Desktop manually." -ForegroundColor Red
        exit 1
    }
    
    $retryCount++
    Start-Sleep -Seconds $retryDelay
}

# Navigate to project directory and start services
Set-Location $projectPath
Write-Host "Starting Docker Compose services..." -ForegroundColor Yellow
docker-compose up -d

if ($LASTEXITCODE -eq 0) {
    Write-Host "All services started successfully!" -ForegroundColor Green
} else {
    Write-Host "Failed to start services. Check Docker Desktop and try again." -ForegroundColor Red
    exit 1
}
