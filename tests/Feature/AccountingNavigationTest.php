<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_accounting_pages(): void
    {
        $this->get(route('accounting.income'))->assertRedirect(route('admin.login'));
        $this->get(route('accounting.expenses'))->assertRedirect(route('admin.login'));
        $this->get(route('accounting.reports'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_open_all_accounting_submenu_pages(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('accounting.income'))
            ->assertOk()
            ->assertSee('Accounting')
            ->assertSee('Income');

        $this->actingAs($admin)->get(route('accounting.expenses'))
            ->assertOk()
            ->assertSee('Expense');

        $this->actingAs($admin)->get(route('accounting.reports'))
            ->assertOk()
            ->assertSee('Reports');
    }
}
