<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { toast } from 'vue-sonner';
import { ArrowLeft, Pencil, Trash2, Lock, LockOpen, Megaphone, MessagesSquare } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import ConfirmAction from '@/components/ConfirmAction.vue';
import Avatar from '@/components/Avatar.vue';
import QuillEditor from '@/components/QuillEditor.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { fromNow } from '@/lib/date.js';

const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
    discussion: {
        type: Object,
        required: true,
    },
    can: {
        type: Object,
        default: () => ({ update: false, delete: false, set_status: false, reply: false, moderate: false }),
    },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id ?? null);

const isClosed = computed(() => props.discussion.status === 'Closed');

const authorName = (record) =>
    record?.created_by
        ? `${record.created_by.first_name} ${record.created_by.last_name}`
        : 'Unknown';

const canEditPost = (post) => props.can.moderate || post.created_by_id === currentUserId.value;

// --- Reply ---
const replyForm = useForm({ content: '' });

const submitReply = () => {
    replyForm.post(route('courses.discussions.posts.store', [props.course.id, props.discussion.id]), {
        preserveScroll: true,
        onSuccess: () => replyForm.reset('content'),
    });
};

// --- Inline post editing ---
const editingPostId = ref(null);
const editForm = useForm({ content: '' });

const startEditPost = (post) => {
    editingPostId.value = post.id;
    editForm.content = post.content;
    editForm.clearErrors();
};

const cancelEditPost = () => {
    editingPostId.value = null;
    editForm.reset('content');
};

const saveEditPost = (post) => {
    editForm.put(route('courses.discussions.posts.update', [props.course.id, props.discussion.id, post.id]), {
        preserveScroll: true,
        onSuccess: () => {
            editingPostId.value = null;
        },
    });
};

const deletePost = (post) => {
    router.delete(route('courses.discussions.posts.destroy', [props.course.id, props.discussion.id, post.id]), {
        preserveScroll: true,
    });
};

// --- Discussion controls ---
const goBack = () => {
    router.visit(route('courses.discussions.index', props.course.id));
};

const editDiscussion = () => {
    router.visit(route('courses.discussions.edit', [props.course.id, props.discussion.id]));
};

const toggleStatus = () => {
    router.patch(
        route('courses.discussions.setStatus', [props.course.id, props.discussion.id]),
        { status: isClosed.value ? 'Open' : 'Closed' },
        { preserveScroll: true },
    );
};

const deleteDiscussion = () => {
    router.delete(route('courses.discussions.destroy', [props.course.id, props.discussion.id]));
};

// --- Real-time replies (Laravel Reverb) ---
useEcho(`courses.${props.course.id}.discussions`, 'DiscussionPostCreated', (event) => {
    // Ignore replies to other threads or the current user's own posts.
    if (event.discussion_id !== props.discussion.id || event.author_id === currentUserId.value) {
        return;
    }

    toast(`${event.author_name} replied to this discussion.`);
    router.reload({ only: ['discussion'] });
});
</script>

