<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Translation;
use App\Models\Locale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Translation Factory
 *
 * Generates realistic translation data for testing and seeding
 * Optimized for creating large datasets (100k+ records)
 */
class TranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Translation::class;

    /**
     * Common translation key patterns for realistic data
     */
    private array $keyPatterns = [
        'auth.login.{0}',
        'auth.register.{0}',
        'auth.password.{0}',
        'navigation.{0}',
        'buttons.{0}',
        'messages.{0}',
        'errors.{0}',
        'validation.{0}',
        'forms.{0}',
        'labels.{0}',
        'titles.{0}',
        'descriptions.{0}',
        'alerts.{0}',
        'modals.{0}',
        'tables.{0}',
        'dashboard.{0}',
        'profile.{0}',
        'settings.{0}',
        'notifications.{0}',
        'search.{0}',
    ];

    /**
     * Common translation suffixes
     */
    private array $suffixes = [
        'title', 'subtitle', 'description', 'label', 'placeholder', 'button',
        'submit', 'cancel', 'save', 'delete', 'edit', 'create', 'update',
        'success', 'error', 'warning', 'info', 'confirm', 'message',
        'required', 'optional', 'loading', 'empty', 'not_found',
    ];

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $keyPattern = $this->faker->randomElement($this->keyPatterns);
        $suffix = $this->faker->randomElement($this->suffixes);
        // Add unique suffix to prevent collisions
        $key = str_replace('{0}', $suffix . '_' . $this->faker->unique()->randomNumber(5), $keyPattern);

        return [
            'key' => $key,
            'value' => $this->generateRealisticTranslation($suffix),
            'locale_id' => Locale::factory(),
            'description' => $this->faker->optional(0.3)->sentence(),
            'metadata' => $this->faker->optional(0.2)->randomElement([
                ['pluralization' => 'one'],
                ['context' => 'formal'],
                ['context' => 'informal'],
                ['length' => 'short'],
                ['length' => 'long'],
                ['audience' => 'technical'],
                ['audience' => 'general'],
            ]),
            'is_active' => $this->faker->boolean(95), // 95% active
            'verified_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'verified_by' => fn () => $this->faker->optional(0.7)->randomElement(User::pluck('id')->toArray()),
        ];
    }

    /**
     * Generate realistic translation text based on suffix
     */
    private function generateRealisticTranslation(string $suffix): string
    {
        return match ($suffix) {
            'title' => $this->faker->sentence(2, false),
            'subtitle' => $this->faker->sentence(3, false),
            'description' => $this->faker->sentence(8),
            'label' => $this->faker->words(2, true),
            'placeholder' => 'Enter ' . $this->faker->word(),
            'button', 'submit', 'save', 'create', 'update' => ucfirst($this->faker->word()),
            'cancel', 'delete' => ucfirst($suffix),
            'success' => ucfirst($this->faker->words(3, true)) . ' successfully!',
            'error' => 'An error occurred: ' . $this->faker->sentence(4),
            'warning' => 'Warning: ' . $this->faker->sentence(4),
            'info' => $this->faker->sentence(5),
            'confirm' => 'Are you sure you want to ' . $this->faker->word() . '?',
            'message' => $this->faker->sentence(6),
            'required' => 'This field is required.',
            'optional' => 'Optional',
            'loading' => 'Loading...',
            'empty' => 'No items found.',
            'not_found' => 'Item not found.',
            default => $this->faker->sentence(4),
        };
    }

    /**
     * Create verified translation
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'verified_by' => User::factory(),
        ]);
    }

    /**
     * Create unverified translation
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_at' => null,
            'verified_by' => null,
        ]);
    }

    /**
     * Create inactive translation
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create translation with specific key pattern
     */
    public function withKeyPattern(string $pattern): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $pattern . '.' . $this->faker->randomElement($this->suffixes),
        ]);
    }

    /**
     * Create translation for specific locale
     */
    public function forLocale(int $localeId): static
    {
        return $this->state(fn (array $attributes) => [
            'locale_id' => $localeId,
        ]);
    }

    /**
     * Create translation with metadata
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata,
        ]);
    }
}
