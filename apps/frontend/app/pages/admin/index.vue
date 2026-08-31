<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { UserCheckIcon, CheckIcon, XIcon, MessagesSquareIcon, CalendarDaysIcon } from '@lucide/vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Table,
  TableBody,
  TableCell,
  TableEmpty,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import StatusBadge from '@/components/StatusBadge.vue'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import MemberReviewDialog, { type ReviewUser } from '@/components/MemberReviewDialog.vue'

interface User {
  id: number
  name: string
  email: string
  role: string
  status: string
  rejection_reason?: string | null
  appeal_reason?: string | null
  created_at?: string | null
  updated_at?: string | null
}

const tabs = [
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'denied', label: 'Denied' },
]

const authStore = useAuthStore()
const apiBase = useRuntimeConfig().public.apiBase

const users = ref<User[]>([])
const loading = ref(true)
const tab = ref('pending')

const reviewOpen = ref(false)
const reviewUser = ref<ReviewUser | null>(null)
const reviewMode = ref<'approve' | 'reject'>('approve')

const pendingUsers = computed(() => users.value.filter((u) => u.status === 'pending'))
const approvedUsers = computed(() => users.value.filter((u) => u.status === 'approved'))
const rejectedUsers = computed(() => users.value.filter((u) => u.status === 'rejected'))

function initials(name: string) {
  const parts = name.trim().split(/\s+/)
  return ((parts[0]?.[0] ?? '') + (parts[parts.length - 1]?.[0] ?? '')).toUpperCase()
}

