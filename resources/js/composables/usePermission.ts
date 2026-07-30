import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();

    const userRole = computed(() => page.props.auth.user?.role?.name ?? '');

    const isAdmin = computed(() => userRole.value === 'admin');
    const isGeneralManager = computed(
        () => userRole.value === 'general_manager',
    );
    const isStationManager = computed(
        () => userRole.value === 'station_manager',
    );
    const isAccountant = computed(() => userRole.value === 'accountant');
    // const isPumpAttendant = computed(() => userRole.value === 'attendant');

    const permissions = computed(() => page.props.auth.user?.permissions ?? []);

    // Named after Blade's @can for developer familiarity — but this checks the new
    // permission-slug system, not a Policy. Admin bypasses since its grants aren't
    // materialized as rows (see User::hasPermission()).
    function can(slug: string): boolean {
        return isAdmin.value || permissions.value.includes(slug);
    }

    function canAny(...slugs: string[]): boolean {
        return (
            isAdmin.value ||
            slugs.some((slug) => permissions.value.includes(slug))
        );
    }

    return {
        userRole,
        isAdmin,
        isGeneralManager,
        isStationManager,
        isAccountant,
        // isPumpAttendant,
        can,
        canAny,
    };
}
