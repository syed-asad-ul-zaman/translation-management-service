<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Locale Factory
 *
 * Generates realistic locale data for testing and seeding
 */
class LocaleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Locale::class;

    /**
     * Common locales with their native names
     */
    private array $locales = [
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
        ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية'],
        ['code' => 'hi', 'name' => 'Hindi', 'native_name' => 'हिन्दी'],
        ['code' => 'nl', 'name' => 'Dutch', 'native_name' => 'Nederlands'],
        ['code' => 'sv', 'name' => 'Swedish', 'native_name' => 'Svenska'],
        ['code' => 'da', 'name' => 'Danish', 'native_name' => 'Dansk'],
        ['code' => 'no', 'name' => 'Norwegian', 'native_name' => 'Norsk'],
        ['code' => 'fi', 'name' => 'Finnish', 'native_name' => 'Suomi'],
        ['code' => 'pl', 'name' => 'Polish', 'native_name' => 'Polski'],
        ['code' => 'tr', 'name' => 'Turkish', 'native_name' => 'Türkçe'],
        ['code' => 'cs', 'name' => 'Czech', 'native_name' => 'Čeština'],
    ];

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        static $index = 0;
        $index++;

        // Generate unique codes to avoid constraint violations
        $baseCode = $this->faker->unique()->languageCode();
        $code = $baseCode . $index; // Append index to ensure uniqueness

        return [
            'code' => $code,
            'name' => $this->faker->country(),
            'native_name' => $this->faker->country(),
            'is_active' => $this->faker->boolean(90), // 90% active
            'is_default' => false,
        ];
    }

    /**
     * Create English locale
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'is_active' => true,
        ]);
    }

    /**
     * Create Spanish locale
     */
    public function spanish(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'es',
            'name' => 'Spanish',
            'native_name' => 'Español',
            'is_active' => true,
        ]);
    }

    /**
     * Create French locale
     */
    public function french(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'fr',
            'name' => 'French',
            'native_name' => 'Français',
            'is_active' => true,
        ]);
    }

    /**
     * Create inactive locale
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create locale with specific code
     */
    public function withCode(string $code): static
    {
        $locale = collect($this->locales)->firstWhere('code', $code);

        return $this->state(fn (array $attributes) => [
            'code' => $code,
            'name' => $locale['name'] ?? ucfirst($code),
            'native_name' => $locale['native_name'] ?? ucfirst($code),
            'is_active' => true, // Default to active for testing
        ]);
    }
}
