import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermission';
import { roleMenus, type NavItem } from '@/types/navigation';

export interface NavigationCommand {
    id: string;
    name: string;
    group: string;
    to: string;
    icon?: NavItem['items'][number]['icon'];
    description: string;
}

const preferredQuickActions = [
    'pos sale',
    'van sale',
    'purchase orders',
    'products',
    'customers',
    'invoices',
    'dashboard',
];

const itemDescription = (group: string, name: string): string => {
    const readableGroup = group.replaceAll('-', ' ');

    return `${readableGroup} / ${name}`;
};

const canSeeItem = (
    item: NavItem['items'][number],
    can: (slug: string) => boolean,
): boolean => {
    if (item.permission && !can(item.permission)) {
        return false;
    }

    if (
        item.permissions &&
        !item.permissions.some((permission) => can(permission))
    ) {
        return false;
    }

    return true;
};

export function useNavigationCommands() {
    const { can } = usePermissions();

    const navigationGroups = computed<NavItem[]>(() =>
        roleMenus
            .default()
            .map((group) => ({
                ...group,
                items: group.items.filter((item) => canSeeItem(item, can)),
            }))
            .filter((group) => group.items.length > 0),
    );

    const commands = computed<NavigationCommand[]>(() =>
        navigationGroups.value.flatMap((group) =>
            group.items.map((item) => ({
                id: `${group.group}:${item.name}`,
                name: item.name,
                group: group.group,
                to: String(item.to),
                icon: item.icon,
                description:
                    item.description ?? itemDescription(group.group, item.name),
            })),
        ),
    );

    const quickActions = computed<NavigationCommand[]>(() => {
        const byPreference = new Map(
            preferredQuickActions.map((name, index) => [name, index]),
        );

        return [...commands.value]
            .sort((first, second) => {
                const firstRank =
                    byPreference.get(first.name) ?? Number.MAX_SAFE_INTEGER;
                const secondRank =
                    byPreference.get(second.name) ?? Number.MAX_SAFE_INTEGER;

                if (firstRank !== secondRank) {
                    return firstRank - secondRank;
                }

                return first.name.localeCompare(second.name);
            })
            .slice(0, 5);
    });

    return {
        navigationGroups,
        commands,
        quickActions,
    };
}
