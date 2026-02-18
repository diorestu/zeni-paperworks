<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name || 'Ava Moore');
const currentPackage = computed(() => page.props.auth?.user?.plan_name || 'Free');
const showDropdown = ref(false);
const showNotifications = ref(false);
const isSidebarCollapsed = ref(false);
const showFeedbackModal = ref(false);
const feedbackSubmitted = ref(false);
const feedbackForm = ref({
    name: '',
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

    const items = [
        { name: 'Dashboard', href: '/dashboard', icon: 'si:bar-chart-line' },
        { name: 'Invoices', href: '/invoices', icon: 'si:ballot-line' },
        { name: 'Quotations', href: '/quotations', icon: 'si:assignment-line' },
    ];

    if (role === 'super_admin' || role === 'admin') {
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

const start = () => (isNavigating.value = true);
const finish = () => (isNavigating.value = false);

let unregisterStart;
let unregisterFinish;

onMounted(() => {
    const savedState = localStorage.getItem('sidebar-collapsed');
    isSidebarCollapsed.value = savedState === 'true';
    unregisterStart = router.on('start', start);
    unregisterFinish = router.on('finish', finish);
    document.addEventListener('click', closeDropdownHandler);
});

onUnmounted(() => {
    if (unregisterStart) unregisterStart();
    if (unregisterFinish) unregisterFinish();
    document.removeEventListener('click', closeDropdownHandler);
});

const closeDropdownHandler = (e) => {
    const dropdown = document.getElementById('user-menu-dropdown');
    const toggle = document.getElementById('user-menu-toggle');
    const notifDropdown = document.getElementById('notif-dropdown');
    const notifToggle = document.getElementById('notif-toggle');
    if (dropdown && !dropdown.contains(e.target) && !toggle.contains(e.target)) {
        showDropdown.value = false;
    }
    if (notifDropdown && !notifDropdown.contains(e.target) && !notifToggle.contains(e.target)) {
        showNotifications.value = false;
    }
};

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem('sidebar-collapsed', String(isSidebarCollapsed.value));
};

const openFeedbackModal = () => {
    showFeedbackModal.value = true;
    feedbackSubmitted.value = false;
};

const closeFeedbackModal = () => {
    showFeedbackModal.value = false;
};

const submitFeedback = () => {
    feedbackSubmitted.value = true;
    feedbackForm.value = {
        name: '',
        company: '',
        role: '',
        rating: 5,
        message: '',
    };
};
</script>

<template>
    <div class="min-h-screen bg-white text-slate-900 font-sans">
        <transition name="fade">
            <div v-if="isNavigating" class="fixed inset-x-0 top-0 z-50 h-1 bg-[#07304a] animate-pulse"></div>
        </transition>

        <div class="flex">
            <!-- Sidebar (Now Slate 50) -->
            <aside
                :class="[
                    'fixed inset-y-0 bg-slate-50 border-r border-slate-100 z-40 transition-all duration-300',
                    isSidebarCollapsed ? 'w-20' : 'w-64'
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
                            :src="isSidebarCollapsed ? '/img/logo/favicon_blue.png' : '/img/logo/logo_text_blue.png'"
                            alt="Paperwork Logo"
                            :class="isSidebarCollapsed ? 'h-10 w-10 object-contain shrink-0' : 'h-12 w-auto max-w-[180px] object-contain shrink-0'"
                        >
                    </Link>

                    <!-- Navigation -->
                    <nav class="mt-12 space-y-1.5">
                        <Link
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            class="group flex items-center rounded-lg px-4 py-3.5 text-sm font-semibold transition-all duration-200"
                            :class="[
                                isSidebarCollapsed ? 'justify-center' : 'justify-between',
                                isActive(item.href)
                                    ? 'bg-[#07304a] text-white shadow-lg shadow-[#07304a]/20'
                                    : 'text-slate-500 hover:bg-white hover:text-slate-900 hover:shadow-sm'
                            ]"
                        >
                            <div class="flex items-center" :class="isSidebarCollapsed ? 'justify-center' : 'gap-[0.9rem]'">
                                <Icon :icon="item.icon" :width="19" :height="19" />
                                <span v-if="!isSidebarCollapsed">{{ item.name }}</span>
                            </div>
                            <span v-if="isActive(item.href) && !isSidebarCollapsed" class="h-1.5 w-1.5 rounded-full bg-white shadow-[0_0_8px_rgba(255,255,255,0.8)]"></span>
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
                                :href="route('profile.billing')"
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
                            v-if="!isSidebarCollapsed"
                            type="button"
                            @click="openFeedbackModal"
                            class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-[10px] font-bold uppercase tracking-widest text-[#07304a] transition hover:bg-slate-50"
                        >
                            Feedback
                        </button>
                        <button
                            v-else
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

            <!-- Main Content Area (Now White) -->
            <div :class="['flex-1 min-h-screen transition-all duration-300', isSidebarCollapsed ? 'ml-20' : 'ml-64']">
                    <!-- Top Header -->
                <header class="sticky top-0 z-30 flex h-20 w-full items-center justify-between bg-white/80 px-10 backdrop-blur-md border-b border-slate-100">
                    <div></div>

                    <div class="flex items-center gap-6">
                        <div class="relative">
                            <button
                                id="notif-toggle"
                                @click="showNotifications = !showNotifications"
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-50 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
                                :aria-expanded="showNotifications ? 'true' : 'false'"
                                aria-haspopup="menu"
                            >
                                <Icon icon="si:notifications-line" :width="18" :height="18"  />
                            </button>
                            <transition name="scale-fade">
                                <div
                                    v-if="showNotifications"
                                    id="notif-dropdown"
                                    class="absolute right-0 mt-3 w-72 origin-top-right rounded-2xl border border-slate-100 bg-white p-3 shadow-2xl ring-1 ring-black/5 z-50"
                                >
                                    <div class="px-3 py-2 border-b border-slate-50 mb-1">
                                        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Notifications</p>
                                    </div>
                                    <div class="px-3 py-4 text-sm text-slate-500">
                                        No new notifications.
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
                                        <Link :href="route('profile.billing')" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-[#07304a]">
                                            <Icon icon="si:credit-card-line" :width="18" :height="18"  />
                                            Billing
                                        </Link>
                                        <Link :href="route('profile.bank-accounts.index')" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-[#07304a]">
                                            <Icon icon="si:building-line" :width="18" :height="18"  />
                                            Bank Accounts
                                        </Link>
                                        <Link :href="route('profile.security')" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-[#07304a]">
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
                    <slot />
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
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-1">
                            <button type="button" @click="closeFeedbackModal" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-600 hover:bg-slate-50">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-xl bg-[#07304a] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-[#0a3f61]">
                                Submit
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
