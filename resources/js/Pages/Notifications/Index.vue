<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    notifications: Object,
    filters: Object,
    unreadCount: Number,
});

const activeStatus = computed(() => props.filters?.status || 'all');

const setStatus = (status) => {
    router.get(route('notifications.index'), { status }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const markOneAsRead = (id) => {
    router.post(route('notifications.read', id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['notifications', 'unreadCount', 'filters'] });
        },
    });
};

const markAllAsRead = () => {
    router.post(route('notifications.read-all'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['notifications', 'unreadCount', 'filters'] });
        },
    });
};

const formatTime = (value) => {
    if (!value) return '';
    const date = new Date(value);
    return date.toLocaleString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Notifications" />

        <div class="mx-auto max-w-5xl space-y-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Notifications</h1>
                    <p class="mt-1 text-sm text-slate-500">Track your recent account activity.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="(unreadCount || 0) === 0"
                    @click="markAllAsRead"
                >
                    <Icon icon="ri:check-double-line" :width="14" :height="14" />
                    Mark all as read
                </button>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition"
                    :class="activeStatus === 'all' ? 'bg-[#07304a] text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                    @click="setStatus('all')"
                >
                    <Icon icon="ri:task-line" :width="14" :height="14" />
                    All
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition"
                    :class="activeStatus === 'unread' ? 'bg-[#07304a] text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                    @click="setStatus('unread')"
                >
                    <Icon icon="ri:mail-unread-line" :width="14" :height="14" />
                    Unread
                </button>
                <span class="ml-2 text-xs font-semibold text-slate-500">Unread: {{ unreadCount || 0 }}</span>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div v-if="!notifications?.data?.length" class="px-6 py-10 text-center text-sm text-slate-500">
                    No notifications found.
                </div>
                <div v-else class="divide-y divide-slate-100">
                    <div
                        v-for="item in notifications.data"
                        :key="item.id"
                        class="flex items-start justify-between gap-4 px-6 py-4"
                        :class="!item.is_read ? 'bg-sky-50/40' : ''"
                    >
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                <Icon :icon="item.icon || 'ri:notification-3-line'" :width="16" :height="16" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ item.title }}</p>
                                <p class="truncate text-sm text-slate-600">{{ item.message }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ formatTime(item.occurred_at) }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Link
                                v-if="item.href"
                                :href="item.href"
                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                            >
                                Open
                            </Link>
                            <button
                                v-if="!item.is_read"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-[#07304a] transition hover:bg-slate-50"
                                @click="markOneAsRead(item.id)"
                            >
                                <Icon icon="ri:check-line" :width="12" :height="12" />
                                Mark as read
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="notifications?.links?.length" class="flex flex-wrap gap-2">
                <template v-for="link in notifications.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg border px-3 py-1.5 text-xs font-medium transition"
                        :class="link.active ? 'border-[#07304a] bg-[#07304a] text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-300"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
