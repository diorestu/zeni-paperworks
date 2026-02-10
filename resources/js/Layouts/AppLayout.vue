<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name || 'Ava Moore');
const showDropdown = ref(false);
const showNotifications = ref(false);

const navItems = computed(() => {
    const role = page.props.auth?.user?.role;
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
        `https://ui-avatars.com/api/?name=${encodeURIComponent(userName.value)}&background=023e8a&color=fff&size=96&bold=true`
);

const isActive = (href) => page.url.startsWith(href);

const isNavigating = ref(false);

const start = () => (isNavigating.value = true);
const finish = () => (isNavigating.value = false);

let unregisterStart;
let unregisterFinish;

onMounted(() => {
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
</script>

<template>
    <div class="min-h-screen bg-white text-slate-900 font-sans">
        <transition name="fade">
            <div v-if="isNavigating" class="fixed inset-x-0 top-0 z-50 h-1 bg-[#023e8a] animate-pulse"></div>
        </transition>

        <div class="flex">
            <!-- Sidebar (Now Slate 50) -->
            <aside class="fixed inset-y-0 w-64 bg-slate-50 border-r border-slate-100 z-20 transition-all duration-300">
                <div class="flex h-full flex-col px-6 py-8">
                    <!-- Brand -->
                    <div class="flex items-center gap-3">
                        <img src="/img/logo/logo_text_blue.png" alt="Paperwork Logo" class="h-12 w-auto max-w-[180px] object-contain shrink-0">
                    </div>

                    <!-- Navigation -->
                    <nav class="mt-12 space-y-1.5">
                        <Link
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            class="group flex items-center justify-between rounded-lg px-4 py-3.5 text-sm font-semibold transition-all duration-200"
                            :class="isActive(item.href) 
                                ? 'bg-[#023e8a] text-white shadow-lg shadow-[#023e8a]/20' 
                                : 'text-slate-500 hover:bg-white hover:text-slate-900 hover:shadow-sm'"
                        >
                            <div class="flex items-center gap-3">
                                <Icon :icon="item.icon" :width="21" :height="21" />
                                <span>{{ item.name }}</span>
                            </div>
                            <span v-if="isActive(item.href)" class="h-1.5 w-1.5 rounded-full bg-white shadow-[0_0_8px_rgba(255,255,255,0.8)]"></span>
                        </Link>
                    </nav>

                    <!-- Footer Tip -->
                    <div class="mt-auto rounded-xl bg-white p-5 text-[11px] leading-relaxed text-slate-400 border border-slate-100 shadow-sm">
                        <p class="font-semibold text-[#023e8a] uppercase tracking-wider mb-1">Quick Tip</p>
                        Keep data updated to ensure faster invoice generation and accurate reporting.
                    </div>
                </div>
            </aside>

            <!-- Main Content Area (Now White) -->
            <div class="flex-1 ml-64 min-h-screen transition-all duration-300">
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
                                    <div class="absolute -inset-1 rounded-full bg-gradient-to-tr from-[#023e8a] to-sky-400 opacity-20 blur transition duration-300 group-hover:opacity-40"></div>
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
                                    <Link :href="route('profile.edit')" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-[#023e8a]">
                                        <Icon icon="si:user-line" :width="18" :height="18"  />
                                        My Profile
                                    </Link>
                                    <Link :href="route('profile.billing')" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-[#023e8a]">
                                        <Icon icon="si:credit-card-line" :width="18" :height="18"  />
                                        Billing
                                    </Link>
                                    <Link :href="route('profile.bank-accounts.index')" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-[#023e8a]">
                                        <Icon icon="si:building-line" :width="18" :height="18"  />
                                        Bank Accounts
                                    </Link>
                                    <Link :href="route('profile.security')" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-[#023e8a]">
                                        <Icon icon="si:shield-line" :width="18" :height="18"  />
                                        Security
                                    </Link>
                                    <div class="my-1 border-t border-slate-50"></div>
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
