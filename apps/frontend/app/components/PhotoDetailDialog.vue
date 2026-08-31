<script setup lang="ts">
import {
  CalendarIcon,
  MapPinIcon,
  UserIcon,
  FileTextIcon,
  Trash2Icon,
  PencilIcon,
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
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
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
import LocationField, { type PlaceLocation } from '@/components/LocationField.vue'

type Photo = {
  id: number
  title: string
  description?: string | null
  taken_year?: number | null
  taken_date?: string | null
  location?: string | null
  latitude?: number | null
  longitude?: number | null
  front_image_url: string
  back_image_url?: string | null
  uploaded_by: number
  uploader?: { name: string } | null
}

const props = defineProps<{
  photo: Photo | null
  open: boolean
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'rotated', photo: Photo): void
  (e: 'updated', photo: Photo): void
  (e: 'deleted', id: number): void
}>()

const authStore = useAuthStore()
const apiBase = useRuntimeConfig().public.apiBase
const busy = ref(false)

const editMode = ref(false)
const editTitle = ref('')
const editDescription = ref('')
const editYear = ref('')
const editDate = ref('')
const editLoc = ref<PlaceLocation>({ location: '', latitude: null, longitude: null })

const canEdit = computed(() =>
  props.photo?.uploaded_by != null && authStore.user?.id === props.photo.uploaded_by,
)

function onOpenChange(value: boolean) {
  emit('update:open', value)
}

function startEdit() {
  if (!props.photo) return
  editTitle.value = props.photo.title
  editDescription.value = props.photo.description ?? ''
  editYear.value = props.photo.taken_year?.toString() ?? ''
  editDate.value = props.photo.taken_date ?? ''
  editLoc.value = {
    location: props.photo.location ?? '',
    latitude: props.photo.latitude != null ? Number(props.photo.latitude) : null,
    longitude: props.photo.longitude != null ? Number(props.photo.longitude) : null,
  }
  editMode.value = true
}

function cancelEdit() {
  editMode.value = false
}

async function saveEdit() {
  if (!props.photo) return
  busy.value = true
  try {
    const res = await fetch(`${apiBase}/photos/${props.photo.id}`, {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        title: editTitle.value,
        description: editDescription.value || null,
        taken_year: editYear.value ? Number(editYear.value) : null,
        taken_date: editDate.value || null,
        location: editLoc.value.location || null,
        latitude: editLoc.value.latitude,
        longitude: editLoc.value.longitude,
      }),
    })
    if (!res.ok) throw new Error('Update failed')
    const updated = (await res.json()) as Photo
    emit('updated', updated)
    editMode.value = false
    toast.success('Photo updated')
  } catch {
    toast.error('Could not update the photo')
  } finally {
    busy.value = false
  }
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
    <DialogContent class="sm:max-w-5xl max-h-[94svh] overflow-y-auto">
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
          :can-edit="canEdit"
          @rotate="rotate"
        />

        <!-- Edit / delete toolbar (uploader only) -->
        <div v-if="canEdit" class="flex flex-wrap items-center justify-end gap-2">
          <Button
            variant="outline"
            size="sm"
            class="min-w-24 justify-center rounded-full"
            :disabled="busy"
            @click="editMode ? cancelEdit() : startEdit()"
          >
            <PencilIcon class="size-3.5" />
            {{ editMode ? 'Cancel' : 'Edit' }}
          </Button>

          <AlertDialog>
            <AlertDialogTrigger as-child>
              <Button variant="destructive" size="sm" class="min-w-24 justify-center rounded-full">
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

        <!-- Edit form -->
        <form v-if="canEdit && editMode" class="space-y-4 rounded-xl border border-border p-4" @submit.prevent="saveEdit">
          <div class="space-y-2">
            <Label for="edit-title">Title</Label>
            <Input id="edit-title" v-model="editTitle" required />
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="edit-year">Year</Label>
              <Input id="edit-year" v-model="editYear" type="number" min="1800" :max="new Date().getFullYear()" />
            </div>
            <div class="space-y-2">
              <Label for="edit-date">Approximate date</Label>
              <Input id="edit-date" v-model="editDate" type="date" />
            </div>
          </div>
          <div class="space-y-2">
            <Label>Location</Label>
            <LocationField v-model="editLoc" :height="'160px'" />
          </div>
          <div class="space-y-2">
            <Label for="edit-description">Description</Label>
            <Textarea id="edit-description" v-model="editDescription" rows="4" />
          </div>
          <div class="flex justify-end gap-2">
            <Button type="button" variant="ghost" size="sm" :disabled="busy" @click="cancelEdit">
              Cancel
            </Button>
            <Button type="submit" size="sm" :disabled="busy">
              {{ busy ? 'Saving...' : 'Save changes' }}
            </Button>
          </div>
        </form>

        <template v-else>
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
        </template>
      </div>
    </DialogContent>
  </Dialog>
</template>