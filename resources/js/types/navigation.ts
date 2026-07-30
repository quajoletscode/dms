import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import { LayoutGridIcon } from '@lucide/vue';
import { dashboard } from '@/routes';

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

// Base menu items - reusable across roles
const baseItems = {
    dashboard: (
        to: NonNullable<InertiaLinkProps['href']>,
        icon: LucideIcon,
    ) => ({
        name: 'dashboard',
        to,
        icon,
    }),
    accountSettings: (
        to: NonNullable<InertiaLinkProps['href']>,
        icon: LucideIcon,
    ) => ({
        name: 'account settings',
        to,
        icon,
    }),
    // systemConfig: (to: NonNullable<InertiaLinkProps['href']>, icon: LucideIcon) => ({
    //     name: 'system configuration',
    //     to,
    //     icon,
    // }),
};

// Role-specific menus
export const roleMenus = {
    admin: (): NavItem[] => [
        {
            group: 'overview',
            items: [baseItems.dashboard(dashboard().url, LayoutGridIcon)],
        },
    ],

    default: (): NavItem[] => [
        {
            group: 'overview',
            items: [baseItems.dashboard(dashboard().url, LayoutGridIcon)],
        },
    ], //pump attendant and any other roles not explicitly defined
};
