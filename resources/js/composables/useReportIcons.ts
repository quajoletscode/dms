import {
    Activity,
    AlertTriangle,
    ArrowDownCircle,
    ArrowUpCircle,
    Banknote,
    BarChart3,
    Calendar,
    Clock,
    CreditCard,
    Database,
    Droplet,
    Fuel,
    Gauge,
    Landmark,
    Package,
    Percent,
    Receipt,
    ShoppingCart,
    Tag,
    Truck,
    TrendingUp,
    UserCheck,
    Users,
    Wallet,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

const reportIconMap: Record<string, LucideIcon> = {
    activity: Activity,
    'alert-triangle': AlertTriangle,
    'arrow-down-circle': ArrowDownCircle,
    'arrow-up-circle': ArrowUpCircle,
    banknote: Banknote,
    calendar: Calendar,
    clock: Clock,
    'credit-card': CreditCard,
    database: Database,
    droplet: Droplet,
    fuel: Fuel,
    gauge: Gauge,
    landmark: Landmark,
    package: Package,
    percent: Percent,
    receipt: Receipt,
    'shopping-cart': ShoppingCart,
    tag: Tag,
    truck: Truck,
    'trending-up': TrendingUp,
    'user-check': UserCheck,
    users: Users,
    wallet: Wallet,
};

export function getReportIcon(name?: string | null): LucideIcon {
    if (!name) {
        return BarChart3;
    }

    return reportIconMap[name] ?? BarChart3;
}
