<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Translation;
use App\Models\TranslationTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Translation Controller Feature Tests
 *
 * Comprehensive tests for Translation API endpoints
 * Tests authentication, validation, CRUD operations, and performance
 */
class TranslationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Locale $locale;
    private TranslationTag $tag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->locale = Locale::factory()->create();
        $this->tag = TranslationTag::factory()->create();

        Sanctum::actingAs($this->user);
    }

    /**
     * Test translation index endpoint
     */
    public function test_can_list_translations(): void
    {
        Translation::factory(5)->create(['locale_id' => $this->locale->id]);

        $response = $this->getJson('/api/translations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'key',
                        'value',
                        'description',
                        'metadata',
                        'is_active',
                        'is_verified',
                        'locale',
                        'tags',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Test translation creation
     */
    public function test_can_create_translation(): void
    {
        $data = [
            'key' => 'test.translation.key',
            'value' => 'Test translation value',
            'locale_id' => $this->locale->id,
            'description' => 'Test description',
            'tag_ids' => [$this->tag->id],
        ];

        $response = $this->postJson('/api/translations', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'key',
                    'value',
                    'description',
                    'locale',
                    'tags',
                ],
            ]);

        $this->assertDatabaseHas('translations', [
            'key' => 'test.translation.key',
            'value' => 'Test translation value',
            'locale_id' => $this->locale->id,
        ]);
    }

    /**
     * Test translation creation validation
     */
    public function test_translation_creation_validation(): void
    {
        $response = $this->postJson('/api/translations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key', 'value', 'locale_id']);
    }

    /**
     * Test duplicate key validation
     */
    public function test_prevents_duplicate_keys_per_locale(): void
    {
        Translation::factory()->create([
            'key' => 'duplicate.key',
            'locale_id' => $this->locale->id,
        ]);

        $response = $this->postJson('/api/translations', [
            'key' => 'duplicate.key',
            'value' => 'Another value',
            'locale_id' => $this->locale->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    /**
     * Test translation update
     */
    public function test_can_update_translation(): void
    {
        $translation = Translation::factory()->create([
            'locale_id' => $this->locale->id,
        ]);

        $data = [
            'value' => 'Updated translation value',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/translations/{$translation->id}", $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'key',
                    'value',
                    'description',
                ],
            ]);

        $translation->refresh();
        $this->assertEquals('Updated translation value', $translation->value);
        $this->assertEquals('Updated description', $translation->description);
    }

    /**
     * Test translation deletion
     */
    public function test_can_delete_translation(): void
    {
        $translation = Translation::factory()->create();

        $response = $this->deleteJson("/api/translations/{$translation->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Translation deleted successfully.']);

        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }

    /**
     * Test translation search
     */
    public function test_can_search_translations(): void
    {
        Translation::factory()->create([
            'key' => 'auth.login.title',
            'value' => 'Login Page',
            'locale_id' => $this->locale->id,
        ]);

        Translation::factory()->create([
            'key' => 'auth.register.title',
            'value' => 'Register Page',
            'locale_id' => $this->locale->id,
        ]);

        $response = $this->getJson('/api/translations/search?q=login');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'auth.login.title');
    }

    /**
     * Test bulk operations
     */
    public function test_can_perform_bulk_operations(): void
    {
        $translations = Translation::factory(3)->create([
            'is_active' => true,
        ]);

        $data = [
            'action' => 'deactivate',
            'ids' => $translations->pluck('id')->toArray(),
        ];

        $response = $this->postJson('/api/translations/bulk', $data);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'affected_count']);

        foreach ($translations as $translation) {
            $translation->refresh();
            $this->assertFalse($translation->is_active);
        }
    }

    /**
     * Test filtering by locale
     */
    public function test_can_filter_by_locale(): void
    {
        $locale1 = Locale::factory()->create(['code' => 'en']);
        $locale2 = Locale::factory()->create(['code' => 'es']);

        Translation::factory(2)->create(['locale_id' => $locale1->id]);
        Translation::factory(3)->create(['locale_id' => $locale2->id]);

        $response = $this->getJson('/api/translations?locale=en');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test filtering by tag
     */
    public function test_can_filter_by_tag(): void
    {
        $translation = Translation::factory()->create();
        $translation->tags()->attach($this->tag);

        Translation::factory(2)->create();

        $response = $this->getJson("/api/translations?tag={$this->tag->slug}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test pagination
     */
    public function test_pagination_works(): void
    {
        Translation::factory(25)->create();

        $response = $this->getJson('/api/translations?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    /**
     * Test authentication is required
     */
    public function test_authentication_required(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/translations');

        $response->assertStatus(401);
    }

    /**
     * Test performance with large dataset
     */
    public function test_performance_with_large_dataset(): void
    {
        Translation::factory(100)->create();

        $startTime = microtime(true);
        $response = $this->getJson('/api/translations?per_page=20');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(200, $responseTime,
            "Response time {$responseTime}ms exceeds 200ms requirement");
    }

    /**
     * Test response time for individual translation
     */
    public function test_single_translation_response_time(): void
    {
        $translation = Translation::factory()->create();

        $startTime = microtime(true);
        $response = $this->getJson("/api/translations/{$translation->id}");
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(100, $responseTime,
            "Single translation response time {$responseTime}ms exceeds 100ms");
    }
}
