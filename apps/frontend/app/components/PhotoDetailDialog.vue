<script setup lang="ts">
import {
  CalendarIcon,
  MapPinIcon,
  UserIcon,
  FileTextIcon,
  Trash2Icon,
} from '@lucide/vue'
import { useAuthStore } from '~/stores/auth'
import { toast } from 'vue-sonner'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Button } from '@/components/ui/button'
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
import PhotoFlipCard from './PhotoFlipCard.vue'

type Photo = {
  id: number
  title: string
  description?: string | null
  taken_year?: number | null
  taken_date?: string | null
  location?: string | null
  front_image_url: string
  back_image_url?: string | null
  uploader?: { name: string } | null
}

const props = defineProps<{
  photo: Photo | null
  open: boolean
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'rotated', photo: Photo): void
  (e: 'deleted', id: number): void
}>()

const authStore = useAuthStore()
const apiBase = useRuntimeConfig().public.apiBase
const busy = ref(false)

function onOpenChange(value: boolean) {
  emit('update:open', value)
}

async function rotate(direction: 'left' | 'right', side: 'front' | 'back' = 'front') {
  if (!props.photo) return
  if (side === 'back' && !props.photo.back_image_url) return
  busy.value = true
  try {
    const res = await fetch(`${apiBase}/photos/${props.photo.id}/rotate`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({ direction, target: side }),
    })
    if (!res.ok) throw new Error('Rotation failed')
    const updated = (await res.json()) as Photo
    emit('rotated', updated)
    toast.success(direction === 'left' ? 'Rotated left' : 'Rotated right')
  } catch {
    toast.error('Could not rotate the photo')
  } finally {
    busy.value = false
  }
}

async function remove() {
  if (!props.photo) return
  try {
    const res = await fetch(`${apiBase}/photos/${props.photo.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    if (!res.ok) throw new Error('Delete failed')
    emit('deleted', props.photo.id)
    emit('update:open', false)
    toast.success('Photo deleted')
  } catch {
    toast.error('Could not delete the photo')
  }
}

function formatDate(value?: string | null) {
  if (!value) return ''
  return new Date(value).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
</script>

<template>
  <Dialog
    :open="open"
    @update:open="onOpenChange"
  >
    <DialogContent class="sm:max-w-2xl">
      <DialogHeader>
        <DialogTitle>{{ photo?.title }}</DialogTitle>
        <DialogDescription>
          Revisit the memory. Flip to the back to read any handwritten notes.
        </DialogDescription>
      </DialogHeader>

      <div v-if="photo" class="space-y-4">
        <PhotoFlipCard
          :front-url="photo.front_image_url"
          :back-url="photo.back_image_url"
          :busy="busy"
          @rotate="rotate"
        />

        <div class="flex items-center justify-end">
          <AlertDialog>
            <AlertDialogTrigger as-child>
              <Button variant="destructive" size="sm">
                <Trash2Icon class="size-3.5" />
                Delete
              </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
              <AlertDialogHeader>
                <AlertDialogTitle>Delete this photo?</AlertDialogTitle>
                <AlertDialogDescription>
                  This permanently removes the photo and its back image from the archive.
                </AlertDialogDescription>
              </AlertDialogHeader>
              <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction variant="destructive" @click="remove">
                  <Trash2Icon class="size-3.5" />
                  Delete photo
                </AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        </div>

        <div class="flex flex-wrap gap-2">
          <Badge v-if="photo.taken_year" variant="outline">
            <CalendarIcon class="size-3" />
            {{ photo.taken_year }}
          </Badge>
          <Badge v-if="photo.location" variant="outline">
            <MapPinIcon class="size-3" />
            {{ photo.location }}
          </Badge>
          <Badge v-if="photo.uploader" variant="outline">
            <UserIcon class="size-3" />
            {{ photo.uploader.name }}
          </Badge>
        </div>

        <Separator />

        <div class="grid gap-3 text-sm">
          <template v-if="photo.taken_date">
            <div class="flex items-start gap-2">
              <CalendarIcon class="mt-0.5 size-4 text-muted-foreground" />
              <div>
                <p class="text-muted-foreground">Captured on</p>
                <p class="font-medium">{{ formatDate(photo.taken_date) }}</p>
              </div>
            </div>
          </template>

          <div v-if="photo.description" class="flex items-start gap-2">
            <FileTextIcon class="mt-0.5 size-4 text-muted-foreground" />
            <div>
              <p class="text-muted-foreground">Notes</p>
              <p class="whitespace-pre-wrap text-foreground">{{ photo.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
