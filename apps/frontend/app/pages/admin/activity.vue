<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { ScrollTextIcon } from '@lucide/vue'
import { toast } from 'vue-sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

interface LogEntry {
  id: number
  action: string
  description?: string | null
  created_at: string
  actor?: string | null
  target?: string | null
  target_type?: string | null
}

const actions = [
  { value: 'all', label: 'All actions' },
  { value: 'user.created', label: 'User created' },
  { value: 'user.updated', label: 'User updated' },
  { value: 'user.deleted', label: 'User deleted' },
  { value: 'user.approved', label: 'User approved' },
  { value: 'user.rejected', label: 'User rejected' },
  { value: 'user.suspended', label: 'User suspended' },
  { value: 'user.role_changed', label: 'Role changed' },
  { value: 'user.appealed', label: 'User appealed' },
  { value: 'login', label: 'Login' },
  { value: 'logout', label: 'Logout' },
  { value: 'photo.created', label: 'Photo created' },
  { value: 'photo.updated', label: 'Photo updated' },
  { value: 'photo.deleted', label: 'Photo deleted' },
  { value: 'photo.rotated', label: 'Photo rotated' },
  { value: 'photo.shared', label: 'Photo shared' },
]

const authStore = useAuthStore()
const apiBase = useRuntimeConfig().public.apiBase

const logs = ref<LogEntry[]>([])
const loading = ref(true)
const loadingMore = ref(false)
const filter = ref('all')
const fromDate = ref('')
const toDate = ref('')
const page = ref(1)
const nextPageUrl = ref<string | null>(null)

function badgeVariant(action: string) {
  if (action.startsWith('photo.')) return 'secondary' as const
  if (action === 'user.deleted' || action === 'user.rejected') return 'destructive' as const
  if (action === 'user.approved') return 'secondary' as const
  return 'outline' as const
}

function formatDateTime(iso: string) {
  return new Date(iso).toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function load(reset = false) {
  loading.value = reset
  loadingMore.value = !reset
  try {
    const search = new URLSearchParams()
    const actionParam = filter.value === 'all' ? '' : filter.value
    if (actionParam) search.set('action', actionParam)
    if (fromDate.value) search.set('from', fromDate.value)
    if (toDate.value) search.set('to', toDate.value)
    if (!reset) search.set('page', String(page.value + 1))

    const res = await fetch(`${apiBase}/admin/activity-logs?${search}`, {
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    const payload = await res.json()
    if (!res.ok) throw new Error('Failed to load')

    page.value = reset ? 1 : page.value + 1
    logs.value = reset ? (payload.data ?? []) : [...logs.value, ...(payload.data ?? [])]
    nextPageUrl.value = payload.next_page_url ?? null
  } catch {
    toast.error('Could not load activity logs')
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

async function loadMore() {
  if (!nextPageUrl.value) return
  await load(false)
}

onMounted(async () => {
  if (!authStore.isAdmin) {
    await navigateTo('/gallery')
    return
  }
  await load(true)
})
</script>

<template>
  <div class="mx-auto w-full max-w-5xl px-4 py-6 lg:px-6">
    <div class="flex items-center gap-2">
      <div class="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
        <ScrollTextIcon class="size-5" />
      </div>
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Audit Log</h1>
        <p class="text-sm text-muted-foreground">Every create, update and delete across the gallery.</p>
      </div>
    </div>

    <Separator class="my-6" />

    <div class="mb-4 flex flex-wrap items-end gap-2">
      <div class="w-full sm:w-52">
        <Select v-model="filter">
          <SelectTrigger class="w-full">
            <SelectValue placeholder="Filter by action" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="a in actions" :key="a.value" :value="a.value">
              {{ a.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>
      <div class="w-full sm:w-44">
        <Label for="log-from" class="text-xs text-muted-foreground">From</Label>
        <Input id="log-from" v-model="fromDate" type="date" class="mt-1" />
      </div>
      <div class="w-full sm:w-44">
        <Label for="log-to" class="text-xs text-muted-foreground">To</Label>
        <Input id="log-to" v-model="toDate" type="date" class="mt-1" />
      </div>
      <Button variant="outline" size="sm" @click="load(true)">Apply</Button>
    </div>

    <div v-if="loading" class="space-y-3">
      <Skeleton class="h-10 w-full rounded-xl" />
      <Skeleton class="h-10 w-full rounded-xl" />
    </div>

    <div v-else class="rounded-xl border border-border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Datetime</TableHead>
            <TableHead>Actor</TableHead>
            <TableHead>Action</TableHead>
            <TableHead class="hidden sm:table-cell">Target</TableHead>
            <TableHead>Details</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="logs.length === 0" :colspan="5">
            No activity recorded.
          </TableEmpty>
          <TableRow v-for="log in logs" :key="log.id">
            <TableCell class="whitespace-nowrap text-sm">{{ formatDateTime(log.created_at) }}</TableCell>
            <TableCell class="text-sm">{{ log.actor || '—' }}</TableCell>
            <TableCell>
              <Badge :variant="badgeVariant(log.action)">{{ log.action }}</Badge>
            </TableCell>
            <TableCell class="hidden text-sm sm:table-cell">
              {{ log.target || '—' }}
              <span v-if="log.target_type && log.target_type !== 'User'" class="text-xs text-muted-foreground">
                ({{ log.target_type }})
              </span>
            </TableCell>
            <TableCell class="max-w-xs truncate text-sm text-muted-foreground">{{ log.description || '—' }}</TableCell>
          </TableRow>
        </TableBody>
      </Table>
      <div v-if="nextPageUrl" class="border-t border-border p-3 text-center">
        <Button variant="outline" size="sm" :disabled="loadingMore" @click="loadMore">
          {{ loadingMore ? 'Loading…' : 'Load more' }}
        </Button>
      </div>
    </div>
  </div>
</template>