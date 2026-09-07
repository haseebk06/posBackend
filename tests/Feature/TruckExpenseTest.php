<?php

namespace Tests\Feature;

use App\Models\TruckExpense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TruckExpenseTest extends TestCase {
    use RefreshDatabase;

    /**
     * @test
     * Test that we can retrieve all truck expenses
     */
    public function it_can_get_all_truck_expenses() {
        TruckExpense::create([
            'truck_number' => 'TRK-001',
            'date' => now(),
            'advance' => 5000,
            'diesel_liters' => 50,
            'diesel_rate_per_liter' => 280,
            'diesel_location' => 'PSO Pump',
        ]);

        $response = $this->getJson('/api/truck-expense/get');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.truck_number', 'TRK-001')
            ->assertJsonPath('data.0.diesel_amount', 14000); // 50 * 280
    }

    /**
     * @test
     * Test that diesel amount is calculated correctly
     */
    public function it_calculates_diesel_amount_correctly() {
        $response = $this->postJson('/api/truck-expense/add', [
            'truck_number' => 'TRK-002',
            'date' => now()->format('Y-m-d'),
            'advance' => 3000,
            'diesel_liters' => 100,
            'diesel_rate_per_liter' => 250,
            'diesel_location' => 'Shell Pump',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.diesel_amount', 25000); // 100 * 250
    }

    /**
     * @test
     * Test that spare parts cost is summed correctly
     */
    public function it_sums_spare_parts_cost_correctly() {
        $response = $this->postJson('/api/truck-expense/add', [
            'truck_number' => 'TRK-003',
            'date' => now()->format('Y-m-d'),
            'advance' => 2000,
            'spare_parts' => [
                ['name' => 'Oil Filter', 'amount' => 500],
                ['name' => 'Air Filter', 'amount' => 800],
                ['name' => 'Spark Plugs', 'amount' => 1200],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.spare_parts_cost', 2500); // 500 + 800 + 1200
    }

    /**
     * @test
     * Test that maintenance cost is summed correctly
     */
    public function it_sums_maintenance_cost_correctly() {
        $response = $this->postJson('/api/truck-expense/add', [
            'truck_number' => 'TRK-004',
            'date' => now()->format('Y-m-d'),
            'advance' => 1000,
            'maintenance_details' => [
                ['detail' => 'Oil Change', 'cost' => 2000],
                ['detail' => 'Brake Pad Replacement', 'cost' => 5000],
                ['detail' => 'Filter Service', 'cost' => 1500],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.maintenance_cost', 8500); // 2000 + 5000 + 1500
    }

    /**
     * @test
     * Test that total amount is calculated correctly (diesel + spare parts + maintenance)
     */
    public function it_calculates_total_amount_correctly() {
        $response = $this->postJson('/api/truck-expense/add', [
            'truck_number' => 'TRK-005',
            'date' => now()->format('Y-m-d'),
            'advance' => 10000,
            'diesel_liters' => 50,
            'diesel_rate_per_liter' => 280,
            'spare_parts' => [
                ['name' => 'Battery', 'amount' => 3000],
            ],
            'maintenance_details' => [
                ['detail' => 'Alignment', 'cost' => 4000],
            ],
        ]);

        $response->assertStatus(201);
        $totalExpected = (50 * 280) + 3000 + 4000; // 14000 + 3000 + 4000 = 21000
        $response->assertJsonPath('data.total_amount', 21000);
    }

    /**
     * @test
     * Test that balance is calculated correctly (total amount - advance)
     */
    public function it_calculates_balance_correctly() {
        $response = $this->postJson('/api/truck-expense/add', [
            'truck_number' => 'TRK-006',
            'date' => now()->format('Y-m-d'),
            'advance' => 5000,
            'diesel_liters' => 100,
            'diesel_rate_per_liter' => 250,
            'diesel_location' => 'Shell Pump',
        ]);

        $response->assertStatus(201);
        $totalAmount = 100 * 250; // 25000
        $advance = 5000;
        $expectedBalance = $totalAmount - $advance; // 20000
        $response->assertJsonPath('data.balance', 20000);
    }

    /**
     * @test
     * Test that truck number is required
     */
    public function it_requires_truck_number() {
        $response = $this->postJson('/api/truck-expense/add', [
            'date' => now()->format('Y-m-d'),
            'advance' => 1000,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.truck_number', ['The truck number field is required.']);
    }

    /**
     * @test
     * Test that we can update a truck expense
     */
    public function it_can_update_truck_expense() {
        $expense = TruckExpense::create([
            'truck_number' => 'TRK-007',
            'date' => now(),
            'advance' => 5000,
            'diesel_liters' => 50,
            'diesel_rate_per_liter' => 280,
            'diesel_location' => 'PSO Pump',
        ]);

        $response = $this->putJson("/api/truck-expense/update/{$expense->id}", [
            'truck_number' => 'TRK-007-UPDATED',
            'date' => now()->format('Y-m-d'),
            'advance' => 6000,
            'diesel_liters' => 60,
            'diesel_rate_per_liter' => 290,
            'diesel_location' => 'Shell Pump',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.advance', 6000)
            ->assertJsonPath('data.diesel_amount', 17400); // 60 * 290
    }

    /**
     * @test
     * Test soft delete functionality
     */
    public function it_can_soft_delete_truck_expense() {
        $expense = TruckExpense::create([
            'truck_number' => 'TRK-008',
            'date' => now(),
            'advance' => 2000,
        ]);

        $response = $this->deleteJson("/api/truck-expense/delete/{$expense->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('truck_expenses', ['id' => $expense->id]);
    }

    /**
     * @test
     * Test restoration of deleted truck expense
     */
    public function it_can_restore_deleted_truck_expense() {
        $expense = TruckExpense::create([
            'truck_number' => 'TRK-009',
            'date' => now(),
            'advance' => 1000,
        ]);

        $expense->delete();
        $this->assertSoftDeleted('truck_expenses', ['id' => $expense->id]);

        $response = $this->postJson("/api/truck-expense/restore/{$expense->id}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('truck_expenses', ['id' => $expense->id, 'deleted_at' => null]);
    }

    /**
     * @test
     * Test deletion logs retrieval
     */
    public function it_can_retrieve_deletion_logs() {
        TruckExpense::create([
            'truck_number' => 'TRK-010',
            'date' => now(),
            'advance' => 1000,
        ])->delete();

        TruckExpense::create([
            'truck_number' => 'TRK-011',
            'date' => now(),
            'advance' => 2000,
        ])->delete();

        $response = $this->getJson('/api/truck-expense/logs');

        $response->assertStatus(200)
            ->assertJsonPath('data', function ($data) {
                return count($data) === 2;
            });
    }

    /**
     * @test
     * Test complex scenario with all components
     */
    public function it_handles_complex_scenario_with_all_components() {
        $response = $this->postJson('/api/truck-expense/add', [
            'truck_number' => 'TRK-COMPLEX-01',
            'date' => now()->format('Y-m-d'),
            'advance' => 15000,
            'diesel_liters' => 75,
            'diesel_rate_per_liter' => 280,
            'diesel_location' => 'Shell Karachi',
            'spare_parts' => [
                ['name' => 'Oil Filter', 'amount' => 600],
                ['name' => 'Air Filter', 'amount' => 900],
                ['name' => 'Battery', 'amount' => 4500],
            ],
            'maintenance_details' => [
                ['detail' => 'Oil Change', 'amount' => 2500],
                ['detail' => 'Tire Rotation', 'amount' => 1800],
                ['detail' => 'Brake Service', 'amount' => 6000],
            ],
        ]);

        $response->assertStatus(201);

        $diesel = 75 * 280; // 21000
        $spareParts = 600 + 900 + 4500; // 6000
        $maintenance = 2500 + 1800 + 6000; // 10300
        $total = $diesel + $spareParts + $maintenance; // 37300
        $balance = $total - 15000; // 22300

        $response->assertJsonPath('data.diesel_amount', $diesel)
            ->assertJsonPath('data.spare_parts_cost', $spareParts)
            ->assertJsonPath('data.maintenance_cost', $maintenance)
            ->assertJsonPath('data.total_amount', $total)
            ->assertJsonPath('data.balance', $balance);
    }
}
