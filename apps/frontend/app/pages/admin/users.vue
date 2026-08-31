<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { UserCogIcon, CheckIcon, BanIcon } from '@lucide/vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
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
  appeal_reason?: string | null
  created_at?: string | null
  updated_at?: string | null
}

const authStore = useAuthStore()
const apiBase = useRuntimeConfig().public.apiBase

const users = ref<User[]>([])
const loading = ref(true)

const reviewOpen = ref(false)
const reviewUser = ref<ReviewUser | null>(null)
const reviewMode = ref<'approve' | 'reject'>('approve')

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

async function suspend(id: number) {
  try {
    const res = await fetch(`${apiBase}/admin/users/${id}/suspend`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    if (!res.ok) throw new Error()
    toast.success('Member suspended')
    await load()
  } catch {
    toast.error('Action failed')
  }
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
        <UserCogIcon class="size-5" />
      </div>
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">User Management</h1>
        <p class="text-sm text-muted-foreground">Manage every member of the archive.</p>
      </div>
    </div>

    <Separator class="my-6" />

    <div v-if="loading" class="space-y-3">
      <Skeleton class="h-12 w-full rounded-xl" />
      <Skeleton class="h-12 w-full rounded-xl" />
    </div>

    <div v-else class="rounded-xl border border-border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead class="text-center">Member</TableHead>
            <TableHead class="text-center">Joined / Approved</TableHead>
            <TableHead class="text-center">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="users.length === 0" :colspan="3">
            No members yet.
          </TableEmpty>
          <TableRow v-for="user in users" :key="user.id">
            <TableCell class="pl-4">
              <div class="flex items-center gap-2.5">
                <Avatar>
                  <AvatarFallback>{{ initials(user.name) }}</AvatarFallback>
                </Avatar>
                <div class="min-w-0">
                  <p class="truncate font-medium">{{ user.name }}</p>
                  <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                  <div class="mt-1">
                    <StatusBadge :status="user.status" />
                  </div>
                </div>
              </div>
            </TableCell>
            <TableCell class="text-center">
              <div class="text-sm">
                <p>Joined {{ formatDateTime(user.created_at) }}</p>
                <p v-if="user.status === 'approved'" class="text-muted-foreground">
                  Approved {{ formatDateTime(user.updated_at) }}
                </p>
              </div>
            </TableCell>
            <TableCell class="text-center">
              <div class="inline-flex justify-center gap-1">
                <Button
                  v-if="user.status !== 'approved'"
                  size="sm"
                  variant="ghost"
                  title="Approve"
                  @click="openReview(user, 'approve')"
                >
                  <CheckIcon class="size-3.5" />
                  <span class="sr-only sm:not-sr-only">Approve</span>
                </Button>
                <Button
                  v-if="user.status === 'approved'"
                  size="sm"
                  variant="ghost"
                  title="Suspend"
                  @click="suspend(user.id)"
                >
                  <BanIcon class="size-3.5" />
                  <span class="sr-only sm:not-sr-only">Suspend</span>
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <MemberReviewDialog
      :open="reviewOpen"
      :user="reviewUser"
      :mode="reviewMode"
      @update:open="(v) => (reviewOpen = v)"
      @done="load"
    />
  </div>
</template>