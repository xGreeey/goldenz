<?php
/**
 * Simple MinIO Test - Verify credentials work
 */

header('Content-Type: text/plain');

$endpoint = 'http://minio:9000';
$bucket = 'goldenz-uploads';
$access_key = 'goldenz';
$secret_key = 'SUOMYNONA';
$region = 'us-east-1';

echo "Testing MinIO Connection...\n\n";
echo "Endpoint: $endpoint\n";
echo "Bucket: $bucket\n";
echo "Access Key: $access_key\n";
echo "Region: $region\n\n";

// Test 1: Simple connectivity
echo "Test 1: Basic connectivity...\n";
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP Code: $http_code\n";
if ($http_code >= 200 && $http_code < 400) {
    echo "✓ MinIO is reachable\n\n";
} else {
    echo "✗ Cannot reach MinIO\n\n";
}

// Test 2: Try to list buckets (requires auth)
echo "Test 2: List buckets (requires authentication)...\n";
$amz_date = gmdate('Ymd\THis\Z');
$date_stamp = gmdate('Ymd');
$parsed_url = parse_url($endpoint);
$host = strtolower($parsed_url['host']);
$port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
$host_header = $host . $port;

$canonical_uri = '/';
$canonical_querystring = '';
$canonical_headers = "host:" . $host_header . "\n" .
                     "x-amz-date:" . $amz_date . "\n";
$signed_headers = 'host;x-amz-date';
$payload_hash = hash('sha256', '');

$canonical_request = "GET\n" .
                    $canonical_uri . "\n" .
                    $canonical_querystring . "\n" .
                    $canonical_headers .
                    "\n" .
                    $signed_headers . "\n" .
                    $payload_hash;

$algorithm = 'AWS4-HMAC-SHA256';
$credential_scope = $date_stamp . '/' . $region . '/s3/aws4_request';
$string_to_sign = $algorithm . "\n" .
                 $amz_date . "\n" .
                 $credential_scope . "\n" .
                 hash('sha256', $canonical_request);

$kSecret = 'AWS4' . $secret_key;
$kDate = hash_hmac('sha256', $date_stamp, $kSecret, true);
$kRegion = hash_hmac('sha256', $region, $kDate, true);
$kService = hash_hmac('sha256', 's3', $kRegion, true);
$kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
$signature = strtolower(hash_hmac('sha256', $string_to_sign, $kSigning));

$authorization = $algorithm . ' ' .
                'Credential=' . $access_key . '/' . $credential_scope . ', ' .
                'SignedHeaders=' . $signed_headers . ', ' .
                'Signature=' . $signature;

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Host: ' . $host_header,
        'x-amz-date: ' . $amz_date,
        'Authorization: ' . $authorization,
    ],
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
if ($http_code >= 200 && $http_code < 300) {
    echo "✓ Authentication successful!\n";
    echo "Response: " . substr($response, 0, 200) . "\n\n";
} else {
    echo "✗ Authentication failed\n";
    echo "Response: " . htmlspecialchars(substr($response, 0, 500)) . "\n\n";
    echo "Canonical Request:\n" . str_replace("\n", "\\n", $canonical_request) . "\n\n";
    echo "String to Sign:\n" . str_replace("\n", "\\n", $string_to_sign) . "\n\n";
}
