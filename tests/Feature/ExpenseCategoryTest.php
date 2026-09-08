<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_manage_expense_categories(): void
    {
        $this->post(route('expense-categories.store'), [])->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_and_create_expense_categories(): void
    {
        $admin = User::factory()->create();
        $existingCategory = ExpenseCategory::factory()->create(['name' => 'Office Rent']);

        $this->actingAs($admin)->get(route('accounting.expense-categories'))
            ->assertOk()
            ->assertSee('Expense Categories')
            ->assertSee('Add Category')
            ->assertSee($existingCategory->name);

        $this->actingAs($admin)->post(route('expense-categories.store'), [
            'name' => 'Marketing',
            'description' => 'Advertising and promotions',
            'is_active' => '1',
        ])->assertRedirect(route('accounting.expense-categories'));

        $this->assertDatabaseHas('expense_categories', [
            'name' => 'Marketing',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_and_delete_an_expense_category(): void
    {
        $admin = User::factory()->create();
        $category = ExpenseCategory::factory()->create();

        $this->actingAs($admin)->put(route('expense-categories.update', $category), [
            'name' => 'Updated Category',
            'description' => 'Updated description',
            'is_active' => '0',
        ])->assertRedirect(route('accounting.expense-categories'));

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->delete(route('expense-categories.destroy', $category))
            ->assertRedirect(route('accounting.expense-categories'));

        $this->assertDatabaseMissing('expense_categories', ['id' => $category->id]);
    }

    public function test_expense_category_name_must_be_unique(): void
    {
        $admin = User::factory()->create();
        ExpenseCategory::factory()->create(['name' => 'Utilities']);

        $this->actingAs($admin)->post(route('expense-categories.store'), [
            'name' => 'Utilities',
            'description' => '',
            'is_active' => '1',
        ])->assertSessionHasErrors('name');
    }
}
