<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Translation;
use App\Models\TranslationTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Performance Tests
 *
 * Tests that verify the application meets performance requirements:
 * - API endpoints respond in <200ms
 * - Export endpoints respond in <500ms
 * - Database queries are optimized
 */
class PerformanceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private array $locales;
    private array $tags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->createTestData();
    }

    /**
     * Create optimized test dataset
     */
    private function createTestData(): void
    {
        $this->locales = [
            Locale::factory()->create(['code' => 'en', 'name' => 'English', 'is_active' => true])->toArray(),
            Locale::factory()->create(['code' => 'es', 'name' => 'Spanish', 'is_active' => true])->toArray(),
            Locale::factory()->create(['code' => 'fr', 'name' => 'French', 'is_active' => true])->toArray(),
            Locale::factory()->create(['code' => 'de', 'name' => 'German', 'is_active' => true])->toArray(),
            Locale::factory()->create(['code' => 'it', 'name' => 'Italian', 'is_active' => true])->toArray(),
        ];

        $this->tags = TranslationTag::factory(10)->create()->toArray();

        $batchSize = 100;
        $batches = 10;

        for ($i = 0; $i < $batches; $i++) {
            $translations = [];
            for ($j = 0; $j < $batchSize; $j++) {
                $translations[] = [
                    'key' => "performance.test.{$i}.{$j}",
                    'value' => $this->faker->sentence(),
                    'locale_id' => $this->faker->randomElement(array_column($this->locales, 'id')),
                    'description' => $this->faker->optional(0.3)->sentence(),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Translation::insert($translations);
        }

        $translationIds = Translation::pluck('id')->take(500)->toArray();
        $attachments = [];

        foreach (array_slice($translationIds, 0, 200) as $translationId) {
            $tagId = $this->faker->randomElement(array_column($this->tags, 'id'));
            $attachments[] = [
                'translation_id' => $translationId,
                'translation_tag_id' => $tagId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('translation_translation_tag')->insert($attachments);
    }

    /**
     * Test translation index performance (<200ms)
     */
    public function test_translation_index_performance(): void
    {
        $startTime = microtime(true);
        $response = $this->getJson('/api/translations?per_page=20');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime,
            "Translation index response time {$responseTime}ms exceeds 200ms requirement");

        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'key', 'value', 'locale', 'tags']
            ],
            'meta'
        ]);
    }

    /**
     * Test translation search performance (<200ms)
     */
    public function test_translation_search_performance(): void
    {
        $startTime = microtime(true);
        $response = $this->getJson('/api/translations/search?q=test&per_page=20');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime,
            "Translation search response time {$responseTime}ms exceeds 200ms requirement");
    }

    /**
     * Test export locale performance (<500ms)
     */
    public function test_export_locale_performance(): void
    {
        $locale = $this->locales[0];

        $startTime = microtime(true);
        $response = $this->getJson("/api/export/locale/{$locale['code']}");
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime,
            "Export locale response time {$responseTime}ms exceeds 500ms requirement");

        $response->assertJsonStructure([
            'locale',
            'translations',
            'meta' => ['total_count', 'generated_at']
        ]);
    }

    /**
     * Test export all performance (<500ms)
     */
    public function test_export_all_performance(): void
    {
        $startTime = microtime(true);
        $response = $this->getJson('/api/export/all');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime,
            "Export all response time {$responseTime}ms exceeds 500ms requirement");
    }

    /**
     * Test database query optimization
     */
    public function test_database_query_optimization(): void
    {
        DB::enableQueryLog();

        $response = $this->getJson('/api/translations?per_page=20&with_relations=true');

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $response->assertStatus(200);

        $this->assertLessThanOrEqual(5, $queryCount,
            "Translation listing generated {$queryCount} queries, should be ≤ 5 (N+1 query problem?)");

        DB::disableQueryLog();
    }

    /**
     * Test bulk operations performance
     */
    public function test_bulk_operations_performance(): void
    {
        $translationIds = Translation::pluck('id')->take(50)->toArray();

        $startTime = microtime(true);
        $response = $this->postJson('/api/translations/bulk', [
            'action' => 'verify',
            'ids' => $translationIds,
        ]);
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime,
            "Bulk operation response time {$responseTime}ms exceeds 200ms requirement");
    }

    /**
     * Test caching effectiveness
     */
    public function test_caching_effectiveness(): void
    {
        $locale = $this->locales[0];
        $endpoint = "/api/export/locale/{$locale['code']}";

        Cache::flush();
        $startTime = microtime(true);
        $this->getJson($endpoint);
        $firstResponseTime = (microtime(true) - $startTime) * 1000;

        $startTime = microtime(true);
        $response = $this->getJson($endpoint);
        $secondResponseTime = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);

        $improvement = ($firstResponseTime - $secondResponseTime) / $firstResponseTime * 100;
        $this->assertGreaterThan(50, $improvement,
            "Cache only improved response time by {$improvement}%, should be >50%");
    }

    /**
     * Test concurrent request handling
     */
    public function test_concurrent_request_simulation(): void
    {
        $responses = [];
        $startTime = microtime(true);

        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->getJson('/api/translations?per_page=10');
        }

        $totalTime = (microtime(true) - $startTime) * 1000;
        $averageTime = $totalTime / 5;

        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        $this->assertLessThan(200, $averageTime,
            "Average response time for concurrent requests {$averageTime}ms exceeds 200ms");
    }

    /**
     * Test memory usage efficiency
     */
    public function test_memory_usage_efficiency(): void
    {
        $initialMemory = memory_get_usage(true);

        $response = $this->getJson('/api/translations?per_page=100');

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        $memoryIncreaseMB = $memoryIncrease / 1024 / 1024;

        $response->assertStatus(200);

        $this->assertLessThan(10, $memoryIncreaseMB,
            "Memory usage increased by {$memoryIncreaseMB}MB, should be <10MB");
    }

    /**
     * Test complex query performance
     */
    public function test_complex_query_performance(): void
    {
        $startTime = microtime(true);

        $response = $this->getJson('/api/translations/search?' . http_build_query([
            'q' => 'test',
            'locale' => $this->locales[0]['code'],
            'tag' => $this->tags[0]['slug'],
            'is_verified' => true,
            'per_page' => 20,
        ]));

        $responseTime = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime,
            "Complex search query response time {$responseTime}ms exceeds 200ms requirement");
    }

    /**
     * Test export with large dataset performance
     */
    public function test_export_large_dataset_performance(): void
    {
        $locale = $this->locales[0];
        $translations = [];

        for ($i = 0; $i < 500; $i++) {
            $translations[] = [
                'key' => "large.dataset.test.{$i}",
                'value' => $this->faker->sentence(),
                'locale_id' => $locale['id'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Translation::insert($translations);

        $startTime = microtime(true);
        $response = $this->getJson("/api/export/locale/{$locale['code']}");
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime,
            "Large dataset export response time {$responseTime}ms exceeds 500ms requirement");

        $data = $response->json();
        $this->assertGreaterThan(500, count($data['translations']),
            "Should have exported >500 translations");
    }
}