<template>
  <AppLayout>
    <Head :title="discussion.title" />

    <div class="min-h-screen bg-darker-50 py-8 px-4 sm:px-6 lg:px-8">

      <!-- Back -->
      <div class="mb-6">
        <Button variant="outline" @click="goBack">
          <ArrowLeft class="w-4 h-4" />
          Back to Discussions
        </Button>
      </div>

      <!-- Discussion header -->
      <Card class="mb-6 shadow-lg">
        <CardContent class="pt-6">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap mb-2">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-primary-100 text-primary-700 shrink-0">
                  <Megaphone v-if="discussion.type === 'Announcement'" class="w-5 h-5" />
                  <MessagesSquare v-else class="w-5 h-5" />
                </span>
                <h1 class="text-2xl font-bold text-darker-900">{{ discussion.title }}</h1>
                <Badge v-if="discussion.type === 'Announcement'" variant="secondary">Announcement</Badge>
                <Badge v-if="isClosed" variant="outline" class="text-darker-500">
                  <Lock class="w-3 h-3" />
                  Closed
                </Badge>
              </div>
              <p class="text-sm text-darker-500">
                Started by {{ authorName(discussion) }} · {{ fromNow(discussion.created_at) }}
              </p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <Button v-if="can.set_status" variant="outline" size="sm" @click="toggleStatus">
                <LockOpen v-if="isClosed" class="w-4 h-4" />
                <Lock v-else class="w-4 h-4" />
                {{ isClosed ? 'Reopen' : 'Close' }}
              </Button>
              <Button v-if="can.update" variant="outline" size="icon-sm" aria-label="Edit discussion" title="Edit" @click="editDiscussion">
                <Pencil class="w-4 h-4" />
              </Button>
              <ConfirmAction
                  v-if="can.delete"
                  title="Delete discussion?"
                  :description="`Are you sure you want to delete &quot;${discussion.title}&quot; and all its replies?`"
                  confirm-label="Delete"
                  @confirm="deleteDiscussion"
              >
                <Button variant="outline" size="icon-sm" class="text-destructive border-destructive hover:bg-destructive/10" aria-label="Delete discussion" title="Delete">
                  <Trash2 class="w-4 h-4" />
                </Button>
              </ConfirmAction>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Posts -->
      <div class="space-y-4">
        <Card v-for="post in discussion.posts" :key="post.id" class="shadow-md">
          <CardContent class="pt-6">
            <div class="flex items-start gap-4">
              <Avatar v-if="post.created_by" :user="post.created_by" size="md" />
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <div>
                    <span class="font-semibold text-darker-900">{{ authorName(post) }}</span>
                    <span class="text-sm text-darker-400 ml-2">{{ fromNow(post.created_at) }}</span>
                  </div>
                  <div v-if="canEditPost(post) && editingPostId !== post.id" class="flex items-center gap-1">
                    <Button variant="ghost" size="icon-sm" aria-label="Edit reply" title="Edit" @click="startEditPost(post)">
                      <Pencil class="w-4 h-4" />
                    </Button>
                    <ConfirmAction
                        title="Delete reply?"
                        description="Are you sure you want to delete this reply?"
                        confirm-label="Delete"
                        @confirm="deletePost(post)"
                    >
                      <Button variant="ghost" size="icon-sm" class="text-destructive hover:bg-destructive/10" aria-label="Delete reply" title="Delete">
                        <Trash2 class="w-4 h-4" />
                      </Button>
                    </ConfirmAction>
                  </div>
                </div>

                <!-- Inline edit -->
                <div v-if="editingPostId === post.id" class="mt-3">
                  <QuillEditor v-model="editForm.content" placeholder="Edit your reply..." />
                  <small v-if="editForm.errors.content" class="text-red-600 mt-1 block">{{ editForm.errors.content }}</small>
                  <div class="flex items-center justify-end gap-2 mt-3">
                    <Button variant="outline" size="sm" :disabled="editForm.processing" @click="cancelEditPost">Cancel</Button>
                    <Button size="sm" :disabled="editForm.processing" @click="saveEditPost(post)">Save</Button>
                  </div>
                </div>

                <!-- Content -->
                <div v-else class="prose prose-sm max-w-none mt-2 text-darker-700" v-html="post.content" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Reply form -->
      <Card v-if="can.reply" class="mt-6 shadow-md">
        <CardContent class="pt-6">
          <h2 class="text-lg font-semibold text-darker-900 mb-3">Add a Reply</h2>
          <form @submit.prevent="submitReply" class="space-y-3">
            <QuillEditor v-model="replyForm.content" placeholder="Write your reply..." />
            <small v-if="replyForm.errors.content" class="text-red-600 block">{{ replyForm.errors.content }}</small>
            <div class="flex justify-end">
              <Button type="submit" :disabled="replyForm.processing" class="px-6">Post Reply</Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <Card v-else-if="isClosed" class="mt-6 border-dashed">
        <CardContent class="py-6 text-center text-darker-500">
          <Lock class="w-5 h-5 mx-auto mb-2" />
          This discussion is closed. No new replies can be added.
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
