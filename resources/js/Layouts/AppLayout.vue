<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, Home, BookOpen, Users, MessagesSquare, User, LogOut } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'vue-sonner';
import Avatar from '@/components/Avatar.vue';

const sidebarVisible = ref(false);

const page = usePage();
const authUser = computed(() => page.props.auth?.user ?? null);
const userDisplayName = computed(() => {
    const user = authUser.value;
    return user ? `${user.first_name} ${user.last_name}` : 'Guest';
});

const allNavigationItems = [
    { label: 'Dashboard', icon: Home, route: 'dashboard' },
    { label: 'Courses', icon: BookOpen, route: 'courses.index' },
    { label: 'Users', icon: Users, route: 'users.index' },
    { label: 'Discussions', icon: MessagesSquare, route: 'discussions.index' },
];

const navigationItems = computed(() => {
    return allNavigationItems.filter((item) => {
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

const isCurrent = (routeName) => {
    try {
        return route().current(routeName + '*');
    } catch (e) {
        return false;
    }
};

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
  <div class="min-h-screen bg-darker-50">
    <Toaster position="top-right" rich-colors />

    <!-- Mobile Header -->
    <header class="lg:hidden fixed top-0 left-0 right-0 z-40 bg-white shadow-md border-b border-darker-200">
      <div class="flex items-center justify-between p-4">
        <h1 class="text-xl font-bold text-primary-700">
          LMS
        </h1>
        <Button variant="ghost" size="icon" class="lg:hidden" @click="sidebarVisible = true">
          <Menu class="w-5 h-5" />
        </Button>
      </div>
    </header>

    <!-- Desktop Sidebar -->
    <aside class="hidden lg:block fixed left-0 top-0 bottom-0 w-64 bg-primary-700 shadow-lg border-r border-primary-800 z-30">
      <div class="flex flex-col h-full">
        <!-- Logo/Title -->
        <div class="p-6 border-b border-primary-800">
          <h1 class="text-2xl font-bold text-primary-50">
            Learn
          </h1>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto p-4">
          <ul class="space-y-2">
            <li v-for="item in navigationItems" :key="item.route">
              <Link
                  :href="route(item.route)"
                  class="flex items-center gap-3 px-4 py-3 rounded-lg text-primary-50 hover:bg-primary-600 transition-colors"
                  :class="isCurrent(item.route) ? 'bg-primary-600 font-semibold' : ''"
              >
                <component :is="item.icon" class="w-5 h-5" />
                <span>{{ item.label }}</span>
              </Link>
            </li>
          </ul>
        </nav>

        <!-- User Section -->
        <div class="p-4 border-t border-primary-800">
          <div class="flex items-center gap-3 px-4 py-3">
            <Avatar v-if="authUser" :user="authUser" variant="darker" />
            <div v-else class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-600 text-primary-50">
              <User class="w-5 h-5" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-primary-50 truncate">
                {{ userDisplayName }}
              </p>
              <p class="text-xs text-primary-100 truncate">
                {{ authUser?.email }}
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
            <LogOut class="w-5 h-5" />
            <span>Logout</span>
          </Link>
        </div>
      </div>
    </aside>

    <!-- Mobile Drawer -->
    <Sheet v-model:open="sidebarVisible">
      <SheetContent side="left" class="bg-primary-700 border-primary-800 p-0 w-72">
        <SheetHeader class="p-6 border-b border-primary-800">
          <SheetTitle class="text-xl font-bold text-primary-50 text-left">
            Learning MS
          </SheetTitle>
        </SheetHeader>

        <nav class="flex-1 p-4">
          <ul class="space-y-2">
            <li v-for="item in navigationItems" :key="item.route">
              <Link
                  :href="route(item.route)"
                  class="flex items-center gap-3 px-4 py-3 rounded-lg text-primary-50 hover:bg-primary-600 transition-colors"
                  :class="isCurrent(item.route) ? 'bg-primary-600 font-semibold' : ''"
                  @click="sidebarVisible = false"
              >
                <component :is="item.icon" class="w-5 h-5" />
                <span>{{ item.label }}</span>
              </Link>
            </li>
          </ul>
        </nav>

        <div class="p-4 border-t border-primary-800 mt-auto">
          <div class="flex items-center gap-3 px-4 py-3 mb-2">
            <Avatar v-if="authUser" :user="authUser" variant="darker" />
            <div v-else class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-600 text-primary-50">
              <User class="w-5 h-5" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-primary-50 truncate">
                {{ userDisplayName }}
              </p>
              <p class="text-xs text-primary-100 truncate">
                {{ authUser?.email }}
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
            <LogOut class="w-5 h-5" />
            <span>Logout</span>
          </Link>
        </div>
      </SheetContent>
    </Sheet>

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
