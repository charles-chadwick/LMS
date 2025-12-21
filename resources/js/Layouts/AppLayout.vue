<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Drawer from 'primevue/drawer';

const sidebarVisible = ref(false);

const toggleSidebar = () => {
    sidebarVisible.value = !sidebarVisible.value;
};

const allNavigationItems = [
    {
        label: 'Dashboard',
        icon: 'pi pi-home',
        route: 'dashboard',
    },
    {
        label: 'Courses',
        icon: 'pi pi-book',
        route: 'courses.index',
    },
    {
        label: 'Users',
        icon: 'pi pi-users',
        route: 'users.index',
    },
    {
        label: 'Discussions',
        icon: 'pi pi-comments',
        route: 'discussions.index',
    },
];

const navigationItems = computed(() => {
    return allNavigationItems.filter(item => {
        try {
            route(item.route);
            return true;
        } catch (e) {
            return false;
        }
    });
});

const hasLogoutRoute = computed(() => {
    try {
        route('logout');
        return true;
    } catch (e) {
        return false;
    }
});
</script>

<template>
    <div class="min-h-screen bg-darker-50">
        <!-- Mobile Header -->
        <header class="lg:hidden fixed top-0 left-0 right-0 z-40 bg-white shadow-md border-b border-darker-200">
            <div class="flex items-center justify-between p-4">
                <h1 class="text-xl font-bold text-primary-700">
                    LMS
                </h1>
                <Button
                    icon="pi pi-bars"
                    severity="secondary"
                    text
                    @click="toggleSidebar"
                    class="lg:hidden"
                />
            </div>
        </header>

        <!-- Desktop Sidebar -->
      <aside class="hidden lg:block fixed left-0 top-0 bottom-0 w-64 bg-primary-700 shadow-lg border-r border-primary-800 z-30">
        <div class="flex flex-col h-full">
          <!-- Logo/Title -->
          <div class="p-6 border-b border-primary-800">
            <h1 class="text-2xl font-bold text-primary-50">
              Learning MS
            </h1>
          </div>

          <!-- Navigation -->
          <nav class="flex-1 overflow-y-auto p-4">
            <ul class="space-y-2">
              <li
                  v-for="item in navigationItems"
                  :key="item.route"
              >
                <Link
                    :href="route(item.route)"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-primary-50 hover:bg-primary-600 transition-colors"
                    :class="[
                                    route().current(item.route + '*')
                                        ? 'bg-primary-600 font-semibold'
                                        : ''
                                ]"
                >
                  <i
                      :class="item.icon"
                      class="text-lg"
                  ></i>
                  <span>{{ item.label }}</span>
                </Link>
              </li>
            </ul>
          </nav>

          <!-- User Section -->
          <div class="p-4 border-t border-primary-800">
            <div class="flex items-center gap-3 px-4 py-3">
              <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-600 text-primary-50 font-semibold">
                <i class="pi pi-user"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-primary-50 truncate">
                  User Name
                </p>
                <p class="text-xs text-primary-100 truncate">
                  user@example.com
                </p>
              </div>
            </div>
            <Link
                v-if="hasLogoutRoute"
                :href="route('logout')"
                method="post"
                as="button"
                class="w-full mt-2 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-primary-50 hover:bg-primary-600 transition-colors"
            >
                        <i class="pi pi-sign-out"></i>
                        <span>Logout</span>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Mobile Drawer -->
        <Drawer
            v-model:visible="sidebarVisible"
            :modal="true"
            class="lg:hidden"
        >
            <template #header>
              <h2 class="text-xl font-bold text-primary-50">
                Learning MS
              </h2>
            </template>

          <nav class="flex-1">
            <ul class="space-y-2">
              <li
                  v-for="item in navigationItems"
                  :key="item.route"
              >
                <Link
                    :href="route(item.route)"
                    @click="sidebarVisible = false"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-primary-50 hover:bg-primary-600 transition-colors"
                    :class="[
                                route().current(item.route + '*')
                                    ? 'bg-primary-600 font-semibold'
                                    : ''
                            ]"
                >
                  <i
                      :class="item.icon"
                      class="text-lg"
                  ></i>
                  <span>{{ item.label }}</span>
                </Link>
              </li>
            </ul>
          </nav>

          <template #footer>
            <div class="border-t border-primary-800 pt-4">
              <div class="flex items-center gap-3 px-4 py-3 mb-2">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-600 text-primary-50 font-semibold">
                  <i class="pi pi-user"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-primary-50 truncate">
                    User Name
                  </p>
                  <p class="text-xs text-primary-100 truncate">
                    user@example.com
                  </p>
                </div>
              </div>
              <Link
                  v-if="hasLogoutRoute"
                  :href="route('logout')"
                  method="post"
                  as="button"
                  class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-primary-50 hover:bg-primary-600 transition-colors"
              >
                        <i class="pi pi-sign-out"></i>
                        <span>Logout</span>
                    </Link>
                </div>
            </template>
        </Drawer>

        <!-- Main Content Area -->
        <main class="lg:ml-64 min-h-screen pt-16 lg:pt-0">
            <div class="p-4 md:p-6 lg:p-8">
                <div class="max-w-full">
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
/* Additional responsive tweaks if needed */
</style>
