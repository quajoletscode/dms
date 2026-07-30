<script lang="ts" setup>
      import { Link, router, usePage } from '@inertiajs/vue3';
      import { AlignLeftIcon, BellIcon, CircleHelpIcon, LogOutIcon, UserCircleIcon } from '@lucide/vue';
      import type { HTMLAttributes } from 'vue';
      import { computed, ref } from 'vue';
      import Breadcrumb from '@/components/Breadcrumb.vue';
      import DropView from '@/components/DropView.vue';
      // import Alert from '@/components/Modal/Alert.vue';
      import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
      import UserAvatar from '@/components/UserAvatar.vue';
      import { truncateText } from '@/composables/useString';
      import { useConfirm } from '@/composables/useConfirm';
      import { dateGroupLabel, formatTime12Hour } from '@/composables/useDate';
      // import { useNotifications, type AppNotification } from '@/composables/useNotifications';
      import { useTour } from '@/composables/useTour';
      // import { tours } from '@/tours';
      // import { logout } from '@/routes';
      import Time from '@/components/Time.vue';
      import profile from '@/routes/profile';
      // import { logout } from '@/routes';
      // import { edit } from '@/routes/profile';

      const props = defineProps<{
            class?: HTMLAttributes['class'],
            library?: boolean,
            acccounts?: boolean,
            superAdmin?: boolean
      }>();

      const emits = defineEmits(['toggleMenu']);

      const page = usePage();

      // const hasMultiCampus = computed(() => page.props.auth && page.props.auth.school?.campuses?.length ? true : false);


      const isLoading = ref(false);

      const ConfirmLogout = () => {
            isLoading.value = true;
            // router.post(logout.url(), {}, {
            //       onFinish: () => isLoading.value = false,
            // });
      }

      const { Confirm } = useConfirm();

      // const { notifications, unreadCount, markAsRead, markAllAsRead } = useNotifications();

      // const notificationTab = ref<'new' | 'archive'>('new');

      // const newNotifications = computed(() => notifications.value.filter((notification) => !notification.read_at));
      // const archivedNotifications = computed(() => notifications.value.filter((notification) => notification.read_at));

      // const visibleNotifications = computed(() => (notificationTab.value === 'new' ? newNotifications.value : archivedNotifications.value));

      // // Group the visible notifications into WhatsApp-style date sections ("Today", "Yesterday", ...)
      // // while preserving their latest-first order.
      // const groupedNotifications = computed(() => {
      //       const groups: { label: string; items: AppNotification[] }[] = [];

      //       for (const notification of visibleNotifications.value) {
      //             const label = dateGroupLabel(notification.created_at);
      //             const lastGroup = groups[groups.length - 1];

      //             if (lastGroup && lastGroup.label === label) {
      //                   lastGroup.items.push(notification);
      //             } else {
      //                   groups.push({ label, items: [notification] });
      //             }
      //       }

      //       return groups;
      // });

      // const priorityDotClass = (priority: AppNotification['data']['priority']) => ({
      //       'bg-red-500': priority === 'critical',
      //       'bg-amber-500': priority === 'important',
      //       'bg-slate-400': priority === 'info',
      // });

      // const openNotification = async (notification: AppNotification) => {
      //       await markAsRead(notification);
      //       // Inertia's router to visit the deep link, which allows for SPA-like navigation without a full page reload.
      //       router.visit(notification.data.deep_link);
      // };

      const handleLogout = async () => {
            const isConfirmed = await Confirm({
                  title: 'Confirm Logout',
                  message: 'Are you sure you want to logout of the application?',
                  confirmText: 'Logout',
                  cancelText: 'Cancel',
            });

            if (isConfirmed) {
                  ConfirmLogout();
            }
      }

      // const { isActive: isTourActive, startTour, hasCompleted } = useTour();

      // // const currentPageTourId = computed(() => page.component);

      // // const currentPageTourSteps = computed(() => tours[currentPageTourId.value]);

      // const showTourNudge = computed(() => {
      //       // re-evaluated when a tour starts/ends since `hasCompleted` itself reads localStorage, not a reactive ref
      //       void isTourActive.value;

      //       return Boolean(currentPageTourSteps.value) && !hasCompleted(currentPageTourId.value);
      // });

      // const handleHelpClick = () => {
      //       if (currentPageTourSteps.value) {
      //             startTour(currentPageTourId.value, currentPageTourSteps.value);
      //             return;
      //       }

      //       router.visit('/tutorials');
      // };
