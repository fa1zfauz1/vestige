<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import {
  ArchiveIcon,
  HomeIcon,
  MenuIcon,
  SearchIcon,
  UploadIcon,
  ShieldCheckIcon,
  LogOutIcon,
  XIcon,
  ImagePlusIcon,
  UsersIcon,
  LayersIcon,
} from '@lucide/vue'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Badge } from '@/components/ui/badge'

const authStore = useAuthStore()
const route = useRoute()
const mobileOpen = ref(false)

const navItems = computed(() => {
  const items = [
    { label: 'Home', to: '/', icon: HomeIcon },
    { label: 'Archive', to: '/archive', icon: ArchiveIcon },
    { label: 'Upload', to: '/upload', icon: UploadIcon },
  ]
  if (authStore.isAdmin) {
    items.push({ label: 'Admin', to: '/admin', icon: ShieldCheckIcon })
  }
  return items
})

const fullName = computed(() => authStore.user?.name ?? 'Family member')
const initials = computed(() => {
  const parts = fullName.value.trim().split(/\s+/)
  return ((parts[0]?.[0] ?? '') + (parts[parts.length - 1]?.[0] ?? '')).toUpperCase()
})

const isPending = computed(() => authStore.user?.status === 'pending')
const isSuspended = computed(() => authStore.user?.status === 'suspended')

function initialsActive(itemTo: string) {
  return (route.path === itemTo) || (itemTo === '/' && route.path === '/archive')
}

watch(() => route.fullPath, () => {
  mobileOpen.value = false
})
</script>

