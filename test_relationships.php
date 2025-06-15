<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Translation Management Service Relationships\n";
echo "==================================================\n\n";

// Test 1: Get a translation with its locale and tags
echo "1. Testing Translation with Locale and Tags:\n";
$translation = App\Models\Translation::with(['locale', 'tags'])->first();

if ($translation) {
    echo "   Translation Key: " . $translation->key . "\n";
    echo "   Translation Value: " . $translation->value . "\n";
    echo "   Locale: " . $translation->locale->code . " (" . $translation->locale->name . ")\n";
    echo "   Tags: " . $translation->tags->pluck('name')->implode(', ') . "\n";
    echo "   Tag Count: " . $translation->tags->count() . "\n\n";
} else {
    echo "   No translations found!\n\n";
}

// Test 2: Get translations for a specific locale
echo "2. Testing Translations for a specific locale (en):\n";
$locale = App\Models\Locale::where('code', 'en')->first();
if ($locale) {
    $translationCount = $locale->translations()->count();
    echo "   Locale: {$locale->code} ({$locale->name})\n";
    echo "   Translation Count: {$translationCount}\n";

    // Show first 3 translations for this locale
    $sampleTranslations = $locale->translations()->limit(3)->get();
    foreach ($sampleTranslations as $trans) {
        echo "   - {$trans->key}: {$trans->value}\n";
    }
    echo "\n";
} else {
    echo "   English locale not found!\n\n";
}

// Test 3: Get translations for a specific tag
echo "3. Testing Translations for a specific tag:\n";
$tag = App\Models\TranslationTag::first();
if ($tag) {
    $translationCount = $tag->translations()->count();
    echo "   Tag: {$tag->name}\n";
    echo "   Translation Count: {$translationCount}\n";

    // Show first 3 translations for this tag
    $sampleTranslations = $tag->translations()->limit(3)->get();
    foreach ($sampleTranslations as $trans) {
        echo "   - {$trans->key}: {$trans->value} ({$trans->locale->code})\n";
    }
    echo "\n";
} else {
    echo "   No tags found!\n\n";
}

// Test 4: Database counts summary
echo "4. Database Summary:\n";
echo "   Total Translations: " . App\Models\Translation::count() . "\n";
echo "   Total Locales: " . App\Models\Locale::count() . "\n";
echo "   Total Tags: " . App\Models\TranslationTag::count() . "\n";
echo "   Total Tag Attachments: " . DB::table('translation_translation_tag')->count() . "\n";

echo "\nAll relationship tests completed successfully!\n";