</script>

<template>
      <header
        class="py-2 px-4 w-full sticky top-0 border-b border-gray-300 dark:border-gray-700 mx-auto z-40 bg-body dark:bg-slate-900 print:hidden"
        :class="props.class">
            <div class="flex items-center justify-between lg:ml-80">
                  <button class="appearance-none [-webkit-appearance:none] lg:hidden" @click="emits('toggleMenu')"
                    type="button">
                        <AlignLeftIcon />
                  </button>
                  <!-- breadcrumb -->
                  <!-- <nav class="hidden lg:flex" aria-label="breadcrumb" v-if="page.props.auth.company">
                        <ol class="breadcrumb mb-0">

                              <li class="breadcrumb-item flex flex-col">
                                    <span class="text-lg font-bold">
                                          {{ page.props.auth.company }}
                                    </span>
                                    <span class="text-sm normal-case">
                                          <template v-if="page.props.auth.station">
                                                {{ page.props.auth.station.name }} ({{ page.props.auth.station.code }})
                                          </template>
                                          <Time v-else />
                                    </span>
                              </li>
                        </ol>
                  </nav> -->

                  <div class="flex items-center  flex-row gap-6 lg:gap-8">
                        <ThemeSwitcher />
                        <!-- <button title="Help" type="button" @click="handleHelpClick"
                          class="appearance-none [-webkit-appearance:none] cursor-pointer relative">
                              <CircleHelpIcon />
                              <span v-if="showTourNudge"
                                class="absolute top-0 right-0 h-2 w-2 rounded-full bg-global-light animate-pulse" />
                        </button> -->
                        <!-- <DropView class="p-0!">
                              <template #trigger>
                                    <button title="Notifications" type="button"
                                      class="appearance-none [-webkit-appearance:none] cursor-pointer relative">
                                          <BellIcon />
                                          <span v-if="unreadCount > 0"
                                            class="bg-global-light text-white z-5 outline-0 rounded-[50%] w-5.5 h-5.5 absolute bottom-[50%] right-[-40%] p-1! font-medium flex items-center justify-center">
                                                {{ unreadCount > 9 ? '9+' : unreadCount }}
                                          </span>
                                    </button>
                              </template>
                              <div class="flex flex-col w-104 max-w-[90vw] max-h-136">
                                    <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                                          <span class="font-semibold text-sm">Notifications</span>
                                          <button v-if="notificationTab === 'new' && unreadCount > 0" type="button" title="Mark all as read"
                                            class="text-xs text-global-light hover:underline cursor-pointer"
                                            @click="markAllAsRead">
                                                mark all read
                                          </button>
                                    </div>
                                    <div class="flex items-center gap-1 px-3 pt-2 border-b border-gray-200 dark:border-gray-700">
                                          <button type="button"
                                            class="px-2.5 py-1.5 text-xs font-medium rounded-t-md cursor-pointer"
                                            :class="notificationTab === 'new' ? 'text-global-light border-b-2 border-global-light' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                            @click="notificationTab = 'new'">
                                                New{{ newNotifications.length ? ` (${newNotifications.length})` : '' }}
                                          </button>
                                          <button type="button"
                                            class="px-2.5 py-1.5 text-xs font-medium rounded-t-md cursor-pointer"
                                            :class="notificationTab === 'archive' ? 'text-global-light border-b-2 border-global-light' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                            @click="notificationTab = 'archive'">
                                                Archive
                                          </button>
                                    </div>
                                    <ul class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                                          <li v-if="visibleNotifications.length === 0" class="p-4 text-sm text-center text-gray-500">
                                                {{ notificationTab === 'new' ? "You're all caught up." : 'No archived notifications yet.' }}
                                          </li>
                                          <template v-for="group in groupedNotifications" :key="group.label">
                                                <li class="sticky top-0 z-10 flex justify-center bg-body py-1.5 dark:bg-slate-900">
                                                      <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-gray-500 dark:bg-slate-800 dark:text-gray-400">
                                                            {{ group.label }}
                                                      </span>
                                                </li>
                                                <li v-for="notification in group.items" :key="notification.id">
                                                      <button type="button"
                                                        class="flex w-full items-start gap-2 p-3 text-left hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                                                        :class="{ 'bg-slate-50 dark:bg-slate-800/50': !notification.read_at }"
                                                        @click="openNotification(notification)">
                                                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                                                              :class="priorityDotClass(notification.data.priority)" />
                                                            <span class="flex flex-1 flex-col">
                                                                  <span class="flex items-start justify-between gap-2">
                                                                        <span class="text-sm font-medium">{{ notification.data.title }}</span>
                                                                        <span class="shrink-0 text-[11px] text-gray-400">{{ formatTime12Hour(notification.created_at) }}</span>
                                                                  </span>
                                                                  <span class="text-xs text-gray-500 line-clamp-2">{{ notification.data.body }}</span>
                                                            </span>
                                                      </button>
                                                </li>
                                          </template>
                                    </ul>
                              </div>
                        </DropView> -->
                        <!-- <DropView>
                              <template #trigger>
                                    <UserAvatar :name="page.props.auth.user.name" />
                              </template>
                              <ul class="p-3 flex flex-col w-full items-start gap-x-4 space-y-2">
                                    <li v-if="page.props.auth.user" class="w-full relative">
                                          <div class="flex flex-row justify-start items-center gap-x-4">
                                                <UserAvatar :image="page.props.auth.user.avatar"
                                                  :name="page.props.auth.user.name" />
                                                <div class="flex flex-col">
                                                      <span class="text-nowrap">
                                                            {{ truncateText(page.props.auth.user.name?.toUpperCase() || page.props.auth.user.email, 20) }}
                                                      </span>
                                                      <span class="uppercase text-xs">
                                                            {{ page.props.auth.user.role.display_name ? page.props.auth.user.role.display_name : 'N/A' }}
                                                      </span>
                                                      <span v-if="page.props.auth.station" class="text-xs text-slate-500 dark:text-slate-400 text-nowrap">
                                                            {{ page.props.auth.station.name }} ({{ page.props.auth.station.code }})
                                                      </span>
                                                </div>
                                          </div>
                                    </li>
                                    <li class="relative w-full">
                                          <Link :href="profile.show().url" title="Account settings"
                                            class="flex items-center gap-4 dark:hover:bg-slate-800 p-3 rounded-md select-none hover:bg-slate-200">
                                                <UserCircleIcon class="text-body" :size="24" />
                                                <span class="capitalize text-nowrap">
                                                      account settings
                                                </span>
                                          </Link>
                                    </li>
                                    <li class="relative w-full">
                                          <button title="Log out" type="button" @click="handleLogout"
                                            class="flex items-center w-full gap-4 dark:hover:bg-slate-800 p-3 rounded-md select-none hover:bg-slate-200">
                                                <LogOutIcon class="text-body" />
                                                <span class="capitalize">
                                                      logout
                                                </span>
                                          </button>
                                    </li>
                              </ul>
                        </DropView> -->
                  </div>
                  <!-- <Alert :show="showAlert" :disable-confirm="isLoading" @close="showAlert = false"
                    @confirm="ConfirmLogout" confirm-text="Logout" title="Confirm Logout"
                    message="Confirm to logout of the application?" /> -->

            </div>
      </header>
</template>

<style scoped></style>