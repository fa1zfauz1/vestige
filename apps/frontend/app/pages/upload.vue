<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { UploadIcon, ImageIcon, FileImageIcon, XIcon } from '@lucide/vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Separator } from '@/components/ui/separator'
import LocationField, { type PlaceLocation } from '@/components/LocationField.vue'

const authStore = useAuthStore()
const apiBase = useRuntimeConfig().public.apiBase

const saving = ref(false)
const error = ref('')
const frontImage = ref<File | null>(null)
const backImage = ref<File | null>(null)
const frontPreview = ref<string | null>(null)
const backPreview = ref<string | null>(null)

const locationValue = ref<PlaceLocation>({ location: '', latitude: null, longitude: null })

const form = reactive({
  title: '',
  description: '',
  taken_year: '',
  taken_date: '',
})

function fileFromEvent(event: Event) {
  return (event.target as HTMLInputElement).files?.[0] ?? null
}

function onFrontChange(event: Event) {
  const file = fileFromEvent(event)
  frontImage.value = file
  frontPreview.value = file ? URL.createObjectURL(file) : null
}

function onBackChange(event: Event) {
  const file = fileFromEvent(event)
  backImage.value = file
  backPreview.value = file ? URL.createObjectURL(file) : null
}

function clearPreview(target: 'front' | 'back') {
  if (target === 'front') {
    frontImage.value = null
    frontPreview.value = null
  } else {
    backImage.value = null
    backPreview.value = null
  }
}

onMounted(async () => {
  if (!authStore.token || (authStore.user && authStore.user.status !== 'approved')) {
    await navigateTo('/')
  }
})

async function submit() {
  if (!authStore.token) return
  if (authStore.user && authStore.user.status !== 'approved') {
    await navigateTo('/')
    return
  }
  if (!frontImage.value) {
    error.value = 'Please choose a front image.'
    return
  }
  if (!form.title.trim()) {
    error.value = 'Please give the photo a title.'
    return
  }

  saving.value = true
  error.value = ''

  const data = new FormData()
  data.append('title', form.title)
  data.append('front_image', frontImage.value)
  if (backImage.value) data.append('back_image', backImage.value)
  if (form.description) data.append('description', form.description)
  if (form.taken_year) data.append('taken_year', form.taken_year)
  if (form.taken_date) data.append('taken_date', form.taken_date)
  if (locationValue.value.location) data.append('location', locationValue.value.location)
  if (locationValue.value.latitude != null) data.append('latitude', String(locationValue.value.latitude))
  if (locationValue.value.longitude != null) data.append('longitude', String(locationValue.value.longitude))

  try {
    const res = await fetch(`${apiBase}/photos`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: data,
    })

    if (!res.ok) throw new Error('Upload failed')

    toast.success('Photo uploaded')
    await navigateTo('/gallery')
  } catch {
    error.value = 'Upload failed. Please check the form and try again.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="mx-auto w-full max-w-2xl px-4 py-6 lg:px-6">
    <Card class="border-border">
      <CardHeader>
        <CardTitle class="text-xl">Upload a photo</CardTitle>
        <CardDescription>
          Add the image, an optional handwritten back, and the memory details.
        </CardDescription>
      </CardHeader>

      <CardContent>
        <form class="space-y-6" @submit.prevent="submit">
          <div class="space-y-2">
            <Label for="title">Title</Label>
            <Input
              id="title"
              v-model="form.title"
              placeholder="e.g. Grandma and grandpa at the beach, 1975"
              required
            />
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <!-- Front image -->
            <div class="space-y-2">
              <Label>Front image</Label>
              <div
                class="relative aspect-[4/3] overflow-hidden rounded-xl border-2 border-dashed border-border transition-colors"
                :class="frontPreview ? 'border-solid border-border bg-muted' : 'bg-muted/40'"
              >
                <img v-if="frontPreview" :src="frontPreview" alt="Front preview" class="h-full w-full object-contain">
                <label v-else class="flex h-full cursor-pointer flex-col items-center justify-center gap-2 text-muted-foreground hover:text-foreground">
                  <ImageIcon class="size-8" />
                  <span class="text-sm">Click to choose</span>
                  <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onFrontChange">
                </label>
                <button
                  v-if="frontPreview"
                  type="button"
                  class="absolute right-2 top-2 rounded-full bg-black/60 p-1.5 text-white hover:bg-black/80"
                  aria-label="Remove front image"
                  @click="clearPreview('front')"
                >
                  <XIcon class="size-4" />
                </button>
              </div>
              <p class="text-xs text-muted-foreground">JPG, PNG or WebP · up to 20MB</p>
            </div>

            <!-- Back image -->
            <div class="space-y-2">
              <Label>Back image</Label>
              <div
                class="relative aspect-[4/3] overflow-hidden rounded-xl border-2 border-dashed border-border transition-colors"
                :class="backPreview ? 'border-solid border-border bg-muted' : 'bg-muted/40'"
              >
                <img v-if="backPreview" :src="backPreview" alt="Back preview" class="h-full w-full object-contain">
                <label v-else class="flex h-full cursor-pointer flex-col items-center justify-center gap-2 text-muted-foreground hover:text-foreground">
                  <FileImageIcon class="size-8" />
                  <span class="text-sm">Optional · handwritten back</span>
                  <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onBackChange">
                </label>
                <button
                  v-if="backPreview"
                  type="button"
                  class="absolute right-2 top-2 rounded-full bg-black/60 p-1.5 text-white hover:bg-black/80"
                  aria-label="Remove back image"
                  @click="clearPreview('back')"
                >
                  <XIcon class="size-4" />
                </button>
              </div>
              <p class="text-xs text-muted-foreground">Scan the handwriting to preserve it</p>
            </div>
          </div>

          <Separator />

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="taken_year">Year</Label>
              <Input id="taken_year" v-model="form.taken_year" type="number" min="1800" :max="new Date().getFullYear()" placeholder="1975" />
            </div>
            <div class="space-y-2">
              <Label for="taken_date">Approximate date</Label>
              <Input id="taken_date" v-model="form.taken_date" type="date" />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label>Location</Label>
              <LocationField v-model="locationValue" />
            </div>
          </div>

          <div class="space-y-2">
            <Label for="description">Description</Label>
            <Textarea
              id="description"
              v-model="form.description"
              placeholder="Who is in the photo, the occasion, any memories..."
              rows="4"
            />
          </div>

          <p v-if="error" class="rounded-lg border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive">{{ error }}</p>

          <Button type="submit" size="lg" class="w-full" :disabled="saving">
            <UploadIcon class="size-4" />
            {{ saving ? 'Uploading...' : 'Save photo' }}
          </Button>
        </form>
      </CardContent>
      <CardFooter class="justify-center text-xs text-muted-foreground">
        Photos are private to approved family members.
      </CardFooter>
    </Card>
  </div>
</template>
