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

class LocaleControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Locale $locale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->locale = Locale::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    /** @test */
    public function it_can_list_locales(): void
    {
        Sanctum::actingAs($this->user);

        Locale::factory()->withCode('es')->create(['is_active' => true]);
        Locale::factory()->withCode('fr')->create(['is_active' => true]);
        Locale::factory()->withCode('de')->create(['is_active' => true]);
        Locale::factory()->withCode('it')->create(['is_active' => true]);
        Locale::factory()->withCode('pt')->create(['is_active' => true]);

        $response = $this->getJson('/api/locales');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'native_name',
                        'is_active',
                        'is_default',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(6, 'data');
    }

    /** @test */
    public function it_can_list_locales_with_statistics(): void
    {
        Sanctum::actingAs($this->user);

        Translation::factory()->count(10)->create(['locale_id' => $this->locale->id]);

        $response = $this->getJson('/api/locales?with_stats=true');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'translations_count',
                        'active_translations_count',
                    ]
                ]
            ]);

        $this->assertEquals(10, $response->json('data.0.translations_count'));
    }

    /** @test */
    public function it_can_include_inactive_locales(): void
    {
        Sanctum::actingAs($this->user);

        Locale::factory()->count(3)->create(['is_active' => false]);

        $response = $this->getJson('/api/locales');
        $response->assertOk()->assertJsonCount(1, 'data');

        $response = $this->getJson('/api/locales?include_inactive=true');
        $response->assertOk()->assertJsonCount(4, 'data');
    }

    /** @test */
    public function it_can_show_a_specific_locale(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/locales/{$this->locale->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'code',
                    'name',
                    'native_name',
                    'is_active',
                    'is_default',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $this->locale->id,
                    'code' => $this->locale->code,
                    'name' => $this->locale->name,
                ]
            ]);
    }

    /** @test */
    public function it_can_create_a_new_locale(): void
    {
        Sanctum::actingAs($this->user);

        $localeData = [
            'code' => 'fr',
            'name' => 'French',
            'native_name' => 'Français',
            'is_active' => true,
            'is_default' => false,
        ];

        $response = $this->postJson('/api/locales', $localeData);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'code',
                    'name',
                    'native_name',
                    'is_active',
                    'is_default',
                ]
            ])
            ->assertJson([
                'data' => $localeData
            ]);

        $this->assertDatabaseHas('locales', $localeData);
    }

    /** @test */
    public function it_validates_locale_creation(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/locales', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code', 'name']);

        $response = $this->postJson('/api/locales', [
            'code' => $this->locale->code,
            'name' => 'Another English',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);

        $response = $this->postJson('/api/locales', [
            'code' => 'INVALID',
            'name' => 'Invalid',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function it_can_update_a_locale(): void
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'name' => 'English (Updated)',
            'native_name' => 'English (Updated)',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/locales/{$this->locale->id}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'code',
                    'name',
                    'native_name',
                    'is_active',
                ]
            ]);

        $this->assertDatabaseHas('locales', [
            'id' => $this->locale->id,
            ...$updateData
        ]);
    }

    /** @test */
    public function it_can_delete_a_locale(): void
    {
        Sanctum::actingAs($this->user);

        $localeToDelete = Locale::factory()->create(['code' => 'de']);

        $response = $this->deleteJson("/api/locales/{$localeToDelete->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Locale deleted successfully.']);

        $this->assertDatabaseMissing('locales', ['id' => $localeToDelete->id]);
    }

    /** @test */
    public function it_prevents_deleting_locale_with_translations(): void
    {
        Sanctum::actingAs($this->user);

        Translation::factory()->create(['locale_id' => $this->locale->id]);

        $response = $this->deleteJson("/api/locales/{$this->locale->id}");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Cannot delete locale with existing translations.']);

        $this->assertDatabaseHas('locales', ['id' => $this->locale->id]);
    }

    /** @test */
    public function it_prevents_deleting_default_locale(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/locales/{$this->locale->id}");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Cannot delete the default locale.']);

        $this->assertDatabaseHas('locales', ['id' => $this->locale->id]);
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $response = $this->getJson('/api/locales');
        $response->assertUnauthorized();

        $response = $this->postJson('/api/locales', []);
        $response->assertUnauthorized();

        $response = $this->putJson("/api/locales/{$this->locale->id}", []);
        $response->assertUnauthorized();

        $response = $this->deleteJson("/api/locales/{$this->locale->id}");
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_handles_pagination(): void
    {
        Sanctum::actingAs($this->user);

        Locale::factory()->count(25)->create(['is_active' => true]);

        $response = $this->getJson('/api/locales?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total']
            ]);

        $this->assertEquals(10, $response->json('meta.per_page'));
        $this->assertEquals(26, $response->json('meta.total'));
    }

    /** @test */
    public function it_respects_per_page_limits(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/locales?per_page=200');
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);

        $response = $this->getJson('/api/locales?per_page=50');
        $response->assertOk();
    }
}
