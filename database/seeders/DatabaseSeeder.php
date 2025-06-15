<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Database Seeder
 *
 * Main seeder that calls all other seeders for the Translation Management Service
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting Translation Management Service database seeding...');

        // Create admin user if not exists
        if (!User::where('email', 'admin@translationservice.com')->exists()) {
            User::factory()->create([
                'name' => 'Translation Admin',
                'email' => 'admin@translationservice.com',
            ]);

            $this->command->info('✓ Created admin user (admin@translationservice.com)');
        }

        // Create test user if not exists
        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

            $this->command->info('✓ Created test user (test@example.com)');
        }

        // Run translation seeder for 100k+ records
        $this->call([
            TranslationSeeder::class,
        ]);

        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('📊 Summary:');
        $this->command->info('   Users: ' . User::count());
        $this->command->info('   Locales: ' . \App\Models\Locale::count());
        $this->command->info('   Translation Tags: ' . \App\Models\TranslationTag::count());
        $this->command->info('   Translations: ' . number_format(\App\Models\Translation::count()));
        $this->command->newLine();
        $this->command->info('🔑 Login credentials:');
        $this->command->info('   Admin: admin@translationservice.com');
        $this->command->info('   Test: test@example.com');
        $this->command->info('   Password: password (default for all users)');
    }
}
