<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255); // Translation key e.g., 'auth.login.title'
            $table->text('value'); // Translation value
            $table->foreignId('locale_id')->constrained('locales')->onDelete('cascade');
            $table->text('description')->nullable(); // Context description
            $table->json('metadata')->nullable(); // Additional metadata (pluralization, etc.)
            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable(); // Quality control
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Unique constraint to prevent duplicate key-locale combinations
            $table->unique(['key', 'locale_id']);

            // Performance indexes
            $table->index(['key', 'locale_id', 'is_active']); // Primary search pattern
            $table->index(['locale_id', 'is_active']); // Locale-based filtering
            $table->index('key'); // Key-based search
            $table->index('verified_at'); // Quality control queries

            // Full-text search index for value column (MySQL/PostgreSQL only)
            if (DB::getDriverName() !== 'sqlite') {
                $table->fullText(['value', 'key']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
