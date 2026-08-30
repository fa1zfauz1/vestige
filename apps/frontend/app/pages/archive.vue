<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { UploadIcon, ImagesIcon } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import PhotoCard from '@/components/PhotoCard.vue'
import PhotoDetailDialog from '@/components/PhotoDetailDialog.vue'

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

const authStore = useAuthStore()
const apiBase = useRuntimeConfig().public.apiBase
const photos = ref<Photo[]>([])
const loading = ref(true)

const selected = ref<Photo | null>(null)
const dialogOpen = ref(false)

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

function openPhoto(photo: Photo) {
  selected.value = photo
  dialogOpen.value = true
}

function onRotated(updated: Photo) {
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
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Discover your archive</h1>
        <p class="mt-1 text-sm text-muted-foreground sm:text-base">
          Revisit and remix your family memories.
        </p>
      </div>
      <NuxtLink to="/upload">
        <Button size="lg" class="w-full sm:w-auto">
          <UploadIcon class="size-4" />
          Upload photos
        </Button>
      </NuxtLink>
    </div>

    <!-- Loading skeletons -->
    <div v-if="loading" class="mt-8 columns-1 gap-4 space-y-4 sm:columns-2 lg:columns-3 xl:columns-4">
      <Skeleton v-for="i in 8" :key="i" class="h-64 w-full rounded-xl" />
    </div>

    <!-- Empty state -->
    <div v-else-if="photos.length === 0" class="mt-8">
      <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-muted/30 px-6 py-20 text-center">
        <div class="flex size-14 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
          <ImagesIcon class="size-7" />
        </div>
        <h2 class="mt-4 text-lg font-semibold">No photos yet</h2>
        <p class="mt-1 max-w-sm text-sm text-muted-foreground">
          Start the family archive by uploading the first photograph and preserving its handwritten back.
        </p>
        <NuxtLink to="/upload" class="mt-6">
          <Button size="lg">
            <UploadIcon class="size-4" />
            Upload your first photo
          </Button>
        </NuxtLink>
      </div>
    </div>

    <!-- Masonry gallery -->
    <div
      v-else
      class="mt-8 columns-1 gap-4 [&>*]:mb-4 sm:columns-2 lg:columns-3 xl:columns-4"
    >
      <PhotoCard
        v-for="photo in photos"
        :key="photo.id"
        :photo="photo"
        class="break-inside-avoid"
        @open="openPhoto"
      />
    </div>

    <PhotoDetailDialog v-model:open="dialogOpen" :photo="selected" @rotated="onRotated" @deleted="onDeleted" />
  </div>
</template>
