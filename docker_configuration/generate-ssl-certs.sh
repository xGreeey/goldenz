#!/bin/bash

# Generate SSL certificates for goldenz.local
# This script creates self-signed certificates for local development

echo "Generating SSL certificates for goldenz.local..."

# Create certs directory if it doesn't exist
mkdir -p certs

# Generate private key
openssl genrsa -out certs/goldenz.local-key.pem 2048

# Generate certificate signing request
openssl req -new -key certs/goldenz.local-key.pem -out certs/goldenz.local.csr -subj "/C=US/ST=State/L=City/O=Organization/CN=goldenz.local"

# Generate self-signed certificate (valid for 365 days)
openssl x509 -req -days 365 -in certs/goldenz.local.csr -signkey certs/goldenz.local-key.pem -out certs/goldenz.local.pem

# Clean up CSR file
rm certs/goldenz.local.csr

echo "SSL certificates generated successfully!"
echo "Certificate: certs/goldenz.local.pem"
echo "Private Key: certs/goldenz.local-key.pem"
echo ""
echo "Note: These are self-signed certificates. Your browser will show a security warning."
echo "You can accept the certificate to proceed with HTTPS access."
