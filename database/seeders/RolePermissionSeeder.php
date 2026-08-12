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
            'sales.pos', 'pos.discount_override',
            'so.create', 'invoice.create', 'invoice.payment.record', 'credit_note.create', 'sales.credit_override',
            'loadout.create', 'loadout.approve',
            'loadin.create', 'loadin.approve',
            'van.sale', 'dsr.settlement.generate',
            'journal.post', 'bank.deposit', 'bank.withdraw', 'bank.transfer', 'bank.reconcile',
            'expense.record', 'collection.record', 'collection.handover', 'fiscal_period.close',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'super_admin' => $permissions,
            // grn.direct.create, grn.override, sales.credit_override, and
            // pos.discount_override are deliberately excluded: each is an
            // elevated escape hatch (bypass-a-PO, exceed-a-PO-qty,
            // exceed-a-credit-limit, exceed-a-discount-threshold), granted
            // per-user on top of this role, not a blanket default — see
            // GrnOverReceiptTest / DirectGrnTest / ConvertToInvoiceCreditLimitTest
            // / PosDiscountOverrideThresholdTest.
            'warehouse_manager' => [
                'warehouse.view', 'van.view', 'van.manage', 'product.view',
                'supplier.view', 'customer.view',
                'po.create', 'po.approve', 'grn.create', 'grn.approve',
                // van.sale here (alongside van.manage) is what lets a manager
                // record a sale on behalf of a DSR from the van-sales UI —
                // RecordVanSale hard-gates on van.sale regardless of who's acting.
                'sales.pos', 'van.sale', 'so.create', 'invoice.create', 'invoice.payment.record', 'credit_note.create',
                'loadout.approve', 'loadin.approve', 'dsr.settlement.generate',
            ],
            'dsr' => [
                'van.view', 'product.view', 'customer.view',
                'loadout.create', 'loadin.create',
                'van.sale', 'dsr.settlement.generate',
                'collection.record', 'collection.handover',
            ],
            'accountant' => [
                'supplier.view', 'customer.view',
                'journal.post', 'bank.deposit', 'bank.withdraw', 'bank.transfer', 'bank.reconcile',
                'expense.record', 'fiscal_period.close',
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
