<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\TranslationTag;
use App\Models\Translation;
use App\Models\Locale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TranslationTagModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes(): void
    {
        $fillable = ['name', 'slug', 'description', 'color', 'is_active'];
        $tag = new TranslationTag();

        $this->assertEquals($fillable, $tag->getFillable());
    }

    /** @test */
    public function it_casts_boolean_attributes(): void
    {
        $tag = TranslationTag::factory()->create([
            'is_active' => 1,
        ]);

        $this->assertIsBool($tag->is_active);
        $this->assertTrue($tag->is_active);
    }

    /** @test */
    public function it_has_translations_relationship(): void
    {
        $locale = Locale::factory()->create();
        $tag = TranslationTag::factory()->create();
        $translation = Translation::factory()->create(['locale_id' => $locale->id]);

        $tag->translations()->attach($translation->id);

        $this->assertTrue($tag->translations()->exists());
        $this->assertEquals($translation->id, $tag->translations->first()->id);
    }

    /** @test */
    public function it_has_active_scope(): void
    {
        TranslationTag::factory()->create(['is_active' => true]);
        TranslationTag::factory()->create(['is_active' => false]);

        $activeTags = TranslationTag::active()->get();
        $this->assertEquals(1, $activeTags->count());
        $this->assertTrue($activeTags->first()->is_active);
    }

    /** @test */
    public function it_auto_generates_slug_from_name(): void
    {
        $tag = new TranslationTag([
            'name' => 'UI Components & Navigation',
            'is_active' => true,
        ]);

        $tag->slug = Str::slug($tag->name);
        $tag->save();

        $this->assertEquals('ui-components-navigation', $tag->slug);
    }

    /** @test */
    public function it_validates_unique_name(): void
    {
        TranslationTag::factory()->create(['name' => 'Unique Tag']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        TranslationTag::factory()->create(['name' => 'Unique Tag']);
    }

    /** @test */
    public function it_validates_unique_slug(): void
    {
        TranslationTag::factory()->create(['slug' => 'unique-slug']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        TranslationTag::factory()->create(['slug' => 'unique-slug']);
    }

    /** @test */
    public function it_can_find_by_slug(): void
    {
        $tag = TranslationTag::factory()->create(['slug' => 'test-slug']);

        $found = TranslationTag::where('slug', 'test-slug')->first();
        $this->assertNotNull($found);
        $this->assertEquals($tag->id, $found->id);
    }

    /** @test */
    public function it_has_proper_table_name(): void
    {
        $tag = new TranslationTag();
        $this->assertEquals('translation_tags', $tag->getTable());
    }

    /** @test */
    public function it_uses_auto_incrementing_primary_key(): void
    {
        $tag = new TranslationTag();
        $this->assertTrue($tag->getIncrementing());
        $this->assertEquals('int', $tag->getKeyType());
        $this->assertEquals('id', $tag->getKeyName());
    }

    /** @test */
    public function it_has_timestamps(): void
    {
        $tag = new TranslationTag();
        $this->assertTrue($tag->usesTimestamps());
    }

    /** @test */
    public function it_sets_default_values(): void
    {
        $tag = new TranslationTag([
            'name' => 'Test Tag',
            'slug' => 'test-tag',
        ]);

        $tag->save();
        $tag->refresh();

        $this->assertIsBool($tag->is_active);
    }

    /** @test */
    public function it_validates_color_format(): void
    {
        $tag = TranslationTag::factory()->create(['color' => '#FF0000']);
        $this->assertEquals('#FF0000', $tag->color);

        $validColors = ['#000000', '#FFFFFF', '#123456', '#ABCDEF'];

        foreach ($validColors as $color) {
            $tag = TranslationTag::factory()->create(['color' => $color]);
            $this->assertEquals($color, $tag->color);
        }
    }

    /** @test */
    public function it_can_be_converted_to_array(): void
    {
        $tag = TranslationTag::factory()->create();
        $array = $tag->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('color', $array);
        $this->assertArrayHasKey('is_active', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    /** @test */
    public function it_can_be_converted_to_json(): void
    {
        $tag = TranslationTag::factory()->create();
        $json = $tag->toJson();

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals($tag->id, $decoded['id']);
        $this->assertEquals($tag->name, $decoded['name']);
        $this->assertEquals($tag->slug, $decoded['slug']);
    }

    /** @test */
    public function it_can_scope_by_name(): void
    {
        $tag1 = TranslationTag::factory()->create(['name' => 'UI Components']);
        $tag2 = TranslationTag::factory()->create(['name' => 'Navigation']);
        $tag3 = TranslationTag::factory()->create(['name' => 'Form Elements']);

        $result = TranslationTag::where('name', 'like', '%UI%')->get();
        $this->assertEquals(1, $result->count());
        $this->assertEquals($tag1->id, $result->first()->id);
    }

    /** @test */
    public function it_maintains_referential_integrity_with_translations(): void
    {
        $locale = Locale::factory()->create();
        $tag = TranslationTag::factory()->create();
        $translation = Translation::factory()->create(['locale_id' => $locale->id]);

        $tag->translations()->attach($translation->id);
        $this->assertEquals(1, $tag->translations()->count());

        $translation->delete();
        $tag->refresh();
        $this->assertEquals(0, $tag->translations()->count());
    }

    /** @test */
    public function it_can_have_multiple_translations(): void
    {
        $locale = Locale::factory()->create();
        $tag = TranslationTag::factory()->create();
        $translations = Translation::factory()->count(5)->create(['locale_id' => $locale->id]);

        $tag->translations()->attach($translations->pluck('id'));

        $this->assertEquals(5, $tag->translations()->count());
    }

    /** @test */
    public function it_can_detach_all_translations(): void
    {
        $locale = Locale::factory()->create();
        $tag = TranslationTag::factory()->create();
        $translations = Translation::factory()->count(3)->create(['locale_id' => $locale->id]);

        $tag->translations()->attach($translations->pluck('id'));
        $this->assertEquals(3, $tag->translations()->count());

        $tag->translations()->detach();
        $this->assertEquals(0, $tag->translations()->count());
    }

    /** @test */
    public function it_handles_description_as_nullable(): void
    {
        $tag = TranslationTag::factory()->create(['description' => null]);
        $this->assertNull($tag->description);

        $tag = TranslationTag::factory()->create(['description' => 'Test description']);
        $this->assertEquals('Test description', $tag->description);
    }
}
