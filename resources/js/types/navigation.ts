import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import {
    ClipboardListIcon,
    LayoutGridIcon,
    PackageIcon,
    ReceiptIcon,
    ShoppingCartIcon,
    TruckIcon,
    UsersIcon,
    WalletIcon,
    WarehouseIcon,
} from '@lucide/vue';
import { dashboard } from '@/routes';
import { index as customersIndex } from '@/routes/customers';
import { index as invoicesIndex } from '@/routes/invoices';
import { create as posCreate } from '@/routes/pos';
import { index as productsIndex } from '@/routes/products';
import { index as purchaseOrdersIndex } from '@/routes/purchase-orders';
import { index as suppliersIndex } from '@/routes/suppliers';
import { index as tillSessionsIndex } from '@/routes/till-sessions';
import { create as vanSaleCreate } from '@/routes/van-sales';
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
        /** OR-matched permission slugs — item shows if the user holds any one of these.
         *  Use instead of `permission` when visibility isn't gated by a single slug. */
        permissions?: string[];
    }[];
}

// Role-specific menus. Items are further filtered by permission at render
// time in SideNav.vue, so a single "default" menu covers every role — each
// item just disappears for users who lack its permission slug.
export const roleMenus = {
    default: (): NavItem[] => [
        {
            group: 'overview',
            items: [
                {
                    name: 'dashboard',
                    to: dashboard().url,
                    icon: LayoutGridIcon,
                },
            ],
        },
        {
            group: 'inventory',
            items: [
                {
                    name: 'warehouses',
                    to: warehousesIndex().url,
                    icon: WarehouseIcon,
                    permission: 'warehouse.view',
                },
                {
                    name: 'vans',
                    to: vansIndex().url,
                    icon: TruckIcon,
                    permission: 'van.view',
                },
                {
                    name: 'products',
                    to: productsIndex().url,
                    icon: PackageIcon,
                    permission: 'product.view',
                },
            ],
        },
        {
            group: 'partners',
            items: [
                {
                    name: 'suppliers',
                    to: suppliersIndex().url,
                    icon: UsersIcon,
                    permission: 'supplier.view',
                },
                {
                    name: 'customers',
                    to: customersIndex().url,
                    icon: UsersIcon,
                    permission: 'customer.view',
                },
            ],
        },
        {
            group: 'procurement',
            items: [
                {
                    name: 'purchase orders',
                    to: purchaseOrdersIndex().url,
                    icon: ClipboardListIcon,
                    permission: 'po.create',
                },
            ],
        },
        {
            group: 'sales',
            items: [
                {
                    name: 'till sessions',
                    to: tillSessionsIndex().url,
                    icon: WalletIcon,
                    permission: 'sales.pos',
                },
                {
                    name: 'pos sale',
                    to: posCreate().url,
                    icon: ShoppingCartIcon,
                    permission: 'sales.pos',
                },
                {
                    name: 'van sale',
                    to: vanSaleCreate().url,
                    icon: TruckIcon,
                    permission: 'van.sale',
                },
                {
                    name: 'invoices',
                    to: invoicesIndex().url,
                    icon: ReceiptIcon,
                    permissions: [
                        'invoice.create',
                        'invoice.payment.record',
                        'sales.pos',
                        'van.sale',
                    ],
                },
            ],
        },
    ],
};
