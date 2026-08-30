<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { ShieldCheckIcon, CheckIcon, XIcon, BanIcon } from '@lucide/vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'
import StatusBadge from '@/components/StatusBadge.vue'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'

interface User {
  id: number
  name: string
  email: string
  role: string
  status: string
}

const authStore = useAuthStore()
const apiBase = useRuntimeConfig().public.apiBase

const pending = ref<User[]>([])
const users = ref<User[]>([])
const loading = ref(true)
const tab = ref('pending')

function initials(name: string) {
  const parts = name.trim().split(/\s+/)
  return ((parts[0]?.[0] ?? '') + (parts[parts.length - 1]?.[0] ?? '')).toUpperCase()
}

async function load() {
  loading.value = true
  try {
    const headers = { Authorization: `Bearer ${authStore.token}` }
    const [pendingRes, usersRes] = await Promise.all([
      fetch(`${apiBase}/admin/pending-users`, { headers }),
      fetch(`${apiBase}/admin/users`, { headers }),
    ])
    if (pendingRes.ok) pending.value = await pendingRes.json()
    if (usersRes.ok) users.value = await usersRes.json()
  } catch {
    toast.error('Could not load users')
  } finally {
    loading.value = false
    if (pending.value.length > 0) tab.value = 'pending'
  }
}

async function action(id: number, action: 'approve' | 'reject' | 'suspend') {
  try {
    const res = await fetch(`${apiBase}/admin/users/${id}/${action}`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    if (!res.ok) throw new Error()
    toast.success(`Member ${action}d`)
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
        <ShieldCheckIcon class="size-5" />
      </div>
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Admin</h1>
        <p class="text-sm text-muted-foreground">Approve and manage family member access.</p>
      </div>
    </div>

    <Separator class="my-6" />

    <div v-if="loading" class="space-y-3">
      <Skeleton class="h-16 w-full rounded-xl" />
      <Skeleton class="h-16 w-full rounded-xl" />
    </div>

    <Tabs v-else v-model="tab" class="w-full">
      <TabsList class="w-full sm:w-auto">
        <TabsTrigger value="pending" class="flex-1 sm:flex-none">
          Pending
          <Badge v-if="pending.length" variant="secondary">{{ pending.length }}</Badge>
        </TabsTrigger>
        <TabsTrigger value="all" class="flex-1 sm:flex-none">
          All members
        </TabsTrigger>
      </TabsList>

      <TabsContent value="pending">
        <div v-if="pending.length === 0" class="rounded-xl border border-dashed border-border bg-muted/30 px-6 py-14 text-center">
          <p class="text-sm font-medium">No pending members</p>
          <p class="mt-1 text-sm text-muted-foreground">New sign-ins will appear here for approval.</p>
        </div>

        <div v-else class="space-y-3">
          <div v-for="user in pending" :key="user.id" class="flex flex-col gap-4 rounded-xl border border-border bg-card p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
              <Avatar size="lg">
                <AvatarFallback>{{ initials(user.name) }}</AvatarFallback>
              </Avatar>
              <div class="min-w-0">
                <p class="truncate font-medium">{{ user.name }}</p>
                <p class="truncate text-sm text-muted-foreground">{{ user.email }}</p>
              </div>
            </div>
            <div class="flex gap-2">
              <Button size="sm" @click="action(user.id, 'approve')">
                <CheckIcon class="size-3.5" />
                Approve
              </Button>
              <Button size="sm" variant="destructive" @click="action(user.id, 'reject')">
                <XIcon class="size-3.5" />
                Reject
              </Button>
            </div>
          </div>
        </div>
      </TabsContent>

      <TabsContent value="all">
        <div class="rounded-xl border border-border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Member</TableHead>
                <TableHead class="hidden sm:table-cell">Role</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableEmpty v-if="users.length === 0">
                No members yet.
              </TableEmpty>
              <TableRow v-for="user in users" :key="user.id">
                <TableCell>
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
                <TableCell class="hidden sm:table-cell">
                  <Badge variant="outline" class="capitalize">{{ user.role.replace('_', ' ') }}</Badge>
                </TableCell>
                <TableCell>
                  <StatusBadge :status="user.status" />
                </TableCell>
                <TableCell class="text-right">
                  <div class="inline-flex gap-1">
                    <Button v-if="user.status !== 'approved'" size="sm" variant="ghost" @click="action(user.id, 'approve')">
                      <CheckIcon class="size-3.5" />
                      <span class="sr-only sm:not-sr-only">Approve</span>
                    </Button>
                    <Button v-if="user.status === 'approved'" size="sm" variant="ghost" title="Suspend" @click="action(user.id, 'suspend')">
                      <BanIcon class="size-3.5" />
                      <span class="sr-only sm:not-sr-only">Suspend</span>
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </TabsContent>
    </Tabs>
  </div>
</template>
