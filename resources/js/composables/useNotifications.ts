import { usePage } from '@inertiajs/vue3';
// import { useEchoNotification } from '@laravel/echo-vue';
import { ref, watch } from 'vue';
// import { isBroadcastingConfigured } from '@/lib/broadcasting';
import notificationRoutes from '@/routes/notifications';

const POLL_INTERVAL_MS = 15_000;

export type NotificationPriority = 'critical' | 'important' | 'info';

export interface NotificationData {
    title: string;
    body: string;
    deep_link: string;
    priority: NotificationPriority;
    entity_type: string;
    entity_id: number;
}

export interface AppNotification {
    id: string;
    type: string;
    read_at: string | null;
    created_at: string;
    data: NotificationData;
}

const notifications = ref<AppNotification[]>([]);
const unreadCount = ref(0);
const isLoading = ref(false);
let initialized = false;
let pollIntervalId: ReturnType<typeof setInterval> | undefined;

interface SharedNotificationsPayload {
    notifications?: AppNotification[];
    unread_count?: number;
}

function syncNotificationsFromSharedProps(page: ReturnType<typeof usePage>) {
    const sharedPayload = page.props.notifications as
        | SharedNotificationsPayload
        | undefined;

    if (!sharedPayload) {
        return;
    }

    notifications.value = sharedPayload.notifications ?? [];
    unreadCount.value = sharedPayload.unread_count ?? 0;
}

function getXsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function fetchNotifications() {
    isLoading.value = true;

    try {
        const response = await fetch(notificationRoutes.index.url(), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return;
        }

        const json = await response.json();
        notifications.value = json.notifications ?? [];
        unreadCount.value = json.unread_count ?? 0;
    } finally {
        isLoading.value = false;
    }
}

async function markAsRead(notification: AppNotification) {
    if (notification.read_at) {
        return;
    }

    const response = await fetch(notificationRoutes.read.url(notification.id), {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-XSRF-TOKEN': getXsrfToken() },
    });

    if (!response.ok) {
        return;
    }

    notification.read_at = new Date().toISOString();
    const json = await response.json();
    unreadCount.value = json.unread_count ?? Math.max(0, unreadCount.value - 1);
}

async function markAllAsRead() {
    const response = await fetch(notificationRoutes.markAllRead.url(), {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-XSRF-TOKEN': getXsrfToken() },
    });

    if (!response.ok) {
        return;
    }

    const now = new Date().toISOString();
    notifications.value.forEach((notification) => {
        notification.read_at = notification.read_at ?? now;
    });
    unreadCount.value = 0;
}


export function useNotifications() {
    if (!initialized) {
        initialized = true;

        const page = usePage();

        // Free initial sync: whatever page is loaded already carries this shared prop.
        syncNotificationsFromSharedProps(page);
        watch(
            () => page.props.notifications,
            () => {
                syncNotificationsFromSharedProps(page);
            },
            { deep: true },
        );

        // Ongoing refresh hits the small dedicated JSON endpoint on a plain interval
        // instead of `usePoll`, which would reload the current page's own Inertia
        // route — re-running that page's full controller (including any heavy
        // queries) every tick just to refresh a notification badge. Paused while
        // the tab is in the background, same as Inertia's poll would do.
        //
        // Guard against a leaked interval: `initialized` lives in this module's
        // top-level scope, which Vite recreates on every HMR update, so without
        // this the previous interval (from before the hot-reload) would keep
        // running forever alongside a newly-registered one, stacking up more
        // duplicate pollers with every edit during a dev session.
        if (pollIntervalId !== undefined) {
            clearInterval(pollIntervalId);
        }

        pollIntervalId = setInterval(() => {
            if (document.hidden) {
                return;
            }

            fetchNotifications();
        }, POLL_INTERVAL_MS);
        // listenForRealtimeNotifications();
    }

    return {
        notifications,
        unreadCount,
        isLoading,
        fetchNotifications,
        markAsRead,
        markAllAsRead,
    };
}



// function listenForRealtimeNotifications() {
//     // Without a configured broadcaster key, instantiating the Echo connector
//     // throws synchronously and would crash whatever component called us
//     // (the header, in practice). Fall back to the periodic fetch instead.
//     if (!isBroadcastingConfigured()) {
//         return;
//     }

//     const page = usePage();
//     const userId = page.props.auth?.user?.id as number | undefined;

//     if (!userId) {
//         return;
//     }

//     try {
//         useEchoNotification<NotificationData & { id: string; type: string }>(
//             `App.Models.User.${userId}`,
//             (payload) => {
//                 notifications.value.unshift({
//                     id: payload.id,
//                     type: payload.type,
//                     read_at: null,
//                     created_at: new Date().toISOString(),
//                     data: {
//                         title: payload.title,
//                         body: payload.body,
//                         deep_link: payload.deep_link,
//                         priority: payload.priority,
//                         entity_type: payload.entity_type,
//                         entity_id: payload.entity_id,
//                     },
//                 });
//                 unreadCount.value += 1;
//             },
//         );
//     } catch (error) {
//         console.error('Failed to subscribe to realtime notifications:', error);
//     }
// }

// Belt-and-braces HMR cleanup: explicitly stop the poller and reset the
// singleton guard right before Vite swaps this module out, rather than
// relying only on the next useNotifications() call to notice and clear it.
if (import.meta.hot) {
    import.meta.hot.dispose(() => {
        if (pollIntervalId !== undefined) {
            clearInterval(pollIntervalId);
            pollIntervalId = undefined;
        }

        initialized = false;
    });
}