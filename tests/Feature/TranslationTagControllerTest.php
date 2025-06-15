<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TranslationTag;
use App\Models\Translation;
use App\Models\Locale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TranslationTagControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private TranslationTag $tag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->tag = TranslationTag::factory()->create([
            'name' => 'UI Components',
            'slug' => 'ui-components',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_list_tags(): void
    {
        Sanctum::actingAs($this->user);

        TranslationTag::factory()->create(['name' => 'mobile-test', 'slug' => 'mobile-test']);
        TranslationTag::factory()->create(['name' => 'desktop-test', 'slug' => 'desktop-test']);
        TranslationTag::factory()->create(['name' => 'web-test', 'slug' => 'web-test']);
        TranslationTag::factory()->create(['name' => 'api-test', 'slug' => 'api-test']);

        $response = $this->getJson('/api/tags');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'color',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta',
            ]);

        $this->assertGreaterThanOrEqual(4, count($response->json('data')));
    }

    /** @test */
    public function it_can_list_tags_with_statistics(): void
    {
        Sanctum::actingAs($this->user);

        $locale = Locale::factory()->create();
        $translations = Translation::factory()->count(5)->create(['locale_id' => $locale->id]);
        $this->tag->translations()->attach($translations->pluck('id'));

        $response = $this->getJson('/api/tags?with_counts=true');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'translation_count',
                    ]
                ]
            ]);

        $this->assertEquals(5, $response->json('data.0.translation_count'));
    }

    /** @test */
    public function it_can_include_inactive_tags(): void
    {
        Sanctum::actingAs($this->user);

        TranslationTag::factory()->count(3)->create(['is_active' => false]);

        $response = $this->getJson('/api/tags');
        $response->assertOk()->assertJsonCount(1, 'data');

        $response = $this->getJson('/api/tags?include_inactive=true');
        $response->assertOk()->assertJsonCount(4, 'data');
    }

    /** @test */
    public function it_can_show_a_specific_tag(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/tags/{$this->tag->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'color',
                    'is_active',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $this->tag->id,
                    'name' => $this->tag->name,
                    'slug' => $this->tag->slug,
                ]
            ]);
    }

    /** @test */
    public function it_can_create_a_new_tag(): void
    {
        Sanctum::actingAs($this->user);

        $tagData = [
            'name' => 'Navigation',
            'description' => 'Navigation related translations',
            'color' => '#22C55E',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/tags', $tagData);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'color',
                    'is_active',
                ]
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Navigation',
                    'slug' => 'navigation',
                    'description' => 'Navigation related translations',
                    'color' => '#22C55E',
                    'is_active' => true,
                ]
            ]);

        $this->assertDatabaseHas('translation_tags', [
            'name' => 'Navigation',
            'slug' => 'navigation',
        ]);
    }

    /** @test */
    public function it_auto_generates_slug_from_name(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/tags', [
            'name' => 'User Interface Elements',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => 'User Interface Elements',
                    'slug' => 'user-interface-elements',
                ]
            ]);
    }

    /** @test */
    public function it_validates_tag_creation(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/tags', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $response = $this->postJson('/api/tags', [
            'name' => $this->tag->name,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $response = $this->postJson('/api/tags', [
            'name' => 'Test Tag',
            'color' => 'invalid-color',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);
    }

    /** @test */
    public function it_can_update_a_tag(): void
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'name' => 'UI Components (Updated)',
            'description' => 'Updated description',
            'color' => '#EF4444',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/tags/{$this->tag->id}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'color',
                    'is_active',
                ]
            ]);

        $this->assertDatabaseHas('translation_tags', [
            'id' => $this->tag->id,
            'name' => 'UI Components (Updated)',
            'slug' => 'ui-components-updated',
            'description' => 'Updated description',
            'color' => '#EF4444',
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_can_delete_a_tag(): void
    {
        Sanctum::actingAs($this->user);

        $tagToDelete = TranslationTag::factory()->create(['name' => 'Temporary Tag']);

        $response = $this->deleteJson("/api/tags/{$tagToDelete->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Translation tag deleted successfully.']);

        $this->assertDatabaseMissing('translation_tags', ['id' => $tagToDelete->id]);
    }

    /** @test */
    public function it_prevents_deleting_tag_with_translations(): void
    {
        Sanctum::actingAs($this->user);

        $locale = Locale::factory()->create();
        $translation = Translation::factory()->create(['locale_id' => $locale->id]);
        $this->tag->translations()->attach($translation->id);

        $response = $this->deleteJson("/api/tags/{$this->tag->id}");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Cannot delete tag that is used by translations. Please remove tag from translations first.']);

        $this->assertDatabaseHas('translation_tags', ['id' => $this->tag->id]);
    }

    /** @test */
    public function it_can_search_tags(): void
    {
        Sanctum::actingAs($this->user);

        TranslationTag::factory()->create(['name' => 'Header Components', 'description' => 'UI elements']);
        TranslationTag::factory()->create(['name' => 'Footer Items', 'description' => 'Navigation items']);
        TranslationTag::factory()->create(['name' => 'Content Blocks', 'description' => 'Main content']);

        $response = $this->getJson('/api/tags?search=header');
        $response->assertOk()->assertJsonCount(1, 'data');

        $response = $this->getJson('/api/tags?search=navigation');
        $response->assertOk()->assertJsonCount(1, 'data');

        $response = $this->getJson('/api/tags?search=nonexistent');
        $response->assertOk()->assertJsonCount(0, 'data');
    }

    /** @test */
    public function it_can_sort_tags(): void
    {
        Sanctum::actingAs($this->user);

        $timestamp = time();
        $tagA = TranslationTag::factory()->create(['name' => "Sort A {$timestamp}", 'slug' => "sort-a-{$timestamp}"]);
        $tagB = TranslationTag::factory()->create(['name' => "Sort B {$timestamp}", 'slug' => "sort-b-{$timestamp}"]);
        $tagC = TranslationTag::factory()->create(['name' => "Sort C {$timestamp}", 'slug' => "sort-c-{$timestamp}"]);

        $response = $this->getJson('/api/tags?sort_by=name&sort_direction=asc&per_page=20');
        $response->assertOk();

        $names = collect($response->json('data'))->pluck('name')->toArray();

        $posA = array_search("Sort A {$timestamp}", $names);
        $posB = array_search("Sort B {$timestamp}", $names);
        $posC = array_search("Sort C {$timestamp}", $names);

        $this->assertNotFalse($posA);
        $this->assertNotFalse($posB);
        $this->assertNotFalse($posC);
        $this->assertLessThan($posB, $posA);
        $this->assertLessThan($posC, $posB);

        $response = $this->getJson('/api/tags?sort_by=name&sort_direction=desc&per_page=20');
        $response->assertOk();

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $posA = array_search("Sort A {$timestamp}", $names);
        $posB = array_search("Sort B {$timestamp}", $names);
        $posC = array_search("Sort C {$timestamp}", $names);

        $this->assertNotFalse($posA);
        $this->assertNotFalse($posB);
        $this->assertNotFalse($posC);
        $this->assertLessThan($posB, $posC);
        $this->assertLessThan($posA, $posB);
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $response = $this->getJson('/api/tags');
        $response->assertUnauthorized();

        $response = $this->postJson('/api/tags', []);
        $response->assertUnauthorized();

        $response = $this->putJson("/api/tags/{$this->tag->id}", []);
        $response->assertUnauthorized();

        $response = $this->deleteJson("/api/tags/{$this->tag->id}");
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_handles_pagination(): void
    {
        Sanctum::actingAs($this->user);

        TranslationTag::factory()->count(25)->create(['is_active' => true]);

        $response = $this->getJson('/api/tags?per_page=10');

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

        $response = $this->getJson('/api/tags?per_page=200');
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);

        $response = $this->getJson('/api/tags?per_page=50');
        $response->assertOk();
    }

    /** @test */
    public function it_validates_color_format(): void
    {
        Sanctum::actingAs($this->user);

        $validColors = ['#FF0000', '#00FF00', '#0000FF', '#FFFFFF', '#000000'];
        foreach ($validColors as $color) {
            $response = $this->postJson('/api/tags', [
                'name' => "Tag for color {$color}",
                'color' => $color,
            ]);
            $response->assertCreated();
        }

        $invalidColors = ['FF0000', '#GGG', '#12345', 'red'];
        foreach ($invalidColors as $color) {
            $response = $this->postJson('/api/tags', [
                'name' => "Tag for invalid color {$color}",
                'color' => $color,
            ]);
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['color']);
        }
    }
}
