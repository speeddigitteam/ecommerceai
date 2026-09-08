<?php

namespace Tests\Feature;

use App\Models\IncomeCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_income_categories(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->post(route('income-categories.store'), [
            'name' => 'Online Sales', 'description' => 'Website sales', 'is_active' => '1',
        ])->assertRedirect(route('accounting.income-categories'));

        $category = IncomeCategory::query()->firstOrFail();
        $this->actingAs($admin)->get(route('accounting.income-categories'))->assertOk()->assertSee('Online Sales');
        $this->actingAs($admin)->put(route('income-categories.update', $category), [
            'name' => 'Retail Sales', 'description' => null, 'is_active' => '0',
        ])->assertRedirect(route('accounting.income-categories'));
        $this->assertDatabaseHas('income_categories', ['name' => 'Retail Sales', 'is_active' => false]);
        $this->actingAs($admin)->delete(route('income-categories.destroy', $category))->assertRedirect(route('accounting.income-categories'));
        $this->assertDatabaseMissing('income_categories', ['id' => $category->id]);
    }
}
