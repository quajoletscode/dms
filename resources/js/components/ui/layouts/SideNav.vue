<script setup lang="ts">
      import { Link, usePage } from '@inertiajs/vue3';
      import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
      import type { HTMLAttributes } from 'vue';
      import TextInput from '@/components/ui/inputs/TextInput.vue';
      import Time from '@/components/Time.vue';
      import { roleMenus } from '../../../types/navigation';
      import type { NavItem } from '../../../types/navigation';
      import NavLinkGroup from '@/components/NavLinkGroup.vue';
      import NavLink from '@/components/NavLink.vue';
import { SearchIcon } from '@lucide/vue';

      const page = usePage();

      const searchKey = ref('');

      const items = computed<NavItem[]>(() => {
            const permissions = page.props.auth.user?.permissions ?? [];

            return roleMenus.default();
      });

      const filteredItems = computed(() => {

            if (!searchKey.value) {
                  return items.value;
            }

            const query = searchKey.value.toLowerCase();

            return items.value
                  .map((group) => ({
                        ...group,
                        items: group.items.filter((item) => item.name.toLowerCase().includes(query)),
                  }))
                  .filter((group) => group.items.length > 0);
      });
      const props = defineProps<{
            class?: HTMLAttributes['class'],
            isOpen: boolean
      }>();

      const computedClass = computed(() => {
            return props.isOpen === true ? 'translate-x-0' : '';
      });

      const emits = defineEmits(['toggle']);

      const ul = useTemplateRef('nav-ul');


      watch(() => page.url, async () => {

            await nextTick();

            if (ul.value) {
                  const currentActiveLink = ul.value.querySelector('a.link-active');

                  if (currentActiveLink) {
                        currentActiveLink.scrollIntoView({
                              behavior: 'instant',
                              block: 'center',
                              inline: 'center',
                        })
                  }
            }
      }, {
            immediate: true, // run immediately to handle the initial page load
      });

      watch(() => props.isOpen, (open) => {
            if (open) {
                  document.body.classList.add('overflow-y-hidden')
            } else {
                  if (typeof window !== 'undefined') {
                        document.body.classList.remove('overflow-y-hidden')
                  }
            }
      }, {
            immediate: true
      });
</script>

<template>
      <Teleport to="body">
            <Transition name="fade" mode="out-in">
                  <div v-if="isOpen" @click="emits('toggle')"
                    class="h-screen w-screen z-122 fixed top-0 lg:bg-transparent lg:hidden"
                    :class="{ 'bg-slate-950/40': isOpen }">
                  </div>
            </Transition>
      </Teleport>
      <aside :class="[
            'h-full lg:z-45 p-2 w-full max-w-xs fixed top-0 z-124 dark:text-gray-100 bg-slate-50 dark:bg-slate-900 transition ease-in duration-150 border-r border-gray-300 dark:border-gray-700 print:hidden -translate-x-full lg:translate-x-0',
            computedClass,
            props.class,
      ]">
            <div class="grid h-full" style="grid-template-rows: auto auto 1fr auto">
                  <div class="sticky top-0 bg-inherit z-50 px-3">
                        <Link
                          class="text-center tracking-tight relative block my-2 text-global-light font-black text-2xl"
                          href="/">
                              DMS
                        </Link>
                  </div>
                  <div class="flex basis-full items-center justify-start p-3 group">
                        <TextInput type="search" spell-check="false" placeholder="Search navigation" v-model="searchKey"
                          class="outline-0! bg-slate-200! dark:bg-slate-800! py-0 pl-10 group rounded-md transition-colors">
                              <template #leading>
                                    <div
                                      class="absolute z-1 top-1/2 -translate-y-1/2  left-3 text-body dark:text-slate-400 select-none group-focus-within:text-global">
                                          <SearchIcon :size="20" />
                                    </div>
                              </template>
                        </TextInput>
                  </div>
                  <ul ref="nav-ul"
                    class="relative flex flex-col w-full gap-1 px-3 overflow-y-auto h-full pb-2 mb-4 smart-scroll">
                        <template v-for="group in filteredItems" :key="group.group">
                              <li class="animate-in fade-in slide-in-from-bottom-2">
                                    <NavLinkGroup :text="group.group" />
                              </li>
                              <li v-for="item in group.items" :key="item.name"
                                class="animate-in fade-in slide-in-from-bottom-2">
                                    <NavLink @toggle="emits('toggle')" :active="item.to === page.url" :name="item.name"
                                      :to="item.to" :icon="item.icon" :icon-size="item.iconSize"
                                      :class="item.classNames" />
                              </li>
                        </template>
                        <li v-if="!filteredItems.length" class="text-center mt-10">
                              No matches found for "{{ searchKey }}"
                        </li>
                  </ul>
                  <div
                    class="relative bottom-0 flex flex-col gap-2 items-start w-full inset-x-0 p-3 border-0 dark:bg-slate-800 bg-slate-100 rounded-lg">
                        <div v-if="page.props.auth.user" class="flex flex-row items-center flex-wrap gap-2">
                              <span>
                                    User:
                              </span>
                              <span class="capitalize">
                                    {{ page.props.auth.user.name ?? page.props.auth.user.username ?? page.props.auth.user.email }}
                              </span>
                        </div>
                        <!-- <div v-if="page.props.auth.user" class="flex flex-row items-center flex-wrap gap-2">
                              <span>
                                    Role:
                              </span>
                              <span class="uppercase text-sm">
                                    {{ page.props.auth.user.role.display_name ?? page.props.auth.user.role.name }}
                              </span>
                        </div> -->
                        <div class="inline-block w-full text-start relative">
                              <span class="text-xs">
                                    <Time />
                              </span>
                        </div>
                  </div>

            </div>
      </aside>
</template>