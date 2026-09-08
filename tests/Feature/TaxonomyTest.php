<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaxonomyTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_create_a_category_with_a_parent(): void
    {
        Storage::fake('public');
        $parent = Category::factory()->create(['name' => 'Electronics']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Mobile Phones',
            'parent_id' => $parent->id,
            'display_type' => 'default',
            'navigation_image' => UploadedFile::fake()->image('mobile-phones.png'),
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'name' => 'Mobile Phones',
            'slug' => 'mobile-phones',
            'parent_id' => $parent->id,
        ]);

        $category = Category::query()->where('name', 'Mobile Phones')->firstOrFail();
        Storage::disk('public')->assertExists($category->navigation_image_path);
        $this->assertStringEndsWith('/mobile-phones', route('catalog.show', $category->slug));
        $this->get(route('catalog.show', $category->slug))->assertOk()->assertSee('Mobile Phones');
    }

    public function test_an_authenticated_user_can_create_a_brand_and_unit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('brands.index'))
            ->assertOk()
            ->assertSee('id="brand-slug"', false)
            ->assertSee('Leave empty to generate automatically');
        $this->actingAs($user)->post(route('brands.store'), ['name' => 'Apple'])
            ->assertRedirect(route('brands.index'));
        $this->actingAs($user)->post(route('units.store'), ['name' => 'Piece'])
            ->assertRedirect(route('units.index'));

        $this->assertDatabaseHas('brands', ['name' => 'Apple']);
        $this->assertDatabaseHas('units', ['name' => 'Piece']);
        $this->assertFalse(Schema::hasColumn('units', 'slug'));
        $brand = Brand::query()->where('name', 'Apple')->firstOrFail();
        $this->assertStringEndsWith('/apple', route('catalog.show', $brand->slug));
        $this->get(route('catalog.show', $brand->slug))->assertOk()->assertSee('Apple');
    }

    public function test_an_authenticated_user_can_view_update_and_delete_a_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);

        $this->actingAs($user)->get(route('categories.show', $category))
            ->assertStatus(301)
            ->assertRedirect(route('catalog.show', $category->slug));
        $this->actingAs($user)->put(route('categories.update', $category), [
            'name' => 'Updated Electronics',
            'slug' => 'updated-electronics',
            'display_type' => 'default',
        ])->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Updated Electronics']);
        $category->refresh();
        $this->actingAs($user)->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_edit_form_excludes_itself_and_descendants_from_parent_options(): void
    {
        $user = User::factory()->create();
        $parent = Category::factory()->create(['name' => 'Electronics']);
        $category = Category::factory()->create(['name' => 'Phones', 'parent_id' => $parent->id]);
        $child = Category::factory()->create(['name' => 'Smartphones', 'parent_id' => $category->id]);

        $this->actingAs($user)->get(route('categories.edit', $category))
            ->assertOk()
            ->assertSee('id="category-edit-form"', false)
            ->assertSee('<option value="'.$parent->id.'"', false)
            ->assertDontSee('<option value="'.$category->id.'"', false)
            ->assertDontSee('<option value="'.$child->id.'"', false);
    }

    public function test_category_cannot_be_moved_beneath_its_descendant(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Electronics']);
        $child = Category::factory()->create(['name' => 'Phones', 'parent_id' => $category->id]);

        $this->actingAs($user)->from(route('categories.edit', $category))->put(route('categories.update', $category), [
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $child->id,
            'display_type' => 'default',
        ])->assertRedirect(route('categories.edit', $category))
            ->assertSessionHasErrors('parent_id');

        $this->assertNull($category->fresh()->parent_id);
    }

    public function test_generated_category_slug_must_be_unique(): void
    {
        $user = User::factory()->create();
        Category::factory()->create([
            'name' => 'Mens Shoes',
            'slug' => 'mens-shoes',
        ]);

        $this->actingAs($user)->post(route('categories.store'), [
            'name' => "Men's Shoes",
            'display_type' => 'default',
        ])->assertRedirect()
            ->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('categories', ['name' => "Men's Shoes"]);
    }
}
