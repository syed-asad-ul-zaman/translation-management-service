<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Locale;
use App\Models\Translation;
use App\Models\TranslationTag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Translation Seeder
 *
 * Seeds the database with 100k+ translation records for performance testing
 * Optimized for fast bulk insertion
 */
class TranslationSeeder extends Seeder
{
    /**
     * Total number of translations to create
     */
    private const TOTAL_TRANSLATIONS = 100000;

    /**
     * Batch size for bulk insertions
     */
    private const BATCH_SIZE = 1000;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Translation Management Service seeding...');

        $startTime = microtime(true);

        // Disable query log for performance
        DB::disableQueryLog();

        // Create base data first
        $this->createBaseData();

        // Create translations in batches
        $this->createTranslations();

        // Attach tags to translations
        $this->attachTagsToTranslations();

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        $this->command->info("✅ Seeding completed successfully!");
        $this->command->info("📊 Created " . number_format(self::TOTAL_TRANSLATIONS) . " translations");
        $this->command->info("⏱️  Execution time: {$executionTime} seconds");

        // Re-enable query log
        DB::enableQueryLog();
    }

    /**
     * Create base data (locales, tags, users)
     */
    private function createBaseData(): void
    {
        $this->command->info('📝 Creating base data...');

        // Create users for verification
        if (User::count() === 0) {
            User::factory(10)->create();
            $this->command->info('✓ Created 10 users');
        }

        // Create locales
        if (Locale::count() === 0) {
            $locales = [
                ['code' => 'en', 'name' => 'English', 'native_name' => 'English'],
                ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español'],
                ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français'],
                ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch'],
                ['code' => 'it', 'name' => 'Italian', 'native_name' => 'Italiano'],
                ['code' => 'pt', 'name' => 'Portuguese', 'native_name' => 'Português'],
                ['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский'],
                ['code' => 'ja', 'name' => 'Japanese', 'native_name' => '日本語'],
                ['code' => 'ko', 'name' => 'Korean', 'native_name' => '한국어'],
                ['code' => 'zh', 'name' => 'Chinese', 'native_name' => '中文'],
            ];

            foreach ($locales as $locale) {
                Locale::create($locale + ['is_active' => true]);
            }

            $this->command->info('✓ Created ' . count($locales) . ' locales');
        }

        // Create translation tags
        if (TranslationTag::count() === 0) {
            $tags = [
                ['name' => 'mobile', 'slug' => 'mobile', 'description' => 'Mobile application translations', 'color' => '#3b82f6'],
                ['name' => 'desktop', 'slug' => 'desktop', 'description' => 'Desktop application translations', 'color' => '#10b981'],
                ['name' => 'web', 'slug' => 'web', 'description' => 'Web application translations', 'color' => '#f59e0b'],
                ['name' => 'admin', 'slug' => 'admin', 'description' => 'Admin interface translations', 'color' => '#ef4444'],
                ['name' => 'api', 'slug' => 'api', 'description' => 'API response translations', 'color' => '#8b5cf6'],
                ['name' => 'auth', 'slug' => 'auth', 'description' => 'Authentication related translations', 'color' => '#ec4899'],
                ['name' => 'navigation', 'slug' => 'navigation', 'description' => 'Navigation menu translations', 'color' => '#06b6d4'],
                ['name' => 'forms', 'slug' => 'forms', 'description' => 'Form field translations', 'color' => '#84cc16'],
                ['name' => 'buttons', 'slug' => 'buttons', 'description' => 'Button text translations', 'color' => '#f97316'],
                ['name' => 'messages', 'slug' => 'messages', 'description' => 'User message translations', 'color' => '#a855f7'],
            ];

            foreach ($tags as $tag) {
                TranslationTag::create($tag + ['is_active' => true]);
            }

            $this->command->info('✓ Created ' . count($tags) . ' translation tags');
        }
    }

    /**
     * Create translations in optimized batches
     */
    private function createTranslations(): void
    {
        // Check if we already have enough translations
        $currentCount = Translation::count();
        if ($currentCount >= self::TOTAL_TRANSLATIONS) {
            $this->command->info("✓ Already have {$currentCount} translations (target: " . number_format(self::TOTAL_TRANSLATIONS) . ")");
            return;
        }

        $needed = self::TOTAL_TRANSLATIONS - $currentCount;
        $this->command->info('📚 Creating ' . number_format($needed) . ' additional translations...');

        $localeIds = Locale::pluck('id')->toArray();
        $userIds = User::pluck('id')->toArray();
        $batchCount = (int) ceil($needed / self::BATCH_SIZE);

        // Get existing key-locale combinations to avoid duplicates
        $existingCombinations = DB::table('translations')
            ->select(DB::raw("CONCAT(`key`, '_', locale_id) as combination"))
            ->pluck('combination')
            ->flip()
            ->toArray();

        $this->command->info('✓ Loaded ' . count($existingCombinations) . ' existing key-locale combinations');

        $keyPatterns = [
            'auth.login',
            'auth.register',
            'auth.password',
            'auth.forgot',
            'navigation.home',
            'navigation.about',
            'navigation.contact',
            'navigation.profile',
            'buttons.save',
            'buttons.cancel',
            'buttons.delete',
            'buttons.edit',
            'buttons.create',
            'forms.name',
            'forms.email',
            'forms.password',
            'forms.confirm',
            'forms.submit',
            'messages.success',
            'messages.error',
            'messages.warning',
            'messages.info',
            'labels.title',
            'labels.description',
            'labels.category',
            'labels.status',
            'errors.validation',
            'errors.server',
            'errors.network',
            'errors.permission',
            'dashboard.title',
            'dashboard.overview',
            'dashboard.statistics',
            'dashboard.recent',
            'profile.edit',
            'profile.settings',
            'profile.avatar',
            'profile.preferences',
            'settings.general',
            'settings.security',
            'settings.notifications',
            'settings.privacy',
        ];

        $suffixes = [
            'title',
            'subtitle',
            'description',
            'label',
            'placeholder',
            'button',
            'message',
            'error',
            'success',
            'warning',
            'info',
            'required',
            'optional',
            'loading',
            'empty',
            'confirm',
            'cancel',
            'submit',
            'reset',
            'clear',
        ];

        $progressBar = $this->command->getOutput()->createProgressBar($batchCount);
        $progressBar->start();

        $totalCreated = 0;
        $counter = $currentCount; // Start counter from current count

        for ($batch = 0; $batch < $batchCount; $batch++) {
            $translations = [];
            $batchSize = min(self::BATCH_SIZE, $needed - $totalCreated);

            $attempts = 0;
            $maxAttempts = $batchSize * 20; // Allow more attempts for unique generation

            for ($i = 0; $i < $batchSize && $attempts < $maxAttempts; $attempts++) {
                $pattern = fake()->randomElement($keyPatterns);
                $suffix = fake()->randomElement($suffixes);
                $localeId = fake()->randomElement($localeIds);

                // Create unique key using counter
                $key = "{$pattern}.{$suffix}.seed.{$counter}";
                $combination = "{$key}_{$localeId}";

                // Skip if combination already exists
                if (isset($existingCombinations[$combination])) {
                    $counter++;
                    continue;
                }

                $existingCombinations[$combination] = true;

                $translations[] = [
                    'key' => $key,
                    'value' => $this->generateTranslationValue($suffix),
                    'locale_id' => $localeId,
                    'description' => fake()->optional(0.3)->sentence(),
                    'metadata' => fake()->optional(0.2)->randomElement([
                        json_encode(['context' => 'formal']),
                        json_encode(['context' => 'informal']),
                        json_encode(['length' => 'short']),
                        json_encode(['audience' => 'technical']),
                        null,
                    ]),
                    'is_active' => fake()->boolean(95),
                    'verified_at' => fake()->optional(0.7)->dateTimeBetween('-1 year', 'now'),
                    'verified_by' => fake()->optional(0.7)->randomElement($userIds),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $counter++;
                $i++;
            }

            // Insert batch
            if (!empty($translations)) {
                Translation::insert($translations);
                $totalCreated += count($translations);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->newLine();
        $this->command->info("✓ Created {$totalCreated} new translations (total: " . ($currentCount + $totalCreated) . ")");
    }

    /**
     * Attach tags to translations (simplified to avoid duplicates)
     */
    private function attachTagsToTranslations(): void
    {
        // Check if we already have tag attachments
        $currentAttachments = DB::table('translation_translation_tag')->count();
        if ($currentAttachments > 0) {
            $this->command->info("✓ Already have {$currentAttachments} tag attachments");
            return;
        }

        $this->command->info('🏷️  Attaching tags to translations...');

        $translationIds = Translation::pluck('id')->toArray();
        $tagIds = TranslationTag::pluck('id')->toArray();

        if (empty($translationIds) || empty($tagIds)) {
            $this->command->warn('⚠️  No translations or tags found, skipping tag attachments');
            return;
        }

        $attachments = [];
        $maxAttachments = min(10000, count($translationIds)); // Reduced for performance

        $progressBar = $this->command->getOutput()->createProgressBar($maxAttachments);
        $progressBar->start();

        // Use a set to track unique combinations
        $usedCombinations = [];

        for ($i = 0; $i < $maxAttachments; $i++) {
            $attempts = 0;
            $maxAttempts = 10;

            do {
                $translationId = fake()->randomElement($translationIds);
                $tagId = fake()->randomElement($tagIds);
                $key = "{$translationId}_{$tagId}";
                $attempts++;
            } while (isset($usedCombinations[$key]) && $attempts < $maxAttempts);

            // Skip if we couldn't find a unique combination
            if (isset($usedCombinations[$key])) {
                $progressBar->advance();
                continue;
            }

            $usedCombinations[$key] = true;

            $attachments[] = [
                'translation_id' => $translationId,
                'translation_tag_id' => $tagId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insert in smaller batches
            if (count($attachments) >= 500) {
                DB::table('translation_translation_tag')->insert($attachments);
                $attachments = [];
            }

            $progressBar->advance();
        }

        // Insert remaining attachments
        if (!empty($attachments)) {
            DB::table('translation_translation_tag')->insert($attachments);
        }

        $progressBar->finish();
        $this->command->newLine();
        $this->command->info('✓ Attached ' . count($usedCombinations) . ' unique tag relationships');
    }

    /**
     * Generate realistic translation value based on suffix
     */
    private function generateTranslationValue(string $suffix): string
    {
        return match ($suffix) {
            'title' => fake()->sentence(2, false),
            'subtitle' => fake()->sentence(3, false),
            'description' => fake()->sentence(8),
            'label' => fake()->words(2, true),
            'placeholder' => 'Enter ' . fake()->word(),
            'button', 'submit' => ucfirst(fake()->word()),
            'message' => fake()->sentence(6),
            'error' => 'Error: ' . fake()->sentence(4),
            'success' => ucfirst(fake()->words(3, true)) . ' successfully!',
            'warning' => 'Warning: ' . fake()->sentence(4),
            'info' => fake()->sentence(5),
            'required' => 'This field is required.',
            'optional' => 'Optional',
            'loading' => 'Loading...',
            'empty' => 'No items found.',
            'confirm' => 'Are you sure?',
            'cancel' => 'Cancel',
            'reset' => 'Reset',
            'clear' => 'Clear',
            default => fake()->sentence(4),
        };
    }
}
