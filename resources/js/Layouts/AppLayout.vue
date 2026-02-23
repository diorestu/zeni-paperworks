<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name || 'Ava Moore');
const currentPackage = computed(() => page.props.auth?.user?.plan_name || 'Free');
const showDropdown = ref(false);
const showNotifications = ref(false);
const notifications = ref([]);
const unreadNotificationCount = ref(0);
const notificationsLoading = ref(false);
const isSidebarCollapsed = ref(false);
const showFeedbackModal = ref(false);
const feedbackSubmitted = ref(false);
const showFeedbackCta = ref(false);
const feedbackForm = useForm({
    name: page.props.auth?.user?.name || '',
    company: '',
    role: '',
    rating: 5,
    message: '',
});

const navItems = computed(() => {
    const role = page.props.auth?.user?.role;
    const isVerified = !!page.props.auth?.user?.email_verified_at;

    if (!isVerified) {
        return [
            { name: 'Dashboard', href: '/dashboard', icon: 'si:bar-chart-line' },
        ];
    }

    if (role === 'super_admin') {
        return [
            { name: 'Dashboard', href: '/dashboard', icon: 'si:bar-chart-line' },
        ];
    }

    const items = [
        { name: 'Dashboard', href: '/dashboard', icon: 'si:bar-chart-line' },
        { name: 'Invoices', href: '/invoices', icon: 'si:ballot-line' },
        { name: 'Quotations', href: '/quotations', icon: 'si:assignment-line' },
    ];

    if (role === 'admin') {
        items.push(
            { name: 'Clients', href: '/clients', icon: 'si:user-alt-line' },
            { name: 'Products', href: '/products', icon: 'si:inventory-line' },
            { name: 'Settings', href: '/settings', icon: 'si:settings-line' }
        );
    }
    
    return items;
});

const avatarUrl = computed(
    () =>
        `https://ui-avatars.com/api/?name=${encodeURIComponent(userName.value)}&background=07304a&color=fff&size=96&bold=true`
);
const isVerifiedUser = computed(() => !!page.props.auth?.user?.email_verified_at);

const isActive = (href) => page.url.startsWith(href);

const isNavigating = ref(false);
const isTransitioning = ref(false);
const sidebarWidth = computed(() => (isSidebarCollapsed.value ? 80 : 256));
const sidebarStyle = computed(() => ({ width: `${sidebarWidth.value}px` }));
const contentWrapperStyle = computed(() => ({ marginLeft: `${sidebarWidth.value}px` }));

const start = () => {
    isNavigating.value = true;
    isTransitioning.value = true;
};

let transitionTimeout = null;
const finish = () => {
    isNavigating.value = false;
    if (transitionTimeout) {
        clearTimeout(transitionTimeout);
    }
    transitionTimeout = setTimeout(() => {
        isTransitioning.value = false;
    }, 180);
};

let unregisterStart;
let unregisterFinish;
let feedbackTimer = null;
let feedbackActiveStartedAt = null;
let feedbackRemainingMs = 60000;
let notificationsPoller = null;

const feedbackStorageKey = computed(() => {
    const userId = page.props.auth?.user?.id || 'guest';
    return `feedback_submitted_${userId}`;
});

const hasSubmittedFeedback = () => localStorage.getItem(feedbackStorageKey.value) === '1';

const clearFeedbackTimer = () => {
    if (feedbackTimer) {
        clearTimeout(feedbackTimer);
        feedbackTimer = null;
    }
    feedbackActiveStartedAt = null;
};

const startFeedbackCountdown = () => {
    if (showFeedbackCta.value || hasSubmittedFeedback()) return;
    if (feedbackTimer || document.hidden || !document.hasFocus()) return;

    feedbackActiveStartedAt = Date.now();
    feedbackTimer = setTimeout(() => {
        showFeedbackCta.value = true;
        clearFeedbackTimer();
    }, feedbackRemainingMs);
};

const pauseFeedbackCountdown = () => {
    if (!feedbackTimer || feedbackActiveStartedAt === null) return;

    const elapsed = Date.now() - feedbackActiveStartedAt;
    feedbackRemainingMs = Math.max(0, feedbackRemainingMs - elapsed);
    clearFeedbackTimer();
};

const handleVisibilityOrFocusChange = () => {
    if (hasSubmittedFeedback()) {
        showFeedbackCta.value = false;
        clearFeedbackTimer();
        return;
    }

    if (!document.hidden && document.hasFocus()) {
        startFeedbackCountdown();
    } else {
        pauseFeedbackCountdown();
    }
};

