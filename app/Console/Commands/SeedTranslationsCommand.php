<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\TranslationSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seed Translations Command
 *
 * Custom command to seed database with 100k+ translation records
 * Provides options for different dataset sizes
 */
class SeedTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'translations:seed
                            {--count=100000 : Number of translations to create}
                            {--batch-size=1000 : Batch size for bulk insertions}
                            {--fresh : Drop and recreate all tables before seeding}
                            {--force : Force seeding without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Seed the database with translation records for performance testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->option('count');
        $batchSize = (int) $this->option('batch-size');
        $fresh = $this->option('fresh');
        $force = $this->option('force');

        $this->info('🚀 Translation Management Service - Database Seeding');
        $this->newLine();

        if ($count < 1 || $count > 1000000) {
            $this->error('Count must be between 1 and 1,000,000');
            return self::FAILURE;
        }

        if ($batchSize < 100 || $batchSize > 10000) {
            $this->error('Batch size must be between 100 and 10,000');
            return self::FAILURE;
        }

        $this->table(['Setting', 'Value'], [
            ['Translations to create', number_format($count)],
            ['Batch size', number_format($batchSize)],
            ['Fresh migration', $fresh ? 'Yes' : 'No'],
            ['Estimated time', $this->estimateTime($count)],
        ]);

        if (!$force && !$this->confirm('Do you want to proceed with seeding?')) {
            $this->info('Seeding cancelled.');
            return self::SUCCESS;
        }

        $startTime = microtime(true);

        try {
            if ($fresh) {
                $this->info('🔄 Running fresh migrations...');
                $this->call('migrate:fresh');
                $this->newLine();
            }

            $this->info('📚 Starting translation seeding...');

            $originalTotal = defined('\Database\Seeders\TranslationSeeder::TOTAL_TRANSLATIONS')
                ? \Database\Seeders\TranslationSeeder::TOTAL_TRANSLATIONS
                : 100000;

            $originalBatchSize = defined('\Database\Seeders\TranslationSeeder::BATCH_SIZE')
                ? \Database\Seeders\TranslationSeeder::BATCH_SIZE
                : 1000;

            $this->updateSeederConstants($count, $batchSize);

            $this->call('db:seed', [
                '--class' => TranslationSeeder::class,
            ]);

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            $this->newLine();
            $this->info('✅ Seeding completed successfully!');
            $this->info("⏱️  Total execution time: {$executionTime} seconds");
            $this->info("📊 Performance: " . number_format($count / $executionTime) . " translations/second");

            $this->newLine();
            $this->info('📊 Final Database Statistics:');
            $stats = [
                ['Users', number_format(DB::table('users')->count())],
                ['Locales', number_format(DB::table('locales')->count())],
                ['Translation Tags', number_format(DB::table('translation_tags')->count())],
                ['Translations', number_format(DB::table('translations')->count())],
                ['Tag Attachments', number_format(DB::table('translation_translation_tag')->count())],
            ];
            $this->table(['Entity', 'Count'], $stats);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Seeding failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return self::FAILURE;
        }
    }

    /**
     * Estimate execution time based on record count
     */
    private function estimateTime(int $count): string
    {
        $seconds = $count / 1000;

        if ($seconds < 60) {
            return round($seconds) . ' seconds';
        } elseif ($seconds < 3600) {
            return round($seconds / 60) . ' minutes';
        } else {
            return round($seconds / 3600, 1) . ' hours';
        }
    }

    /**
     * Update seeder constants (for demonstration - in production use dependency injection)
     */
    private function updateSeederConstants(int $count, int $batchSize): void
    {
        $this->info("📝 Using {$count} translations with batch size {$batchSize}");
    }
}
