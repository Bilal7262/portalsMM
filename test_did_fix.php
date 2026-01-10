<?php

function request($method, $url, $data = null, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => json_decode($response, true)];
}

echo "1. Admin Login...\n";
$adminLogin = request('POST', 'http://127.0.0.1:8000/api/admin/login', [
    'email' => 'admin@admin.com',
    'password' => 'password'
]);
$token = $adminLogin['body']['token'] ?? null;
if (!$token) die("Login failed\n");
echo "Success.\n";

echo "\n2. Fetching Companies and DIDs...\n";
$companiesRes = request('GET', 'http://127.0.0.1:8000/api/admin/companies', null, $token);
$companyId = $companiesRes['body']['data'][0]['id'] ?? ($companiesRes['body'][0]['id'] ?? null);

$didsRes = request('GET', 'http://127.0.0.1:8000/api/admin/dids', null, $token);
$didId = $didsRes['body']['data'][0]['id'] ?? ($didsRes['body'][0]['id'] ?? null);

echo "Company ID: $companyId, DID ID: $didId\n";

if (!$companyId || !$didId) die("Missing seed data\n");

echo "\n3. Assigning DID (testing fix)...\n";
$assignRes = request('POST', 'http://127.0.0.1:8000/api/admin/company-dids', [
    'company_id' => $companyId,
    'did_id' => $didId,
    'price_per_min' => 0.05,
    'start_date' => date('Y-m-d')
], $token);

echo "Status: {$assignRes['status']}\n";
print_r($assignRes['body']);
