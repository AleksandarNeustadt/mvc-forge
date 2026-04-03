<?php
/**
 * Map Languages to Continents and Regions
 * 
 * Maps existing languages to continents and regions based on country_code
 * Run with: php scripts/map-languages-to-continents-regions.php
 */

$appPath = dirname(__DIR__);
require_once $appPath . '/bootstrap/app.php';
ap_bootstrap_cli_application($appPath);

echo "🌍 Mapping languages to continents and regions...\n\n";

try {
    $tables = DatabaseBuilder::getTables();
    
    if (!in_array('languages', $tables)) {
        echo "❌ Table 'languages' does not exist.\n";
        exit(1);
    }
    
    if (!in_array('continents', $tables)) {
        echo "❌ Table 'continents' does not exist. Please run world migrations first.\n";
        exit(1);
    }
    
    if (!in_array('regions', $tables)) {
        echo "❌ Table 'regions' does not exist. Please run world migrations first.\n";
        exit(1);
    }

    // Get all continents and regions
    $continents = Database::select("SELECT id, code, name FROM continents WHERE is_active = 1");
    $regions = Database::select("SELECT id, continent_id, code, name FROM regions WHERE is_active = 1");
    
    // Create lookup maps
    $continentMap = [];
    foreach ($continents as $continent) {
        $continentMap[$continent['code']] = $continent['id'];
    }
    
    $regionMap = [];
    foreach ($regions as $region) {
        $regionMap[$region['code']] = $region['id'];
    }
    
    // Language code to continent and region mapping (fallback when country_code is missing)
    // Format: 'language_code' => ['continent_code', 'region_code']
    $languageCodeMapping = [
        'mk' => ['eu', 'balkans'],      // Macedonian
        'sr' => ['eu', 'balkans'],      // Serbian
        'hr' => ['eu', 'balkans'],      // Croatian
        'bg' => ['eu', 'balkans'],      // Bulgarian
        'sl' => ['eu', 'central-europe'], // Slovenian
    ];

    // Country code to continent and region mapping
    // Format: 'country_code' => ['continent_code', 'region_code']
    $countryMapping = [
        // Europe
        'RS' => ['eu', 'balkans'],      // Serbia
        'BA' => ['eu', 'balkans'],      // Bosnia and Herzegovina
        'ME' => ['eu', 'balkans'],      // Montenegro
        'HR' => ['eu', 'balkans'],      // Croatia
        'SI' => ['eu', 'central-europe'], // Slovenia
        'MK' => ['eu', 'balkans'],      // North Macedonia
        'BG' => ['eu', 'balkans'],      // Bulgaria
        'RO' => ['eu', 'eastern-europe'], // Romania
        'HU' => ['eu', 'central-europe'], // Hungary
        'SK' => ['eu', 'central-europe'], // Slovakia
        'CZ' => ['eu', 'central-europe'], // Czech Republic
        'PL' => ['eu', 'eastern-europe'], // Poland
        'DE' => ['eu', 'western-europe'], // Germany
        'AT' => ['eu', 'central-europe'], // Austria
        'CH' => ['eu', 'western-europe'], // Switzerland
        'FR' => ['eu', 'western-europe'], // France
        'BE' => ['eu', 'western-europe'], // Belgium
        'NL' => ['eu', 'western-europe'], // Netherlands
        'GB' => ['eu', 'northern-europe'], // United Kingdom
        'IE' => ['eu', 'northern-europe'], // Ireland
        'DK' => ['eu', 'northern-europe'], // Denmark
        'SE' => ['eu', 'northern-europe'], // Sweden
        'NO' => ['eu', 'northern-europe'], // Norway
        'FI' => ['eu', 'northern-europe'], // Finland
        'IS' => ['eu', 'northern-europe'], // Iceland
        'ES' => ['eu', 'southern-europe'], // Spain
        'PT' => ['eu', 'southern-europe'], // Portugal
        'IT' => ['eu', 'southern-europe'], // Italy
        'GR' => ['eu', 'southern-europe'], // Greece
        'CY' => ['eu', 'southern-europe'], // Cyprus
        'MT' => ['eu', 'southern-europe'], // Malta
        'RU' => ['eu', 'eastern-europe'], // Russia (European part)
        'UA' => ['eu', 'eastern-europe'], // Ukraine
        'BY' => ['eu', 'eastern-europe'], // Belarus
        'LT' => ['eu', 'northern-europe'], // Lithuania
        'LV' => ['eu', 'northern-europe'], // Latvia
        'EE' => ['eu', 'northern-europe'], // Estonia
        
        // Asia
        'CN' => ['as', 'east-asia'],     // China
        'JP' => ['as', 'east-asia'],     // Japan
        'KR' => ['as', 'east-asia'],     // South Korea
        'KP' => ['as', 'east-asia'],     // North Korea
        'TW' => ['as', 'east-asia'],     // Taiwan
        'HK' => ['as', 'east-asia'],     // Hong Kong
        'MO' => ['as', 'east-asia'],     // Macau
        'MN' => ['as', 'east-asia'],     // Mongolia
        'IN' => ['as', 'south-asia'],    // India
        'PK' => ['as', 'south-asia'],    // Pakistan
        'BD' => ['as', 'south-asia'],    // Bangladesh
        'LK' => ['as', 'south-asia'],    // Sri Lanka
        'NP' => ['as', 'south-asia'],    // Nepal
        'TH' => ['as', 'southeast-asia'], // Thailand
        'VN' => ['as', 'southeast-asia'], // Vietnam
        'ID' => ['as', 'southeast-asia'], // Indonesia
        'MY' => ['as', 'southeast-asia'], // Malaysia
        'SG' => ['as', 'southeast-asia'], // Singapore
        'PH' => ['as', 'southeast-asia'], // Philippines
        'MM' => ['as', 'southeast-asia'], // Myanmar
        'KH' => ['as', 'southeast-asia'], // Cambodia
        'LA' => ['as', 'southeast-asia'], // Laos
        'KZ' => ['as', 'central-asia'],  // Kazakhstan
        'UZ' => ['as', 'central-asia'],  // Uzbekistan
        'KG' => ['as', 'central-asia'],  // Kyrgyzstan
        'TJ' => ['as', 'central-asia'],  // Tajikistan
        'TM' => ['as', 'central-asia'], // Turkmenistan
        'AF' => ['as', 'central-asia'], // Afghanistan
        'TR' => ['as', 'west-asia'],     // Turkey
        'IR' => ['as', 'middle-east'],    // Iran
        'IQ' => ['as', 'middle-east'],    // Iraq
        'SA' => ['as', 'middle-east'],    // Saudi Arabia
        'AE' => ['as', 'middle-east'],    // UAE
        'IL' => ['as', 'middle-east'],    // Israel
        'JO' => ['as', 'middle-east'],    // Jordan
        'LB' => ['as', 'middle-east'],    // Lebanon
        'SY' => ['as', 'middle-east'],    // Syria
        'YE' => ['as', 'middle-east'],    // Yemen
        'OM' => ['as', 'middle-east'],    // Oman
        'KW' => ['as', 'middle-east'],    // Kuwait
        'QA' => ['as', 'middle-east'],    // Qatar
        'BH' => ['as', 'middle-east'],    // Bahrain
        
        // North America
        'US' => ['na', 'north-america'],  // United States
        'CA' => ['na', 'north-america'],  // Canada
        'MX' => ['na', 'central-america'], // Mexico
        'GT' => ['na', 'central-america'], // Guatemala
        'BZ' => ['na', 'central-america'], // Belize
        'SV' => ['na', 'central-america'], // El Salvador
        'HN' => ['na', 'central-america'], // Honduras
        'NI' => ['na', 'central-america'], // Nicaragua
        'CR' => ['na', 'central-america'], // Costa Rica
        'PA' => ['na', 'central-america'], // Panama
        'CU' => ['na', 'caribbean'],      // Cuba
        'JM' => ['na', 'caribbean'],      // Jamaica
        'HT' => ['na', 'caribbean'],      // Haiti
        'DO' => ['na', 'caribbean'],      // Dominican Republic
        'PR' => ['na', 'caribbean'],      // Puerto Rico
        'TT' => ['na', 'caribbean'],      // Trinidad and Tobago
        
        // South America
        'BR' => ['sa', 'south-america'],  // Brazil
        'AR' => ['sa', 'south-america'],  // Argentina
        'CL' => ['sa', 'south-america'],  // Chile
        'CO' => ['sa', 'south-america'],  // Colombia
        'PE' => ['sa', 'south-america'],  // Peru
        'VE' => ['sa', 'south-america'],  // Venezuela
        'EC' => ['sa', 'south-america'],  // Ecuador
        'BO' => ['sa', 'south-america'],  // Bolivia
        'PY' => ['sa', 'south-america'],  // Paraguay
        'UY' => ['sa', 'south-america'],  // Uruguay
        'GY' => ['sa', 'south-america'],  // Guyana
        'SR' => ['sa', 'south-america'],  // Suriname
        
        // Africa
        'EG' => ['af', 'north-africa'],   // Egypt
        'LY' => ['af', 'north-africa'],   // Libya
        'TN' => ['af', 'north-africa'],   // Tunisia
        'DZ' => ['af', 'north-africa'],   // Algeria
        'MA' => ['af', 'north-africa'],   // Morocco
        'SD' => ['af', 'north-africa'],   // Sudan
        'ET' => ['af', 'east-africa'],    // Ethiopia
        'KE' => ['af', 'east-africa'],    // Kenya
        'TZ' => ['af', 'east-africa'],    // Tanzania
        'UG' => ['af', 'east-africa'],    // Uganda
        'RW' => ['af', 'east-africa'],    // Rwanda
        'NG' => ['af', 'west-africa'],    // Nigeria
        'GH' => ['af', 'west-africa'],   // Ghana
        'SN' => ['af', 'west-africa'],    // Senegal
        'ZA' => ['af', 'southern-africa'], // South Africa
        'ZW' => ['af', 'southern-africa'], // Zimbabwe
        'CD' => ['af', 'central-africa'], // DR Congo
        'CM' => ['af', 'central-africa'], // Cameroon
        
        // Oceania
        'AU' => ['oc', 'australasia'],    // Australia
        'NZ' => ['oc', 'australasia'],    // New Zealand
        'FJ' => ['oc', 'melanesia'],      // Fiji
        'PG' => ['oc', 'melanesia'],      // Papua New Guinea
        'NC' => ['oc', 'melanesia'],      // New Caledonia
        'VU' => ['oc', 'melanesia'],      // Vanuatu
        'SB' => ['oc', 'melanesia'],      // Solomon Islands
        'WS' => ['oc', 'polynesia'],      // Samoa
        'TO' => ['oc', 'polynesia'],      // Tonga
        'PF' => ['oc', 'polynesia'],      // French Polynesia
        'GU' => ['oc', 'micronesia'],     // Guam
        'FM' => ['oc', 'micronesia'],     // Micronesia
    ];

    // Get all languages
    $languages = Database::select("SELECT id, code, name, country_code FROM languages");
    
    echo "📋 Processing " . count($languages) . " languages...\n\n";
    
    $updated = 0;
    $skipped = 0;
    
    foreach ($languages as $language) {
        $countryCode = strtoupper($language['country_code'] ?? '');
        $langId = $language['id'];
        $langCode = strtolower($language['code'] ?? '');
        $langName = $language['name'];
        
        // Try to get mapping from country_code first, then fallback to language code
        $continentCode = null;
        $regionCode = null;
        
        if (!empty($countryCode) && isset($countryMapping[$countryCode])) {
            list($continentCode, $regionCode) = $countryMapping[$countryCode];
        } elseif (!empty($langCode) && isset($languageCodeMapping[$langCode])) {
            // Fallback to language code mapping
            list($continentCode, $regionCode) = $languageCodeMapping[$langCode];
            echo "  ℹ️  Language '{$langName}' ({$langCode}) has no country_code, using language code mapping...\n";
        } else {
            if (empty($countryCode) && empty($langCode)) {
                echo "  ⚠️  Language '{$langName}' has no country_code or language code, skipping...\n";
            } elseif (empty($countryCode)) {
                echo "  ⚠️  Language '{$langName}' ({$langCode}) has no country_code and no language code mapping, skipping...\n";
            } else {
                echo "  ⚠️  Country code '{$countryCode}' for language '{$langName}' not found in mapping, skipping...\n";
            }
            $skipped++;
            continue;
        }
        
        $continentId = $continentMap[$continentCode] ?? null;
        $regionId = $regionMap[$regionCode] ?? null;
        
        if (!$continentId) {
            echo "  ⚠️  Continent '{$continentCode}' not found for language '{$langName}', skipping...\n";
            $skipped++;
            continue;
        }
        
        if (!$regionId) {
            echo "  ⚠️  Region '{$regionCode}' not found for language '{$langName}', skipping...\n";
            $skipped++;
            continue;
        }
        
        // Update language
        Database::execute(
            "UPDATE languages SET continent_id = ?, region_id = ? WHERE id = ?",
            [$continentId, $regionId, $langId]
        );
        
        $countryInfo = !empty($countryCode) ? $countryCode : 'no country_code';
        echo "  ✅ Mapped '{$langName}' ({$langCode}, {$countryInfo}) → {$continentCode} / {$regionCode}\n";
        $updated++;
    }
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Mapping completed!\n";
    echo "   Updated: {$updated} languages\n";
    echo "   Skipped: {$skipped} languages\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
