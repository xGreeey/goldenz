# PowerShell script to generate SSL certificates for goldenz.local
# This script creates self-signed certificates for local development

Write-Host "Generating SSL certificates for goldenz.local..." -ForegroundColor Green

# Create certs directory if it doesn't exist
if (-not (Test-Path "certs")) {
    New-Item -ItemType Directory -Path "certs" | Out-Null
}

# Generate private key and certificate using OpenSSL
# Note: You need OpenSSL installed. If not available, you can use PowerShell's New-SelfSignedCertificate

# Check if OpenSSL is available
$opensslPath = Get-Command openssl -ErrorAction SilentlyContinue

if ($opensslPath) {
    Write-Host "Using OpenSSL..." -ForegroundColor Yellow
    
    # Generate private key
    openssl genrsa -out certs/goldenz.local-key.pem 2048
    
    # Generate certificate signing request
    openssl req -new -key certs/goldenz.local-key.pem -out certs/goldenz.local.csr -subj "/C=US/ST=State/L=City/O=Organization/CN=goldenz.local"
    
    # Generate self-signed certificate (valid for 365 days)
    openssl x509 -req -days 365 -in certs/goldenz.local.csr -signkey certs/goldenz.local-key.pem -out certs/goldenz.local.pem
    
    # Clean up CSR file
    Remove-Item certs/goldenz.local.csr
    
    Write-Host "SSL certificates generated successfully using OpenSSL!" -ForegroundColor Green
} else {
    Write-Host "OpenSSL not found. Using PowerShell's New-SelfSignedCertificate..." -ForegroundColor Yellow
    
    # Generate certificate using PowerShell (Windows native)
    $cert = New-SelfSignedCertificate `
        -DnsName "goldenz.local", "www.goldenz.local" `
        -CertStoreLocation "cert:\LocalMachine\My" `
        -KeyAlgorithm RSA `
        -KeyLength 2048 `
        -NotAfter (Get-Date).AddYears(1)
    
    # Export certificate to PEM format
    $certPath = "cert:\LocalMachine\My\$($cert.Thumbprint)"
    
    # Export certificate
    $pwd = ConvertTo-SecureString -String "temp" -Force -AsPlainText
    Export-Certificate -Cert $certPath -FilePath "certs\goldenz.local.cer" | Out-Null
    
    # Convert CER to PEM
    $certBytes = [System.IO.File]::ReadAllBytes("certs\goldenz.local.cer")
    $certBase64 = [System.Convert]::ToBase64String($certBytes)
    $pemCert = "-----BEGIN CERTIFICATE-----`n"
    for ($i = 0; $i -lt $certBase64.Length; $i += 64) {
        $pemCert += $certBase64.Substring($i, [Math]::Min(64, $certBase64.Length - $i)) + "`n"
    }
    $pemCert += "-----END CERTIFICATE-----"
    [System.IO.File]::WriteAllText("certs\goldenz.local.pem", $pemCert)
    
    # Export private key (requires additional steps - using OpenSSL format)
    Write-Host "Note: Private key export requires additional configuration." -ForegroundColor Yellow
    Write-Host "For full compatibility, please install OpenSSL or use the bash script." -ForegroundColor Yellow
    
    # Alternative: Use certutil to export with private key
    Write-Host "Attempting to export private key..." -ForegroundColor Yellow
    $pfxPath = "certs\goldenz.local.pfx"
    Export-PfxCertificate -Cert $certPath -FilePath $pfxPath -Password $pwd | Out-Null
    
    Write-Host "Certificate generated. PFX file created at: $pfxPath" -ForegroundColor Green
    Write-Host "You may need to convert PFX to PEM format for Apache compatibility." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Certificate: certs/goldenz.local.pem" -ForegroundColor Cyan
Write-Host "Private Key: certs/goldenz.local-key.pem" -ForegroundColor Cyan
Write-Host ""
Write-Host "Note: These are self-signed certificates. Your browser will show a security warning." -ForegroundColor Yellow
Write-Host "You can accept the certificate to proceed with HTTPS access." -ForegroundColor Yellow
