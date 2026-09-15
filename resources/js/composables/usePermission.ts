import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();

    const userRole = computed(() => page.props.auth.role ?? '');

    const isSuperAdmin = computed(() => userRole.value === 'super_admin');
    const isWarehouseManager = computed(
        () => userRole.value === 'warehouse_manager',
    );
    const isDsr = computed(() => userRole.value === 'dsr');
    const isAccountant = computed(() => userRole.value === 'accountant');
    const isWholesaleCashier = computed(
        () => userRole.value === 'wholesale_cashier',
    );

    const permissions = computed(() => page.props.auth.permissions ?? []);

    // Named after Blade's @can for developer familiarity — but this checks the
    // spatie/laravel-permission slug system, not a Policy directly. Super Admin
    // bypasses since its grants aren't necessarily materialized as rows.
    function can(slug: string): boolean {
        return isSuperAdmin.value || permissions.value.includes(slug);
    }

    function canAny(...slugs: string[]): boolean {
        return (
            isSuperAdmin.value ||
            slugs.some((slug) => permissions.value.includes(slug))
        );
    }

    return {
        userRole,
        isSuperAdmin,
        isWarehouseManager,
        isDsr,
        isAccountant,
        isWholesaleCashier,
        can,
        canAny,
    };
}
