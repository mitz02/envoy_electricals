<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseListingTest extends TestCase
{
    use RefreshDatabase;

    private int $expenseSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_expenses_follow_the_selected_store_and_support_filters(): void
    {
        $owner = $this->owner();
        $firstStore = $this->store('EXP-A', 'Expense Store A');
        $secondStore = $this->store('EXP-B', 'Expense Store B');
        $transport = $this->category('Transport');
        $office = $this->category('Office');

        $matching = $this->expense($firstStore, $transport, 'Delivery fuel', 750, 'recorded', now()->subDays(2)->toDateString());
        $otherFirstStoreExpense = $this->expense($firstStore, $office, 'Office supplies', 250, 'recorded', now()->subDays(3)->toDateString());
        $voidExpense = $this->expense($firstStore, $transport, 'Delivery void', 900, 'void', now()->subDay()->toDateString());
        $secondStoreExpense = $this->expense($secondStore, $transport, 'Delivery fuel elsewhere', 400, 'recorded', now()->subDays(2)->toDateString());

        $this->withSession(['admin_store_id' => $firstStore->id])
            ->actingAs($owner)
            ->get(route('admin.expenses.index', [
                'search' => 'Delivery',
                'category_id' => $transport->id,
                'status' => 'recorded',
                'from' => now()->subMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Expenses/Index')
                ->where('selectedStoreId', $firstStore->id)
                ->where('result_count', 1)
                ->where('month_total', 1000)
                ->where('filters.search', 'Delivery')
                ->where('filters.status', 'recorded')
                ->where('expenses.total', 1)
                ->where('expenses.data.0.id', $matching->id)
                ->where('expenses.data.0.store_id', $firstStore->id)
            );

        $this->withSession(['admin_store_id' => $secondStore->id])
            ->actingAs($owner)
            ->get(route('admin.expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedStoreId', $secondStore->id)
                ->where('result_count', 1)
                ->where('month_total', 400)
                ->where('expenses.total', 1)
                ->where('expenses.data.0.id', $secondStoreExpense->id)
            );

        $this->assertDatabaseHas('expenses', ['id' => $otherFirstStoreExpense->id]);
        $this->assertDatabaseHas('expenses', ['id' => $voidExpense->id]);
    }

    public function test_expenses_are_paginated(): void
    {
        $owner = $this->owner();
        $store = $this->store('EXP-PAGE', 'Expense Pagination Store');
        $category = $this->category('Recurring');

        foreach (range(1, 16) as $index) {
            $this->expense($store, $category, "Recurring expense {$index}", $index, 'recorded', now()->toDateString());
        }

        $this->withSession(['admin_store_id' => $store->id])
            ->actingAs($owner)
            ->get(route('admin.expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('expenses.total', 16)
                ->where('expenses.per_page', 15)
                ->where('expenses.current_page', 1)
                ->where('expenses.last_page', 2)
                ->has('expenses.data', 15)
            );
    }

    private function owner(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->firstOrFail()->id,
        ]);
    }

    private function store(string $code, string $name): Store
    {
        return Store::create([
            'name' => $name,
            'code' => $code,
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    private function category(string $name): ExpenseCategory
    {
        return ExpenseCategory::create([
            'name' => $name,
            'slug' => str($name)->slug()->append('-'.uniqid())->value(),
        ]);
    }

    private function expense(
        Store $store,
        ExpenseCategory $category,
        string $description,
        float $amount,
        string $status,
        string $date,
    ): Expense {
        return Expense::create([
            'ref_id' => 'EXP-'.str_pad((string) ++$this->expenseSequence, 6, '0', STR_PAD_LEFT),
            'expense_date' => $date,
            'expense_category_id' => $category->id,
            'description' => $description,
            'amount' => $amount,
            'payment_method' => 'cash',
            'store_id' => $store->id,
            'status' => $status,
        ]);
    }
}