onMounted(() => {
    const savedState = localStorage.getItem('sidebar-collapsed');
    isSidebarCollapsed.value = savedState === 'true';
    showFeedbackCta.value = false;
    feedbackRemainingMs = 60000;
    unregisterStart = router.on('start', start);
    unregisterFinish = router.on('finish', finish);
    document.addEventListener('click', closeDropdownHandler);
    document.addEventListener('visibilitychange', handleVisibilityOrFocusChange);
    window.addEventListener('focus', handleVisibilityOrFocusChange);
    window.addEventListener('blur', handleVisibilityOrFocusChange);
    if (!hasSubmittedFeedback()) {
        startFeedbackCountdown();
    }
    fetchNotifications({ force: true });
    notificationsPoller = setInterval(() => {
        fetchNotifications();
    }, 20000);
});

onUnmounted(() => {
    if (unregisterStart) unregisterStart();
    if (unregisterFinish) unregisterFinish();
    if (transitionTimeout) clearTimeout(transitionTimeout);
    clearFeedbackTimer();
    document.removeEventListener('click', closeDropdownHandler);
    document.removeEventListener('visibilitychange', handleVisibilityOrFocusChange);
    window.removeEventListener('focus', handleVisibilityOrFocusChange);
    window.removeEventListener('blur', handleVisibilityOrFocusChange);
    if (notificationsPoller) {
        clearInterval(notificationsPoller);
        notificationsPoller = null;
    }
});

const closeDropdownHandler = (e) => {
    const dropdown = document.getElementById('user-menu-dropdown');
    const toggle = document.getElementById('user-menu-toggle');
    const notifDropdown = document.getElementById('notif-dropdown');
    const notifToggle = document.getElementById('notif-toggle');
    if (dropdown && toggle && !dropdown.contains(e.target) && !toggle.contains(e.target)) {
        showDropdown.value = false;
    }
    if (notifDropdown && notifToggle && !notifDropdown.contains(e.target) && !notifToggle.contains(e.target)) {
        showNotifications.value = false;
    }
};

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem('sidebar-collapsed', String(isSidebarCollapsed.value));
};

const formatNotificationTime = (value) => {
    if (!value) return '';

    const date = new Date(value);
    const diffMs = Date.now() - date.getTime();
    const diffMinutes = Math.floor(diffMs / 60000);

    if (diffMinutes < 1) return 'Just now';
    if (diffMinutes < 60) return `${diffMinutes}m ago`;

    const diffHours = Math.floor(diffMinutes / 60);
    if (diffHours < 24) return `${diffHours}h ago`;

    return date.toLocaleDateString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const fetchNotifications = async ({ force = false } = {}) => {
    if (notificationsLoading.value && !force) return;

    notificationsLoading.value = true;

    try {
        const response = await fetch(route('notifications.feed'), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) return;

        const data = await response.json();
        const incoming = Array.isArray(data.notifications) ? data.notifications : [];

        notifications.value = incoming;
        unreadNotificationCount.value = Number(data.unread_count || 0);
    } catch (error) {
        // Ignore network issues; next polling cycle will retry.
    } finally {
        notificationsLoading.value = false;
    }
};

const markNotificationsAsRead = async () => {
    if (unreadNotificationCount.value <= 0) return;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        await fetch(route('notifications.read-all'), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            body: JSON.stringify({}),
            credentials: 'same-origin',
        });
        unreadNotificationCount.value = 0;
    } catch (error) {
        // Ignore and wait for next sync.
    }
};

const toggleNotifications = async () => {
    showNotifications.value = !showNotifications.value;
    if (!showNotifications.value) return;

    await fetchNotifications({ force: true });
};

const openNotification = async (item) => {
    if (!item?.id || item?.is_read) {
        showNotifications.value = false;
        return;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        await fetch(route('notifications.read', item.id), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            body: JSON.stringify({}),
            credentials: 'same-origin',
        });
    } catch (error) {
        // Ignore and continue navigation.
    } finally {
        showNotifications.value = false;
    }
};

const openFeedbackModal = () => {
    showFeedbackModal.value = true;
    feedbackSubmitted.value = false;
    feedbackForm.clearErrors();
};

const closeFeedbackModal = () => {
    showFeedbackModal.value = false;
};

const submitFeedback = () => {
    feedbackForm.post(route('feedback.store'), {
        preserveScroll: true,
        onSuccess: () => {
            localStorage.setItem(feedbackStorageKey.value, '1');
            showFeedbackCta.value = false;
            feedbackSubmitted.value = true;
            feedbackForm.reset();
            feedbackForm.name = page.props.auth?.user?.name || '';
            feedbackForm.rating = 5;
            clearFeedbackTimer();
        },
    });
};
</script>

