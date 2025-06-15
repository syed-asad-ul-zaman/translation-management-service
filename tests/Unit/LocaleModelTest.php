<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Locale;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes(): void
    {
        $fillable = ['code', 'name', 'native_name', 'is_active', 'is_default'];
        $locale = new Locale();

        $this->assertEquals($fillable, $locale->getFillable());
    }

    /** @test */
    public function it_casts_boolean_attributes(): void
    {
        $locale = Locale::factory()->create([
            'is_active' => 1,
            'is_default' => 0,
        ]);

        $this->assertIsBool($locale->is_active);
        $this->assertIsBool($locale->is_default);
        $this->assertTrue($locale->is_active);
        $this->assertFalse($locale->is_default);
    }

    /** @test */
    public function it_has_translations_relationship(): void
    {
        $locale = Locale::factory()->create();
        $translation = Translation::factory()->create(['locale_id' => $locale->id]);

        $this->assertTrue($locale->translations()->exists());
        $this->assertEquals($translation->id, $locale->translations->first()->id);
    }

    /** @test */
    public function it_has_active_translations_relationship(): void
    {
        $locale = Locale::factory()->create();

        Translation::factory()->create(['locale_id' => $locale->id, 'is_active' => true]);
        Translation::factory()->create(['locale_id' => $locale->id, 'is_active' => false]);

        $this->assertEquals(2, $locale->translations()->count());
        $this->assertEquals(1, $locale->activeTranslations()->count());
    }

    /** @test */
    public function it_has_active_scope(): void
    {
        Locale::factory()->create(['is_active' => true]);
        Locale::factory()->create(['is_active' => false]);

        $activeLocales = Locale::active()->get();
        $this->assertEquals(1, $activeLocales->count());
        $this->assertTrue($activeLocales->first()->is_active);
    }

    /** @test */
    public function it_has_default_scope(): void
    {
        Locale::factory()->create(['is_default' => true]);
        Locale::factory()->create(['is_default' => false]);

        $defaultLocales = Locale::default()->get();
        $this->assertEquals(1, $defaultLocales->count());
        $this->assertTrue($defaultLocales->first()->is_default);
    }

    /** @test */
    public function it_validates_unique_code(): void
    {
        Locale::factory()->create(['code' => 'en']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Locale::factory()->create(['code' => 'en']);
    }

    /** @test */
    public function it_validates_unique_default_locale(): void
    {
        $locale1 = Locale::factory()->create(['is_default' => true]);
        $locale2 = Locale::factory()->create(['is_default' => false]);

        $this->assertTrue($locale1->is_default);
        $this->assertFalse($locale2->is_default);
    }

    /** @test */
    public function it_can_find_by_code(): void
    {
        $locale = Locale::factory()->create(['code' => 'fr']);

        $found = Locale::where('code', 'fr')->first();
        $this->assertNotNull($found);
        $this->assertEquals($locale->id, $found->id);
    }

    /** @test */
    public function it_has_proper_table_name(): void
    {
        $locale = new Locale();
        $this->assertEquals('locales', $locale->getTable());
    }

    /** @test */
    public function it_uses_auto_incrementing_primary_key(): void
    {
        $locale = new Locale();
        $this->assertTrue($locale->getIncrementing());
        $this->assertEquals('int', $locale->getKeyType());
        $this->assertEquals('id', $locale->getKeyName());
    }

    /** @test */
    public function it_has_timestamps(): void
    {
        $locale = new Locale();
        $this->assertTrue($locale->usesTimestamps());
    }

    /** @test */
    public function it_sets_default_values(): void
    {
        $locale = new Locale([
            'code' => 'de',
            'name' => 'German',
        ]);

        $locale->save();
        $locale->refresh();

        $this->assertIsBool($locale->is_active);
        $this->assertIsBool($locale->is_default);
    }

    /** @test */
    public function it_can_be_converted_to_array(): void
    {
        $locale = Locale::factory()->create();
        $array = $locale->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('native_name', $array);
        $this->assertArrayHasKey('is_active', $array);
        $this->assertArrayHasKey('is_default', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    /** @test */
    public function it_can_be_converted_to_json(): void
    {
        $locale = Locale::factory()->create();
        $json = $locale->toJson();

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals($locale->id, $decoded['id']);
        $this->assertEquals($locale->code, $decoded['code']);
    }
}
