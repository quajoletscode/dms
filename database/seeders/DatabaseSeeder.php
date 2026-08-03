<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\MasterData\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * One demo login per role, all sharing the same password, so anyone can
     * log in as any role to see exactly what that role's permissions expose.
     *
     * @var list<array{name: string, email: string, role: string}>
     */
    private const DEMO_USERS = [
        ['name' => 'Ama Super Admin', 'email' => 'superadmin@example.com', 'role' => 'super_admin'],
        ['name' => 'Kojo Warehouse Manager', 'email' => 'warehouse.manager@example.com', 'role' => 'warehouse_manager'],
        ['name' => 'Yaw DSR', 'email' => 'dsr@example.com', 'role' => 'dsr'],
        ['name' => 'Efua Accountant', 'email' => 'accountant@example.com', 'role' => 'accountant'],
        ['name' => 'Kwesi Wholesale Cashier', 'email' => 'cashier@example.com', 'role' => 'wholesale_cashier'],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(ChartOfAccountSeeder::class);

        $password = Hash::make('Pass$12');

        foreach (self::DEMO_USERS as $demo) {
            $user = User::factory()->create([
                'name' => $demo['name'],
                'email' => $demo['email'],
                'password' => $password,
            ]);

            $user->assignRole($demo['role']);
        }

        $piece = Unit::query()->create(['name' => 'Piece', 'symbol' => 'pc']);
        Unit::query()->create(['name' => 'Carton', 'symbol' => 'ctn', 'base_unit_id' => $piece->id, 'conversion_factor' => 12]);
    }
}