<template>
    <div class="admin-ui min-h-screen bg-slate-50 text-slate-900 font-sans">
        <transition name="fade">
            <div v-if="isNavigating" class="fixed inset-x-0 top-0 z-50 h-1 bg-[#07304a] animate-pulse"></div>
        </transition>

        <div class="flex">
            <!-- Sidebar -->
            <aside
                :style="sidebarStyle"
                :class="[
                    'fixed inset-y-0 bg-white border-r border-slate-100 z-40 transition-all duration-300'
                ]"
            >
                <button
                    @click="toggleSidebar"
                    class="absolute top-14 -right-5 flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-[#07304a] shadow-lg hover:bg-slate-50 transition-all z-50"
                    :title="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    :aria-label="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                >
                    <Icon :icon="isSidebarCollapsed ? 'si:chevron-right-line' : 'si:chevron-left-line'" :width="20" :height="20" />
                </button>

                <div class="flex h-full flex-col px-6 py-8">
                    <!-- Brand -->
                    <Link
                        :href="route('landing')"
                        class="flex items-center gap-3 focus:outline-none"
                        :class="isSidebarCollapsed ? 'justify-center' : ''"
                        aria-label="Go to landing page"
                    >
                        <img
                            :src="isSidebarCollapsed ? '/img/logo/sq_white_rounded.png' : '/img/logo/logo_colorful.png'"
                            alt="Paperwork Logo"
                            :class="isSidebarCollapsed ? 'h-[3.125rem] w-[3.125rem] object-contain shrink-0' : 'h-[3.75rem] w-auto max-w-[180px] object-contain shrink-0'"
                        >
                    </Link>

                    <!-- Navigation -->
                    <nav class="mt-8 space-y-1.5">
                        <Link
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            class="group flex items-center rounded-lg px-4 py-3.5 text-sm font-semibold transition-all duration-200"
                            :class="[
                                isSidebarCollapsed ? 'justify-center' : 'justify-between',
                                isActive(item.href)
                                    ? 'bg-[#eef4ff] text-[#2f5ea8] border border-[#c8d9f7] shadow-none'
                                    : 'text-slate-500 hover:bg-white/70 hover:text-slate-700'
                            ]"
                        >
                            <div class="flex items-center" :class="isSidebarCollapsed ? 'justify-center' : 'gap-[0.9rem]'">
                                <Icon :icon="item.icon" :width="19" :height="19" />
                                <span v-if="!isSidebarCollapsed">{{ item.name }}</span>
                            </div>
                        </Link>
                    </nav>

                    <!-- Footer Tip -->
                    <div class="relative mt-auto">
                        <div
                            v-if="!isSidebarCollapsed"
                            class="rounded-xl p-5 text-[11px] leading-relaxed text-slate-500 border border-white/60 shadow-lg shadow-[#07304a]/10 backdrop-blur-md ring-1 ring-white/30"
                        >
                            <p class="mt-1 text-[10px] font-semibold uppercase tracking-widest text-slate-500">
                                Current: <span class="text-[#07304a]">{{ currentPackage }}</span>
                            </p>
                            <Link
                                :href="route('settings.billing')"
                                class="mt-4 block w-full rounded-xl bg-[#07304a] px-4 py-2.5 text-center text-[10px] font-bold uppercase tracking-widest text-white transition-all hover:bg-[#0a3f61]"
                            >
                                Upgrade
                            </Link>
                        </div>
                        <div v-else class="flex justify-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white border border-slate-100 text-[#07304a] shadow-sm" :title="`${currentPackage} package`">
                                <Icon icon="si:award-line" :width="20" :height="20" />
                            </div>
                        </div>

                        <button
                            v-if="showFeedbackCta && !isSidebarCollapsed"
                            type="button"
                            @click="openFeedbackModal"
                            class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-[10px] font-bold uppercase tracking-widest text-[#07304a] transition hover:bg-slate-50"
                        >
                            Feedback
                        </button>
                        <button
                            v-else-if="showFeedbackCta"
                            type="button"
                            @click="openFeedbackModal"
                            class="mt-3 mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-slate-200 bg-white text-[#07304a] transition hover:bg-slate-50"
                            title="Feedback"
                            aria-label="Feedback"
                        >
                            <Icon icon="si:chat-line" :width="18" :height="18" />
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div :style="contentWrapperStyle" class="flex-1 min-h-screen bg-slate-50 transition-all duration-300">
                    <!-- Top Header -->
                <header class="sticky top-0 z-30 flex h-20 w-full items-center justify-between bg-slate-50/85 px-10 backdrop-blur-md border-b border-slate-100">
                    <div></div>

                    <div class="flex items-center gap-6">
                        <div class="relative">
                            <button
                                id="notif-toggle"
                                @click="toggleNotifications"
                                class="relative flex h-10 w-10 items-center justify-center rounded-lg bg-slate-50 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
                                :aria-expanded="showNotifications ? 'true' : 'false'"
                                aria-haspopup="menu"
                            >
                                <Icon icon="si:notifications-line" :width="18" :height="18"  />
                                <span
                                    v-if="unreadNotificationCount > 0"
                                    class="absolute -right-1 -top-1 inline-flex min-w-[18px] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[9px] font-semibold leading-none text-white"
                                >
                                    {{ unreadNotificationCount > 99 ? '99+' : unreadNotificationCount }}
                                </span>
                            </button>
                            <transition name="scale-fade">
                                <div
                                    v-if="showNotifications"
                                    id="notif-dropdown"
                                    class="absolute right-0 mt-3 w-80 origin-top-right rounded-2xl border border-slate-100 bg-white p-3 shadow-2xl ring-1 ring-black/5 z-50"
                                >
                                    <div class="mb-1 flex items-center justify-between border-b border-slate-50 px-3 py-2">
                                        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Notifications</p>
                                        <button
                                            type="button"
                                            class="text-[10px] font-semibold text-[#07304a] hover:underline disabled:cursor-not-allowed disabled:text-slate-300"
                                            :disabled="unreadNotificationCount <= 0"
                                            @click="markNotificationsAsRead"
                                        >
                                            Mark all as read
                                        </button>
                                    </div>
                                    <div v-if="notificationsLoading" class="px-3 py-4 text-sm text-slate-500">
                                        Loading notifications...
                                    </div>
                                    <div v-else-if="notifications.length === 0" class="px-3 py-4 text-sm text-slate-500">
                                        No notifications yet.
                                    </div>
                                    <div v-else class="max-h-80 space-y-1 overflow-y-auto py-1">
                                        <Link
                                            v-for="item in notifications"
                                            :key="item.id"
                                            :href="item.href || '#'"
                                            class="flex items-start gap-3 rounded-xl px-3 py-2.5 transition hover:bg-slate-50"
                                            :class="item.is_read ? '' : 'bg-sky-50/50'"
                                            @click="openNotification(item)"
                                        >
                                            <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                                <Icon :icon="item.icon || 'si:notifications-line'" :width="14" :height="14" />
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-[12px] font-semibold text-slate-700">{{ item.title }}</span>
                                                <span class="block truncate text-[11px] text-slate-500">{{ item.message }}</span>
                                                <span class="mt-1 block text-[10px] font-medium text-slate-400">{{ formatNotificationTime(item.occurred_at) }}</span>
                                            </span>
                                        </Link>
                                    </div>
                                    <div class="mt-2 border-t border-slate-100 px-3 pt-3">
                                        <Link
                                            :href="route('notifications.index')"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-[#07304a] hover:underline"
                                            @click="showNotifications = false"
                                        >
                                            View all notifications
                                            <Icon icon="si:arrow-right-line" :width="12" :height="12" />
                                        </Link>
                                    </div>
                                </div>
                            </transition>
                        </div>
                        
                        <div class="h-8 w-[1px] bg-slate-200"></div>

                        <div class="relative">
                            <button 
                                id="user-menu-toggle"
                                @click="showDropdown = !showDropdown"
                                class="flex items-center gap-3 pl-2 transition-opacity hover:opacity-80"
                            >
                                <div class="text-right flex flex-col">
                                    <span class="text-sm font-semibold text-slate-900">{{ userName }}</span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Owner Account</span>
                                </div>
                                <div class="group relative">
                                    <div class="absolute -inset-1 rounded-full bg-gradient-to-tr from-[#07304a] to-sky-400 opacity-20 blur transition duration-300 group-hover:opacity-40"></div>
                                    <img :src="avatarUrl" alt="avatar" class="relative h-11 w-11 rounded-full border-2 border-white object-cover shadow-md" />
                                    <div class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-white shadow-sm border border-slate-100 text-slate-400">
                                        <Icon icon="si:expand-more-line" :width="10" :height="10"  />
                                    </div>
                                </div>
                            </button>

                            <!-- User Dropdown Menu -->
                            <transition name="scale-fade">
                                <div 
                                    v-if="showDropdown"
                                    id="user-menu-dropdown"
                                    class="absolute right-0 mt-3 w-56 origin-top-right rounded-2xl border border-slate-100 bg-white p-2 shadow-2xl ring-1 ring-black/5 z-50"
                                >
                                    <div class="px-3 py-2 border-b border-slate-50 mb-1">
                                        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Account Control</p>
                                    </div>
                                    <template v-if="isVerifiedUser">
                                        <Link :href="route('profile.edit')" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-[#07304a]">
                                            <Icon icon="si:user-line" :width="18" :height="18"  />
                                            My Profile
                                        </Link>
                                        <Link :href="route('settings.billing')" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-[#07304a]">
                                            <Icon icon="si:credit-card-line" :width="18" :height="18"  />
                                            Billing
                                        </Link>
                                        <Link :href="route('profile.bank-accounts.index')" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-[#07304a]">
                                            <Icon icon="si:building-line" :width="18" :height="18"  />
                                            Bank Accounts
                                        </Link>
                                        <Link :href="route('settings.reset-password')" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-[#07304a]">
                                            <Icon icon="si:shield-line" :width="18" :height="18"  />
                                            Security
                                        </Link>
                                        <div class="my-1 border-t border-slate-50"></div>
                                    </template>
                                    <p v-else class="px-3 py-2 text-[10px] font-semibold uppercase tracking-widest text-amber-600">
                                        Verify email to unlock menus
                                    </p>
                                    <Link :href="route('logout')" method="post" as="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-rose-500 transition hover:bg-rose-50">
                                        <Icon icon="si:arrow-left-line" :width="18" :height="18"  />
                                        Log out
                                    </Link>
                                </div>
                            </transition>
                        </div>
                    </div>
                </header>

                <!-- Page Page -->
                <main class="p-10">
                    <div
                        :class="[
                            'will-change-transform transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]',
                            isTransitioning ? 'translate-y-1 scale-[0.995] opacity-80' : 'translate-y-0 scale-100 opacity-100',
                        ]"
                    >
                        <slot />
                    </div>
                </main>
            </div>
        </div>

        <transition name="fade">
            <div v-if="showFeedbackModal" class="fixed inset-0 z-[70] bg-slate-900/45 p-4 sm:p-6" @click.self="closeFeedbackModal">
                <div class="mx-auto mt-12 w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Share Feedback</h3>
                            <p class="text-xs text-slate-500">Tell us about your experience using Paperwork.</p>
                        </div>
                        <button type="button" @click="closeFeedbackModal" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                            <Icon icon="si:close-line" :width="18" :height="18" />
                        </button>
                    </div>

                    <div v-if="feedbackSubmitted" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                        Thanks for your feedback. Your testimonial has been recorded.
                    </div>

                    <form v-else @submit.prevent="submitFeedback" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-widest text-slate-400">Name</label>
                                <input v-model="feedbackForm.name" type="text" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-[#07304a]" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-widest text-slate-400">Company</label>
                                <input v-model="feedbackForm.company" type="text" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-[#07304a]" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-widest text-slate-400">Role</label>
                                <input v-model="feedbackForm.role" type="text" placeholder="Finance Manager" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-[#07304a]" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-widest text-slate-400">Rating</label>
                                <select v-model="feedbackForm.rating" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-[#07304a]">
                                    <option :value="5">5 - Excellent</option>
                                    <option :value="4">4 - Good</option>
                                    <option :value="3">3 - Fair</option>
                                    <option :value="2">2 - Poor</option>
                                    <option :value="1">1 - Bad</option>
                                </select>
                            </div>
                        </div>

                        <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-widest text-slate-400">Testimonial</label>
                                <textarea v-model="feedbackForm.message" rows="4" required placeholder="Share your experience..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-[#07304a]"></textarea>
                                <p v-if="feedbackForm.errors.message" class="mt-1 text-xs font-semibold text-rose-500">{{ feedbackForm.errors.message }}</p>
                            </div>

                        <div class="flex items-center justify-end gap-2 pt-1">
                            <button type="button" @click="closeFeedbackModal" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-600 hover:bg-slate-50">
                                Cancel
                            </button>
                            <button type="submit" :disabled="feedbackForm.processing" class="rounded-xl bg-[#07304a] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-[#0a3f61] disabled:cursor-not-allowed disabled:opacity-60">
                                {{ feedbackForm.processing ? 'Submitting...' : 'Submit' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 200ms ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
