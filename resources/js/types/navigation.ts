import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import { LayoutGridIcon, PackageIcon, TruckIcon, UsersIcon, WarehouseIcon } from '@lucide/vue';
import { dashboard } from '@/routes';
import { index as customersIndex } from '@/routes/customers';
import { index as productsIndex } from '@/routes/products';
import { index as suppliersIndex } from '@/routes/suppliers';
import { index as vansIndex } from '@/routes/vans';
import { index as warehousesIndex } from '@/routes/warehouses';

export interface NavItem {
    group: string;
    items: {
        name: string;
        to: NonNullable<InertiaLinkProps['href']>;
        icon?: LucideIcon;
        classNames?: string;
        iconSize?: number;
        description?: string;
        /** Permission slug required to see this item (e.g. "grn.view"). Omit for items that
         *  should always be visible to any authenticated user (dashboard, profile). */
        permission?: string;
    }[];
}

// Role-specific menus. Items are further filtered by permission at render
// time in SideNav.vue, so a single "default" menu covers every role — each
// item just disappears for users who lack its permission slug.
export const roleMenus = {
    default: (): NavItem[] => [
        {
            group: 'overview',
            items: [{ name: 'dashboard', to: dashboard().url, icon: LayoutGridIcon }],
        },
        {
            group: 'inventory',
            items: [
                { name: 'warehouses', to: warehousesIndex().url, icon: WarehouseIcon, permission: 'warehouse.view' },
                { name: 'vans', to: vansIndex().url, icon: TruckIcon, permission: 'van.view' },
                { name: 'products', to: productsIndex().url, icon: PackageIcon, permission: 'product.view' },
            ],
        },
        {
            group: 'partners',
            items: [
                { name: 'suppliers', to: suppliersIndex().url, icon: UsersIcon, permission: 'supplier.view' },
                { name: 'customers', to: customersIndex().url, icon: UsersIcon, permission: 'customer.view' },
            ],
        },
    ],
};
