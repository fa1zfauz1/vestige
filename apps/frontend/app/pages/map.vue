<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { MapIcon } from '@lucide/vue'
import { Skeleton } from '@/components/ui/skeleton'
import ArchiveMap, { type MapPhoto } from '@/components/ArchiveMap.vue'
import PhotoDetailDialog from '@/components/PhotoDetailDialog.vue'

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

const authStore = useAuthStore()
const apiBase = useRuntimeConfig().public.apiBase
const photos = ref<Photo[]>([])
const loading = ref(true)

const selected = ref<Photo | null>(null)
const dialogOpen = ref(false)

const photosWithCoords = computed<MapPhoto[]>(() =>
  photos.value.filter((p) => p.latitude != null && p.longitude != null),
)

async function load() {
  if (!authStore.token) {
    await navigateTo('/')
    return
  }

  loading.value = true
  try {
    const res = await fetch(`${apiBase}/photos`, {
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    if (!res.ok) throw new Error('Failed to load')
    const payload = await res.json()
    photos.value = payload.data ?? []
  } catch {
    photos.value = []
  } finally {
    loading.value = false
  }
}

function openPhoto(photo: Photo | MapPhoto) {
  selected.value = photo as Photo
  dialogOpen.value = true
}

function onRotated(updated: Photo) {
  const index = photos.value.findIndex((p) => p.id === updated.id)
  if (index !== -1) photos.value.splice(index, 1, updated)
  selected.value = updated
}

function onUpdated(updated: Photo) {
  const index = photos.value.findIndex((p) => p.id === updated.id)
  if (index !== -1) photos.value.splice(index, 1, updated)
  selected.value = updated
}

function onDeleted(id: number) {
  photos.value = photos.value.filter((p) => p.id !== id)
  selected.value = null
}

onMounted(load)
</script>

<template>
  <div class="mx-auto w-full max-w-screen-2xl px-4 py-6 lg:px-6">
    <div class="flex items-center gap-3">
      <div class="flex size-10 items-center justify-center rounded-lg bg-primary text-primary-foreground">
        <MapIcon class="size-5" />
      </div>
      <div>
        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Map</h1>
        <p class="text-sm text-muted-foreground">Photos pinned to the places they were taken.</p>
      </div>
    </div>

    <Skeleton v-if="loading" class="mt-6 h-[60vh] w-full rounded-xl" />

    <div v-else class="mt-6">
      <ArchiveMap :photos="photosWithCoords" :height="'calc(100vh - 240px)'" @select="openPhoto" />
      <p class="mt-2 text-sm text-muted-foreground">
        {{
          photosWithCoords.length === 0
            ? 'No photos pinned yet. Add a location when uploading or editing a photo.'
            : `${photosWithCoords.length} ${photosWithCoords.length === 1 ? 'photo' : 'photos'} pinned. Click a marker to open it.`
        }}
      </p>
    </div>

    <PhotoDetailDialog
      v-model:open="dialogOpen"
      :photo="selected"
      @rotated="onRotated"
      @updated="onUpdated"
      @deleted="onDeleted"
    />
  </div>
</template>