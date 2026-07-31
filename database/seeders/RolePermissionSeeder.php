<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permission slugs used across the app (po.create, grn.approve, ...), per
     * spec Section 3.2. RIMS-only roles (Retailer Owner/Cashier) are excluded.
     */
    public function run(): void
    {
        $permissions = [
            'warehouse.view', 'warehouse.manage',
            'van.view', 'van.manage',
            'product.view', 'product.manage',
            'supplier.view', 'supplier.manage',
            'customer.view', 'customer.manage',
            'po.create', 'po.approve',
            'grn.create', 'grn.approve', 'grn.direct.create', 'grn.override',
            'sales.pos',
            'loadout.create', 'loadout.approve',
            'loadin.create', 'loadin.approve',
            'journal.post', 'bank.deposit', 'bank.withdraw', 'bank.transfer',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'super_admin' => $permissions,
            'warehouse_manager' => [
                'warehouse.view', 'van.view', 'van.manage', 'product.view',
                'supplier.view', 'customer.view',
                'po.create', 'po.approve', 'grn.create', 'grn.approve', 'grn.direct.create', 'grn.override',
                'sales.pos', 'loadout.approve', 'loadin.approve',
            ],
            'dsr' => [
                'van.view', 'product.view', 'customer.view',
                'loadout.create', 'loadin.create',
            ],
            'accountant' => [
                'supplier.view', 'customer.view',
                'journal.post', 'bank.deposit', 'bank.withdraw', 'bank.transfer',
            ],
            'wholesale_cashier' => [
                'product.view', 'customer.view', 'sales.pos',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
