<script setup lang="ts">
import { ref, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'

export interface ReviewUser {
  id: number
  name: string
  appeal_reason?: string | null
}

const props = defineProps<{
  open: boolean
  user: ReviewUser | null
  mode: 'approve' | 'reject'
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'done'): void
}>()

const authStore = useAuthStore()
const apiBase = useRuntimeConfig().public.apiBase

const reason = ref('')
const busy = ref(false)

watch(() => props.open, (open) => {
  if (open) reason.value = ''
})

async function submit() {
  if (!props.user) return
  if (props.mode === 'reject' && !reason.value.trim()) {
    toast.error('Please provide a reason for rejecting this member.')
    return
  }

  busy.value = true
  try {
    const body = new URLSearchParams()
    if (props.mode === 'reject') body.set('reason', reason.value)

    const res = await fetch(`${apiBase}/admin/users/${props.user.id}/${props.mode}`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body,
    })
    if (!res.ok) throw new Error('Action failed')
    toast.success(props.mode === 'approve' ? 'Member approved' : 'Member rejected')
    emit('done')
    emit('update:open', false)
  } catch {
    toast.error('Action failed')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ mode === 'approve' ? 'Approve' : 'Reject' }} {{ user?.name }}</DialogTitle>
        <DialogDescription>
          {{
            mode === 'approve'
              ? 'Approve this member for access to the gallery.'
              : 'Provide a reason — the member will see it when they sign in.'
          }}
        </DialogDescription>
      </DialogHeader>

      <div
        v-if="user?.appeal_reason"
        class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-sm text-amber-800 dark:text-amber-300"
      >
        <p class="font-medium">Appeal from member:</p>
        <p class="mt-1">{{ user.appeal_reason }}</p>
      </div>

      <div v-if="mode === 'reject'" class="space-y-2">
        <Label>Reason (required)</Label>
        <Textarea
          v-model="reason"
          rows="3"
          placeholder="e.g. Not part of the family circle"
        />
      </div>

      <DialogFooter>
        <Button variant="ghost" :disabled="busy" @click="emit('update:open', false)">
          Cancel
        </Button>
        <Button
          :variant="mode === 'reject' ? 'destructive' : 'default'"
          :disabled="busy"
          @click="submit"
        >
          {{ busy ? 'Saving…' : mode === 'approve' ? 'Approve' : 'Reject' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>