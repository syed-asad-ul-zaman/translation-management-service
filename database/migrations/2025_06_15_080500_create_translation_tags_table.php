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
        Schema::create('translation_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // e.g., 'mobile', 'desktop', 'web'
            $table->string('slug', 50)->unique(); // URL-friendly version
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6366f1'); // Hex color for UI
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['is_active', 'slug']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_tags');
    }
};
