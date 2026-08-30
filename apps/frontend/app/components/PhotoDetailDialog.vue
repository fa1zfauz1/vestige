<script setup lang="ts">
import { CalendarIcon, MapPinIcon, UserIcon, FileTextIcon } from '@lucide/vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
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
}>()

function onOpenChange(value: boolean) {
  emit('update:open', value)
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
        />

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
