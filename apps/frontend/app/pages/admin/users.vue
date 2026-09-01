<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { UserCogIcon, CheckIcon, BanIcon, ShieldCheckIcon, Trash2Icon } from '@lucide/vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog'
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

const roleTarget = ref<{ id: number; name: string; role: 'admin' | 'family_member' } | null>(null)
const roleBusy = ref(false)

function requestRole(user: User, role: 'admin' | 'family_member') {
  roleTarget.value = { id: user.id, name: user.name, role }
}

async function confirmRole() {
  const target = roleTarget.value
  if (!target) return
  roleBusy.value = true
  try {
    const res = await fetch(`${apiBase}/admin/users/${target.id}/role`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({ role: target.role }),
    })
    if (!res.ok) throw new Error('Role update failed')
    toast.success(target.role === 'admin' ? 'Member is now an admin' : 'Admin role removed')
    roleTarget.value = null
    await load()
  } catch {
    toast.error('Could not update the role')
  } finally {
    roleBusy.value = false
  }
}

const deleteTarget = ref<{ id: number; name: string } | null>(null)
const deleteBusy = ref(false)

async function confirmDelete() {
  const target = deleteTarget.value
  if (!target) return
  deleteBusy.value = true
  try {
    const res = await fetch(`${apiBase}/admin/users/${target.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    if (!res.ok) throw new Error('Delete failed')
    toast.success('User deleted')
    deleteTarget.value = null
    await load()
  } catch {
    toast.error('Could not delete the user')
  } finally {
    deleteBusy.value = false
  }
}

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
    if (res.ok) {
      const data = (await res.json()) as User[]
      const me = authStore.user?.id
      users.value = data.sort((a, b) => {
        if (a.id === me) return -1
        if (b.id === me) return 1
        return a.name.localeCompare(b.name)
      })
    }
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
    await navigateTo('/gallery')
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
        <p class="text-sm text-muted-foreground">Manage every member of the gallery.</p>
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
            <TableHead class="text-center">Role</TableHead>
            <TableHead class="text-center">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="users.length === 0" :colspan="4">
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
              <Badge variant="outline" class="capitalize">{{ user.role.replace('_', ' ') }}</Badge>
            </TableCell>
            <TableCell class="text-center">
              <div class="flex flex-col items-stretch justify-center gap-1.5">
                <Button
                  v-if="user.status !== 'approved'"
                  size="sm"
                  variant="ghost"
                  class="w-full justify-center rounded-full bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400"
                  @click="openReview(user, 'approve')"
                >
                  <CheckIcon class="size-3.5" />
                  Approve
                </Button>
                <Button
                  v-if="user.role !== 'admin'"
                  size="sm"
                  variant="ghost"
                  class="w-full justify-center rounded-full bg-indigo-500/15 text-indigo-700 hover:bg-indigo-500/25 dark:text-indigo-400"
                  @click="requestRole(user, 'admin')"
                >
                  <ShieldCheckIcon class="size-3.5" />
                  Make admin
                </Button>
                <Button
                  v-else-if="authStore.user && authStore.user.id !== user.id"
                  size="sm"
                  variant="ghost"
                  class="w-full justify-center rounded-full bg-amber-500/15 text-amber-700 hover:bg-amber-500/25 dark:text-amber-400"
                  @click="requestRole(user, 'family_member')"
                >
                  <ShieldCheckIcon class="size-3.5" />
                  Remove admin
                </Button>
                <AlertDialog>
                  <AlertDialogTrigger as-child>
                    <Button
                      v-if="user.status === 'approved'"
                      size="sm"
                      variant="ghost"
                      class="w-full justify-center rounded-full bg-destructive/10 text-destructive hover:bg-destructive/20"
                    >
                      <BanIcon class="size-3.5" />
                      Suspend
                    </Button>
                  </AlertDialogTrigger>
                  <AlertDialogContent>
                    <AlertDialogHeader>
                      <AlertDialogTitle>Suspend {{ user.name }}?</AlertDialogTitle>
                      <AlertDialogDescription>
                        They will no longer be able to access the gallery until you approve them again.
                      </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                      <AlertDialogCancel>Cancel</AlertDialogCancel>
                      <AlertDialogAction variant="destructive" @click="suspend(user.id)">
                        <BanIcon class="size-3.5" />
                        Suspend
                      </AlertDialogAction>
                    </AlertDialogFooter>
                  </AlertDialogContent>
                </AlertDialog>
                <Button
                  v-if="authStore.user && authStore.user.id !== user.id"
                  size="sm"
                  variant="ghost"
                  class="w-full justify-center rounded-full bg-destructive/10 text-destructive hover:bg-destructive/20"
                  @click="deleteTarget = { id: user.id, name: user.name }"
                >
                  <Trash2Icon class="size-3.5" />
                  Delete
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

    <Dialog :open="!!roleTarget" @update:open="(v) => { if (!v) roleTarget = null }">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>
            {{ roleTarget?.role === 'admin' ? 'Make' : 'Remove' }} admin: {{ roleTarget?.name }}?
          </DialogTitle>
          <DialogDescription>
            {{
              roleTarget?.role === 'admin'
                ? 'This member will gain access to approval and user management.'
                : 'This member will lose admin privileges.'
            }}
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="ghost" :disabled="roleBusy" @click="roleTarget = null">
            Cancel
          </Button>
          <Button :disabled="roleBusy" @click="confirmRole">
            {{ roleBusy ? 'Saving…' : roleTarget?.role === 'admin' ? 'Make admin' : 'Remove admin' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog :open="!!deleteTarget" @update:open="(v) => { if (!v) deleteTarget = null }">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Delete {{ deleteTarget?.name }}?</DialogTitle>
          <DialogDescription>
            This permanently removes the user and all of their photos. This cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="ghost" :disabled="deleteBusy" @click="deleteTarget = null">
            Cancel
          </Button>
          <Button variant="destructive" :disabled="deleteBusy" @click="confirmDelete">
            {{ deleteBusy ? 'Deleting…' : 'Delete user' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>