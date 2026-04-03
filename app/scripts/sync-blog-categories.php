<?php
/**
 * Script: Sync Multilingual Blog Categories
 */

$appPath = dirname(__DIR__);
require_once $appPath . '/bootstrap/app.php';
ap_bootstrap_cli_application($appPath);

echo "🌍 Starting Blog Categories Sync...\n\n";

$languages = Language::getActive();
$serbian = Language::findByCode('sr');
$srCategories = BlogCategory::query()->where('language_id', $serbian->id)->get();

$enTranslations = [
    'Projekti' => 'Projects',
    'Vesti' => 'News',
    'O nama' => 'About Me',
    'Uslovi' => 'Terms',
    'Privatnost' => 'Privacy',
];

$enSlugTranslations = [
    'nasi-projects' => 'projects',
    'vesti' => 'news',
    'o-nama' => 'about-me',
    'uslovi' => 'terms',
    'privatnsot' => 'privacy',
];

foreach ($languages as $lang) {
    if ($lang->code === 'sr') continue;
    echo "🌐 Processing: {$lang->name} ({$lang->code})...\n";

    foreach ($srCategories as $srCatData) {
        $srCat = (new BlogCategory())->newFromBuilder($srCatData);
        
        $existing = BlogCategory::query()
            ->where('language_id', $lang->id)
            ->where('slug', $srCat->slug) // Will fail for EN if we don't translate slug first
            ->first();
            
        if ($existing) continue;

        $newData = $srCat->getAttributes();
        unset($newData['id']);
        $newData['language_id'] = $lang->id;
        $newData['created_at'] = time();
        $newData['updated_at'] = time();

        if ($lang->code === 'en') {
            if (isset($enTranslations[$srCat->name])) {
                $newData['name'] = $enTranslations[$srCat->name];
            }
            if (isset($enSlugTranslations[$srCat->slug])) {
                $newData['slug'] = $enSlugTranslations[$srCat->slug];
            }
        }

        try {
            BlogCategory::create($newData);
            echo "  ✅ Created: {$newData['name']}\n";
        } catch (Exception $e) {
            echo "  ❌ Failed: " . $e->getMessage() . "\n";
        }
    }
}
echo "\n🚀 Done!\n";
