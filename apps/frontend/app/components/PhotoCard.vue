<script setup lang="ts">
import { ArrowLeftRightIcon, CalendarIcon, MapPinIcon } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'

const props = defineProps<{
  photo: {
    id: number
    title: string
    description?: string | null
    taken_year?: number | null
    taken_date?: string | null
    location?: string | null
    front_image_url: string
    back_image_url?: string | null
    uploaded_by: number
    uploader?: { name: string } | null
  }
  class?: string
}>()

const emit = defineEmits<{
  (e: 'open', photo: typeof props.photo): void
}>()
</script>

<template>
  <button
    type="button"
    :class="cn('group relative block w-full overflow-hidden rounded-xl text-left ring-1 ring-border transition-shadow duration-200 hover:ring-2 hover:ring-ring/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring', props.class)"
    @click="emit('open', photo)"
  >
    <img
      :src="photo.front_image_url"
      :alt="photo.title"
      class="w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
      loading="lazy"
    >

    <div v-if="photo.back_image_url" class="absolute right-3 top-3">
      <Badge variant="secondary" class="shadow-sm">
        <ArrowLeftRightIcon class="size-3" />
        Front &amp; back
      </Badge>
    </div>

    <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3 pt-8 opacity-0 transition-opacity duration-200 group-hover:opacity-100 focus-visible:opacity-100">
      <p class="text-sm font-medium text-white">{{ photo.title }}</p>
      <div class="mt-1.5 flex flex-wrap gap-1.5">
        <span v-if="photo.taken_year" class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-xs text-white">
          <CalendarIcon class="size-3" />
          {{ photo.taken_year }}
        </span>
        <span v-if="photo.location" class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-xs text-white">
          <MapPinIcon class="size-3" />
          {{ photo.location }}
        </span>
      </div>
    </div>
  </button>
</template>
