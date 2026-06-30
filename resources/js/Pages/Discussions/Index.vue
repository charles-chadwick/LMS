<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { toast } from 'vue-sonner';
import { ArrowLeft, Plus, Inbox, MessagesSquare, Megaphone, Lock } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/Layouts/AppLayout.vue';
import { fromNow } from '@/lib/date.js';

const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
    discussions: {
        type: Array,
        required: true,
    },
    can: {
        type: Object,
        default: () => ({ create: false, create_announcement: false }),
    },
});

const goToCourse = () => {
    router.visit(route('courses.show', props.course.id));
};

const startDiscussion = () => {
    router.visit(route('courses.discussions.create', props.course.id));
};

const authorName = (discussion) =>
    discussion.created_by
        ? `${discussion.created_by.first_name} ${discussion.created_by.last_name}`
        : 'Unknown';

// --- Real-time reply notifications (Laravel Reverb) ---
useEcho(`courses.${props.course.id}.discussions`, 'DiscussionPostCreated', (event) => {
    toast(`${event.author_name} replied in "${event.discussion_title}".`);
    router.reload({ only: ['discussions'] });
});
</script>

<template>
  <AppLayout>
    <Head :title="`Discussions — ${course.title}`" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <!-- Back Button -->
      <div class="mb-6">
        <Button variant="outline" @click="goToCourse">
          <ArrowLeft class="w-4 h-4" />
          Back to Course
        </Button>
      </div>

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-darker-900">Discussions</h1>
            <p class="mt-2 text-sm text-darker-600">
              {{ course.title }} <span class="font-mono">({{ course.code }})</span>
            </p>
          </div>
          <Button v-if="can.create" class="px-6" @click="startDiscussion">
            <Plus class="w-4 h-4" />
            Start Discussion
          </Button>
        </div>
      </div>

      <!-- List -->
      <Card class="shadow-lg">
        <CardContent class="pt-6">
          <div v-if="discussions.length === 0" class="text-center py-12">
            <Inbox class="w-16 h-16 text-darker-300 mb-4 mx-auto" />
            <p class="text-darker-500 text-lg mb-2">No discussions yet</p>
            <p class="text-darker-400 text-sm mb-6">Be the first to start a conversation in this course.</p>
            <Button v-if="can.create" @click="startDiscussion">
              <Plus class="w-4 h-4" />
              Start the First Discussion
            </Button>
          </div>

          <ul v-else class="divide-y divide-darker-100">
            <li
                v-for="discussion in discussions"
                :key="discussion.id"
                class="flex items-center gap-4 py-4 hover:bg-darker-50 transition-colors px-2 rounded-md"
            >
              <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-primary-100 text-primary-700 shrink-0">
                <Megaphone v-if="discussion.type === 'Announcement'" class="w-5 h-5" />
                <MessagesSquare v-else class="w-5 h-5" />
              </span>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <Link
                      :href="route('courses.discussions.show', [course.id, discussion.id])"
                      class="text-primary-600 hover:text-primary-800 font-medium hover:underline truncate"
                  >
                    {{ discussion.title }}
                  </Link>
                  <Badge v-if="discussion.type === 'Announcement'" variant="secondary">Announcement</Badge>
                  <Badge v-if="discussion.status === 'Closed'" variant="outline" class="text-darker-500">
                    <Lock class="w-3 h-3" />
                    Closed
                  </Badge>
                </div>
                <p class="text-sm text-darker-500 mt-1">
                  Started by {{ authorName(discussion) }} · {{ fromNow(discussion.created_at) }}
                </p>
              </div>
              <div class="text-center shrink-0">
                <span class="inline-flex items-center justify-center min-w-8 h-8 px-2 rounded-full bg-darker-100 text-darker-700 text-sm font-semibold">
                  {{ discussion.posts_count }}
                </span>
                <p class="text-xs text-darker-400 mt-1">posts</p>
              </div>
            </li>
          </ul>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