function formatDateTime(iso?: string | null) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function load() {
  loading.value = true
  try {
    const res = await fetch(`${apiBase}/admin/users`, {
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    if (res.ok) users.value = await res.json()
  } catch {
    toast.error('Could not load users')
  } finally {
    loading.value = false
  }
}

function openReview(user: User, mode: 'approve' | 'reject') {
  reviewUser.value = { id: user.id, name: user.name, appeal_reason: user.appeal_reason }
  reviewMode.value = mode
  reviewOpen.value = true
}

onMounted(async () => {
  if (!authStore.isAdmin) {
    await navigateTo('/archive')
    return
  }
  await load()
})
</script>

<template>
  <div class="mx-auto w-full max-w-4xl px-4 py-6 lg:px-6">
    <div class="flex items-center gap-2">
      <div class="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
        <UserCheckIcon class="size-5" />
      </div>
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Approval Request</h1>
        <p class="text-sm text-muted-foreground">Review applications, approvals and rejections.</p>
      </div>
    </div>

    <Separator class="my-6" />

    <div v-if="loading" class="space-y-3">
      <Skeleton class="h-16 w-full rounded-xl" />
      <Skeleton class="h-16 w-full rounded-xl" />
    </div>

    <template v-else>
      <!-- Top segmented tabs -->
      <div class="mb-4 grid w-full grid-cols-3 rounded-xl bg-muted p-1" role="tablist">
        <button
          v-for="t in tabs"
          :key="t.value"
          type="button"
          role="tab"
          :aria-selected="tab === t.value"
          class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg text-sm font-medium transition-colors"
          :class="tab === t.value ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
          @click="tab = t.value"
        >
          {{ t.label }}
          <Badge v-if="t.value === 'pending' && pendingUsers.length" variant="secondary">{{ pendingUsers.length }}</Badge>
        </button>
      </div>

      <!-- Pending -->
      <div v-if="tab === 'pending'">
        <div
          v-if="pendingUsers.length === 0"
          class="rounded-xl border border-dashed border-border bg-muted/30 px-6 py-14 text-center"
        >
          <p class="text-sm font-medium">No pending requests</p>
          <p class="mt-1 text-sm text-muted-foreground">New sign-ins and appeals will appear here for review.</p>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="user in pendingUsers"
            :key="user.id"
            class="flex flex-col gap-4 rounded-xl border border-border bg-card p-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="flex min-w-0 items-center gap-3">
              <Avatar size="lg">
                <AvatarFallback>{{ initials(user.name) }}</AvatarFallback>
              </Avatar>
              <div class="min-w-0">
                <p class="truncate font-medium">{{ user.name }}</p>
                <p class="truncate text-sm text-muted-foreground">{{ user.email }}</p>
                <p class="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                  <CalendarDaysIcon class="size-3.5" />
                  {{ user.appeal_reason ? 'Appealed' : 'Requested' }} on {{ formatDateTime(user.appeal_reason ? user.updated_at : user.created_at) }}
                </p>
                <p
                  v-if="user.appeal_reason"
                  class="mt-1 flex items-start gap-1.5 rounded-md bg-amber-500/10 px-2 py-1 text-xs text-amber-800 dark:text-amber-300"
                >
                  <MessagesSquareIcon class="mt-0.5 size-3.5 shrink-0" />
                  <span>{{ user.appeal_reason }}</span>
                </p>
              </div>
            </div>
            <div class="flex shrink-0 gap-2">
              <Button size="sm" @click="openReview(user, 'approve')">
                <CheckIcon class="size-3.5" />
                Approve
              </Button>
              <Button size="sm" variant="destructive" @click="openReview(user, 'reject')">
                <XIcon class="size-3.5" />
                Reject
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Approved -->
      <div v-else-if="tab === 'approved'">
        <div class="rounded-xl border border-border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="text-center">Member</TableHead>
                <TableHead class="hidden text-center sm:table-cell">Role</TableHead>
                <TableHead class="text-center">Approved on</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableEmpty v-if="approvedUsers.length === 0" :colspan="3">
                No approved members yet.
              </TableEmpty>
              <TableRow v-for="user in approvedUsers" :key="user.id">
                <TableCell class="pl-4">
                  <div class="flex items-center gap-2.5">
                    <Avatar>
                      <AvatarFallback>{{ initials(user.name) }}</AvatarFallback>
                    </Avatar>
                    <div class="min-w-0">
                      <p class="truncate font-medium">{{ user.name }}</p>
                      <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                    </div>
                  </div>
                </TableCell>
                <TableCell class="hidden text-center sm:table-cell">
                  <Badge variant="outline" class="capitalize">{{ user.role.replace('_', ' ') }}</Badge>
                </TableCell>
                <TableCell class="text-center">
                  <span class="inline-flex items-center gap-1.5 text-sm">
                    <CalendarDaysIcon class="size-3.5 text-muted-foreground" />
                    {{ formatDateTime(user.updated_at) }}
                  </span>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>

      <!-- Denied -->
      <div v-else>
        <div class="rounded-xl border border-border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="text-center">Member</TableHead>
                <TableHead class="hidden text-center md:table-cell">Reason</TableHead>
                <TableHead class="text-center">Denied on</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableEmpty v-if="rejectedUsers.length === 0" :colspan="3">
                No denied members.
              </TableEmpty>
              <TableRow v-for="user in rejectedUsers" :key="user.id">
                <TableCell class="pl-4">
                  <div class="flex items-center gap-2.5">
                    <Avatar>
                      <AvatarFallback>{{ initials(user.name) }}</AvatarFallback>
                    </Avatar>
                    <div class="min-w-0">
                      <p class="truncate font-medium">{{ user.name }}</p>
                      <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                    </div>
                  </div>
                </TableCell>
                <TableCell class="hidden max-w-xs text-center text-sm text-muted-foreground md:table-cell">
                  <p class="truncate">{{ user.rejection_reason || '—' }}</p>
                </TableCell>
                <TableCell class="text-center">
                  <span class="inline-flex items-center gap-1.5 text-sm">
                    <CalendarDaysIcon class="size-3.5 text-muted-foreground" />
                    {{ formatDateTime(user.updated_at) }}
                  </span>
                  <div class="mt-1 sm:hidden">
                    <StatusBadge :status="user.status" />
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>
    </template>

    <MemberReviewDialog
      :open="reviewOpen"
      :user="reviewUser"
      :mode="reviewMode"
      @update:open="(v) => (reviewOpen = v)"
      @done="load"
    />
  </div>
</template>