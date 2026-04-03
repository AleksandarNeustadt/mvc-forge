<?php
// test-geo.php - Test geolocation functionality

session_start();

require_once __DIR__ . '/core/services/GeoLocation.php';

$geo = new GeoLocation();

echo "=== GeoLocation Test ===\n\n";

// Test 1: Detect your current IP
echo "1. Your detected IP:\n";
$detectedLang = $geo->detectLanguage();
echo "   Language detected: {$detectedLang}\n\n";

// Test 2: Test specific IPs from different countries
$testIps = [
    '8.8.8.8'         => 'US (Google DNS)',
    '81.92.211.100'   => 'Germany',
    '91.185.200.1'    => 'Serbia',
    '37.143.128.1'    => 'Bosnia',
    '217.73.192.1'    => 'Russia',
    '103.1.1.1'       => 'Taiwan',
];

echo "2. Testing known IPs:\n";
foreach ($testIps as $ip => $description) {
    $country = $geo->detectCountry($ip);
    $lang = $geo->detectLanguage($ip);
    echo "   {$description} ({$ip}): Country={$country}, Language={$lang}\n";
}

echo "\n3. Testing country-to-language mapping:\n";
$testCountries = ['RS', 'DE', 'US', 'TW', 'BR', 'JP', 'XX'];
foreach ($testCountries as $country) {
    $lang = $geo->getLanguageForCountry($country);
    echo "   {$country} => {$lang}\n";
}

echo "\n=== Test Complete ===\n";