<template>
  <div class="min-h-dvh bg-background text-foreground">
    <!-- Desktop sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-border bg-sidebar lg:flex"
    >
      <div class="flex h-16 items-center gap-2 px-4">
        <div class="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
          <ImagePlusIcon class="size-4" />
        </div>
        <span class="text-base font-semibold tracking-tight">VESTIGE</span>
      </div>

      <div class="px-3">
        <div class="flex items-center gap-2 rounded-xl p-2 hover:bg-muted/60">
          <Avatar size="lg">
            <AvatarFallback>{{ initials }}</AvatarFallback>
          </Avatar>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium">{{ fullName }}</p>
            <p class="truncate text-xs text-muted-foreground capitalize">{{ authStore.user?.role?.replace('_', ' ') }}</p>
          </div>
          <Button variant="ghost" size="icon-sm" aria-label="Switch account">
            <UsersIcon class="size-4" />
          </Button>
        </div>

        <Button as-child variant="secondary" class="mt-2 w-full">
          <NuxtLink to="/upload">
            <ImagePlusIcon class="size-4" />
            Invite your family
          </NuxtLink>
        </Button>
      </div>

      <Separator class="my-4" />

      <nav class="flex-1 space-y-1 overflow-y-auto px-3">
        <template v-for="(item, i) in navItems" :key="item.to">
          <NuxtLink
            :to="item.to"
            class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
            :class="initialsActive(item.to) ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted/60 hover:text-foreground'"
          >
            <component :is="item.icon" class="size-4" />
            {{ item.label }}
          </NuxtLink>
          <Separator v-if="i === 2 && authStore.isAdmin" class="my-2" />
        </template>
      </nav>

      <div class="px-3 pb-4">
        <div class="flex flex-col gap-2 rounded-xl bg-muted/40 p-3 text-xs text-muted-foreground">
          <p class="font-medium text-foreground">For the stories that outlive us.</p>
          <p>Private, family-only access. Uploads are kept on your own infrastructure.</p>
        </div>
      </div>
    </aside>

    <!-- Mobile sidebar (off-canvas) -->
    <Transition enter-active-class="transition-opacity ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="mobileOpen" class="fixed inset-0 z-50 bg-black/50 lg:hidden" @click="mobileOpen = false" />
    </Transition>

    <Transition
      enter-active-class="transition ease-in-out duration-200"
      enter-from-class="-translate-x-full"
      leave-active-class="transition ease-in-out duration-200"
      leave-to-class="-translate-x-full"
    >
      <aside v-if="mobileOpen" class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-border bg-background lg:hidden">
        <div class="flex h-16 items-center justify-between px-4">
          <div class="flex items-center gap-2">
            <div class="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
              <ImagePlusIcon class="size-4" />
            </div>
            <span class="text-base font-semibold tracking-tight">VESTIGE</span>
          </div>
          <Button variant="ghost" size="icon-sm" aria-label="Close menu" @click="mobileOpen = false">
            <XIcon class="size-4" />
          </Button>
        </div>

        <div class="px-3">
          <div class="flex items-center gap-2 rounded-xl p-2">
            <Avatar size="lg">
              <AvatarFallback>{{ initials }}</AvatarFallback>
            </Avatar>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium">{{ fullName }}</p>
              <p class="truncate text-xs text-muted-foreground capitalize">{{ authStore.user?.role?.replace('_', ' ') }}</p>
            </div>
          </div>
          <Button as-child variant="secondary" class="mt-2 w-full" @click="mobileOpen = false">
            <NuxtLink to="/upload" class="flex items-center gap-2">
              <ImagePlusIcon class="size-4" />
              Invite your family
            </NuxtLink>
          </Button>
        </div>

        <Separator class="my-4" />

        <nav class="flex-1 space-y-1 overflow-y-auto px-3">
          <NuxtLink
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
            :class="initialsActive(item.to) ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted/60 hover:text-foreground'"
          >
            <component :is="item.icon" class="size-4" />
            {{ item.label }}
          </NuxtLink>
        </nav>

        <div class="border-t border-border p-3">
          <Button variant="ghost" class="w-full justify-start gap-2 text-red-600 dark:text-red-400" @click="authStore.logout">
            <LogOutIcon class="size-4" />
            Log out
          </Button>
        </div>
      </aside>
    </Transition>

    <!-- Main column -->
    <div class="flex min-h-dvh flex-col lg:pl-64">
      <!-- Topbar -->
      <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-border bg-background/80 px-4 backdrop-blur-md lg:px-6">
        <Button variant="ghost" size="icon" class="lg:hidden" aria-label="Open menu" @click="mobileOpen = true">
          <MenuIcon class="size-5" />
        </Button>

        <div class="flex items-center gap-2 lg:hidden">
          <div class="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
            <ImagePlusIcon class="size-4" />
          </div>
        </div>

        <!-- Search -->
        <div class="relative hidden max-w-md flex-1 sm:block">
          <SearchIcon class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <input
            type="search"
            placeholder="Search family memories, places, people…"
            class="h-9 w-full rounded-lg border border-border bg-muted/40 pl-9 pr-3 text-sm text-foreground outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30"
          >
        </div>

        <div class="flex-1 sm:hidden" />

        <div class="ml-auto flex items-center gap-2">
          <ThemeToggle />

          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <button class="flex size-8 items-center justify-center rounded-full" aria-label="Open account menu">
                <Avatar size="default">
                  <AvatarFallback class="bg-primary text-primary-foreground">{{ initials }}</AvatarFallback>
                </Avatar>
              </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
              <DropdownMenuLabel class="font-normal">
                <div class="flex items-center gap-2">
                  <Avatar size="sm">
                    <AvatarFallback>{{ initials }}</AvatarFallback>
                  </Avatar>
                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-foreground">{{ fullName }}</p>
                    <p class="truncate text-xs text-muted-foreground">{{ authStore.user?.email }}</p>
                  </div>
                </div>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem as-child>
                <NuxtLink to="/archive" class="flex items-center gap-2">
                  <ArchiveIcon class="size-4" />
                  My archive
                </NuxtLink>
              </DropdownMenuItem>
              <DropdownMenuItem v-if="authStore.isAdmin" as-child>
                <NuxtLink to="/admin" class="flex items-center gap-2">
                  <ShieldCheckIcon class="size-4" />
                  Admin panel
                </NuxtLink>
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem variant="destructive" @click="authStore.logout">
                <LogOutIcon class="size-4" />
                Log out
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </header>

      <!-- Pending / suspended banner -->
      <div v-if="isPending || isSuspended" class="px-4 pt-4 lg:px-6">
        <div class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
          <p v-if="isPending" class="flex items-center gap-2">
            <ClockIcon class="size-4" />
            <span>Your account is <b>pending approval</b>. You'll get access once an administrator approves you.</span>
          </p>
          <p v-else class="flex items-center gap-2">
            <LayersIcon class="size-4" />
            <span>Your account has been <b>suspended</b>. Contact an administrator for help.</span>
          </p>
        </div>
      </div>

      <!-- Page content -->
      <main class="flex-1">
        <slot />
      </main>
    </div>
  </div>
</template>
