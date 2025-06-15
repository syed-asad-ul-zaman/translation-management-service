<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Translation;
use App\Models\TranslationTag;
use App\Services\CdnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestData();
    }

    protected function createTestData(): void
    {
        $en = Locale::factory()->create(['code' => 'en', 'name' => 'English', 'is_active' => true]);
        $fr = Locale::factory()->create(['code' => 'fr', 'name' => 'French', 'is_active' => true]);

        $uiTag = TranslationTag::factory()->create(['name' => 'ui', 'slug' => 'ui']);
        $apiTag = TranslationTag::factory()->create(['name' => 'api', 'slug' => 'api']);

        $translation1 = Translation::factory()->create([
            'key' => 'welcome.message',
            'value' => 'Welcome!',
            'locale_id' => $en->id,
            'is_active' => true,
        ]);

        $translation2 = Translation::factory()->create([
            'key' => 'welcome.message',
            'value' => 'Bienvenue!',
            'locale_id' => $fr->id,
            'is_active' => true,
        ]);

        $translation3 = Translation::factory()->create([
            'key' => 'app.title',
            'value' => 'My App',
            'locale_id' => $en->id,
            'is_active' => true,
        ]);

        $translation1->tags()->attach([$uiTag->id, $apiTag->id]);
        $translation2->tags()->attach([$uiTag->id]);
        $translation3->tags()->attach([$apiTag->id]);
    }

    public function test_export_locale_without_cdn(): void
    {
        Config::set('cdn.enabled', false);

        $response = $this->getJson('/api/export/locale/en');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'locale',
                'translations',
                'meta' => [
                    'total_count',
                    'active_count',
                    'last_updated',
                    'generated_at',
                    'cache_key',
                ]
            ])
            ->assertJson([
                'locale' => 'en',
            ])
            ->assertJsonMissing([
                'meta' => [
                    'cdn_url'
                ]
            ]);

        $this->assertEquals('en', $response->json('locale'));
        $this->assertArrayHasKey('welcome.message', $response->json('translations'));
        $this->assertArrayHasKey('app.title', $response->json('translations'));
    }

    public function test_export_locale_with_cdn_enabled(): void
    {
        Config::set('cdn.enabled', true);
        Config::set('cdn.base_url', 'https://cdn.example.com');

        $response = $this->getJson('/api/export/locale/en');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'locale',
                'translations',
                'meta' => [
                    'total_count',
                    'active_count',
                    'last_updated',
                    'generated_at',
                    'cache_key',
                    'cdn_url',
                ]
            ])
            ->assertJson([
                'locale' => 'en',
            ]);

        $this->assertStringContainsString('https://cdn.example.com', $response->json('meta.cdn_url'));
    }

    public function test_export_all_without_cdn(): void
    {
        Config::set('cdn.enabled', false);

        $response = $this->getJson('/api/export/all');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'locales',
                'translations',
                'meta' => [
                    'total_locales',
                    'total_translations',
                    'last_updated',
                    'generated_at',
                    'cache_key',
                ]
            ])
            ->assertJsonMissing([
                'meta' => [
                    'cdn_url'
                ]
            ]);

        $this->assertIsArray($response->json('locales'));
        $this->assertArrayHasKey('en', $response->json('translations'));
        $this->assertArrayHasKey('fr', $response->json('translations'));
    }

    public function test_export_all_with_cdn_enabled(): void
    {
        Config::set('cdn.enabled', true);
        Config::set('cdn.base_url', 'https://cdn.example.com');

        $response = $this->getJson('/api/export/all');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'locales',
                'translations',
                'meta' => [
                    'total_locales',
                    'total_translations',
                    'last_updated',
                    'generated_at',
                    'cache_key',
                    'cdn_url',
                ]
            ]);

        $this->assertStringContainsString('https://cdn.example.com', $response->json('meta.cdn_url'));
    }

    public function test_export_locale_with_tags_filter(): void
    {
        $response = $this->getJson('/api/export/locale/en?tags=ui');

        $response->assertStatus(200);

        $translations = $response->json('translations');
        $this->assertArrayHasKey('welcome.message', $translations);
    }    public function test_export_locale_with_metadata(): void
    {
        $response = $this->getJson('/api/export/locale/en?include_metadata=1');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => [
                    'total_count',
                    'active_count',
                ]
            ]);
        
        $this->assertIsInt($response->json('meta.total_count'));
        $this->assertIsInt($response->json('meta.active_count'));
    }

    public function test_export_locale_not_found(): void
    {
        $response = $this->getJson('/api/export/locale/xyz');

        $response->assertStatus(422);
    }

    public function test_export_locale_caching(): void
    {
        Cache::flush();

        $response1 = $this->getJson('/api/export/locale/en');
        $cacheKey1 = $response1->json('meta.cache_key');

        $response2 = $this->getJson('/api/export/locale/en');
        $cacheKey2 = $response2->json('meta.cache_key');

        $this->assertEquals($cacheKey1, $cacheKey2);
    }

    public function test_cdn_service_integration(): void
    {
        Config::set('cdn.enabled', true);
        Config::set('cdn.base_url', 'https://cdn.example.com');

        $cdnService = app(CdnService::class);

        $this->assertTrue($cdnService->isEnabled());

        $url = $cdnService->translationExportUrl('en');
        $this->assertStringContainsString('https://cdn.example.com', $url);
        $this->assertStringContainsString('exports/en.json', $url);
    }

    public function test_export_performance(): void
    {
        $startTime = microtime(true);

        $response = $this->getJson('/api/export/locale/en');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(500, $executionTime);
    }
}
