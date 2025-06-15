<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TranslationTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Translation Tag Factory
 *
 * Generates realistic translation tag data for testing and seeding
 */
class TranslationTagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TranslationTag::class;

    /**
     * Common translation tag names
     */
    private array $tagNames = [
        'mobile', 'desktop', 'web', 'api', 'admin', 'frontend', 'backend',
        'authentication', 'navigation', 'forms', 'buttons', 'modals', 'alerts',
        'tables', 'dashboard', 'profile', 'settings', 'notifications', 'search',
        'filters', 'pagination', 'validation', 'errors', 'success', 'warnings',
        'tooltips', 'placeholders', 'labels', 'titles', 'descriptions',
        'general', 'specific', 'technical', 'user-friendly', 'formal', 'casual',
    ];

    /**
     * Color palette for tags
     */
    private array $colors = [
        '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e',
        '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16', '#22c55e',
        '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1',
        '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#6b7280', '#374151',
    ];

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement($this->tagNames);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->optional(0.7)->sentence(),
            'color' => $this->faker->randomElement($this->colors),
            'is_active' => $this->faker->boolean(95), // 95% active
        ];
    }

    /**
     * Create tag for mobile platform
     */
    public function mobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'mobile',
            'slug' => 'mobile',
            'description' => 'Translations for mobile applications',
            'color' => '#3b82f6',
        ]);
    }

    /**
     * Create tag for desktop platform
     */
    public function desktop(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'desktop',
            'slug' => 'desktop',
            'description' => 'Translations for desktop applications',
            'color' => '#10b981',
        ]);
    }

    /**
     * Create tag for web platform
     */
    public function web(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'web',
            'slug' => 'web',
            'description' => 'Translations for web applications',
            'color' => '#f59e0b',
        ]);
    }

    /**
     * Create tag for admin interface
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin',
            'slug' => 'admin',
            'description' => 'Translations for admin interface',
            'color' => '#ef4444',
        ]);
    }

    /**
     * Create inactive tag
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create tag with specific color
     */
    public function withColor(string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => $color,
        ]);
    }
}
