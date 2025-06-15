<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // e.g., 'en', 'fr', 'es'
            $table->string('name', 100); // e.g., 'English', 'French', 'Spanish'
            $table->string('native_name', 100)->nullable(); // e.g., 'English', 'Français', 'Español'
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // Indexes for performance
            $table->index(['is_active', 'code']);
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locales');
    }
};
