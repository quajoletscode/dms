<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\MasterData\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // User::factory(10)->create();

        $admin = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $admin->assignRole('super_admin');

        $piece = Unit::query()->create(['name' => 'Piece', 'symbol' => 'pc']);
        Unit::query()->create(['name' => 'Carton', 'symbol' => 'ctn', 'base_unit_id' => $piece->id, 'conversion_factor' => 12]);
    }
}
