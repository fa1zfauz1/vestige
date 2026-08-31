<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { ImagePlusIcon, LockKeyholeIcon, ShieldCheckIcon, SparklesIcon, ArchiveIcon, BanIcon } from '@lucide/vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Textarea } from '@/components/ui/textarea'

const authStore = useAuthStore()
const apiBase = useRuntimeConfig().public.apiBase

const isApproved = computed(() => authStore.user?.status === 'approved')
const isRejected = computed(() => authStore.user?.status === 'rejected')

const appealText = ref('')
const appealing = ref(false)

async function submitAppeal() {
  if (!appealText.value.trim()) {
    toast.error('Please explain why you should be approved.')
    return
  }
  appealing.value = true
  try {
    const res = await fetch(`${apiBase}/appeal`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({ reason: appealText.value }),
    })
    if (!res.ok) throw new Error('Appeal failed')
    toast.success('Appeal submitted', {
      description: 'An administrator will review your appeal shortly.',
    })
    appealText.value = ''
    await authStore.fetchUser()
  } catch {
    toast.error('Could not submit your appeal. Please try again.')
  } finally {
    appealing.value = false
  }
}

const features = [
  { icon: LockKeyholeIcon, title: 'Private by design', description: 'Sign in with Google and only approved family members can view the gallery.' },
  { icon: ArchiveIcon, title: 'Front & back scanning', description: 'Upload the photo and its handwritten back so no handwritten memory is lost.' },
  { icon: SparklesIcon, title: 'Searchable memories', description: 'Attach dates, places and stories so you can rediscover moments instantly.' },
]
</script>

<template>
  <div class="mx-auto w-full max-w-5xl px-4 py-12 lg:px-6">
    <div v-if="authStore.user && isApproved" class="flex flex-col items-center text-center">
      <div class="flex size-14 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
        <ImagePlusIcon class="size-7" />
      </div>
      <h1 class="mt-6 text-3xl font-semibold tracking-tight sm:text-5xl">
        Welcome back, {{ authStore.user.name.split(' ')[0] }}
      </h1>
      <p class="mt-3 text-base text-muted-foreground italic sm:text-lg">For the stories that outlive us.</p>
      <p class="mt-4 max-w-xl text-base text-muted-foreground sm:text-lg">
        Revisit photographs, flip the backs, and keep stories alive.
      </p>
      <div class="mt-8 flex flex-col gap-3 sm:flex-row">
        <NuxtLink to="/gallery">
          <Button size="lg">
            <ArchiveIcon class="size-4" />
            Open your gallery
          </Button>
        </NuxtLink>
        <NuxtLink to="/upload">
          <Button size="lg" variant="outline">
            <ImagePlusIcon class="size-4" />
            Upload photos
          </Button>
        </NuxtLink>
      </div>
    </div>

    <div v-else-if="authStore.user && isRejected" class="flex w-full flex-col items-center text-center">
      <div class="flex size-14 items-center justify-center rounded-2xl bg-destructive/10 text-destructive">
        <BanIcon class="size-7" />
      </div>
      <h1 class="mt-6 text-3xl font-semibold tracking-tight sm:text-4xl">
        Your account wasn't approved
      </h1>
      <p class="mt-3 text-base text-muted-foreground italic sm:text-lg">For the stories that outlive us.</p>
      <p class="mt-4 max-w-xl text-base text-muted-foreground sm:text-lg">
        {{ authStore.user.rejection_reason || 'The administrator did not approve your request for access to this gallery.' }}
      </p>

      <div class="mt-8 w-full max-w-md rounded-2xl border border-border bg-card p-5 text-left">
        <p class="text-sm font-medium">Think this was a mistake? Appeal below:</p>
        <p class="mt-1 text-xs text-muted-foreground">
          Your message will be reviewed by an administrator. If it's approved, you'll be able to join.
        </p>
        <Textarea
          v-model="appealText"
          class="mt-3"
          rows="4"
          placeholder="Explain who you are and why you should have access…"
        />
        <Button class="mt-3 w-full" :disabled="appealing" @click="submitAppeal">
          {{ appealing ? 'Submitting…' : 'Submit appeal' }}
        </Button>
      </div>
    </div>

    <div v-else-if="authStore.user" class="flex flex-col items-center text-center">
      <div class="flex size-14 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
        <ImagePlusIcon class="size-7" />
      </div>
      <h1 class="mt-6 text-3xl font-semibold tracking-tight sm:text-5xl">
        Welcome to VESTIGE, {{ authStore.user.name.split(' ')[0] }}
      </h1>
      <p class="mt-3 text-base text-muted-foreground italic sm:text-lg">For the stories that outlive us.</p>
      <p class="mt-4 max-w-xl text-base text-muted-foreground sm:text-lg">
        Your account is pending approval by an administrator. You'll be able to browse and share your family's memories as soon as you're approved.
      </p>
    </div>

    <div v-else class="flex flex-col items-center text-center">
      <div class="flex size-14 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
        <ImagePlusIcon class="size-7" />
      </div>
      <h1 class="mt-6 text-4xl font-semibold tracking-[0.25em] sm:text-6xl">
        VESTIGE
      </h1>
      <p class="mt-3 text-base text-muted-foreground italic sm:text-lg">For the stories that outlive us.</p>
      <p class="mt-6 max-w-xl text-base text-muted-foreground sm:text-lg">
        Upload old photographs, preserve handwritten backs, capture dates and places, and keep access limited to approved family members.
      </p>
      <div class="mt-8 flex w-full max-w-sm flex-col gap-3">
        <a :href="`${apiBase}/auth/google/redirect`" class="w-full">
          <Button size="lg" class="w-full">
            <ShieldCheckIcon class="size-4" />
            Sign in with Google
          </Button>
        </a>
        <NuxtLink to="/upload" class="w-full">
          <Button size="lg" variant="outline" class="w-full">
            <ImagePlusIcon class="size-4" />
            Explore the gallery
          </Button>
        </NuxtLink>
      </div>
    </div>

    <div v-if="!authStore.user" class="mt-16 grid gap-4 sm:grid-cols-3">
      <Card v-for="feature in features" :key="feature.title" class="border-border">
        <CardContent class="flex flex-col items-start gap-3">
          <div class="flex size-10 items-center justify-center rounded-lg bg-muted text-foreground">
            <component :is="feature.icon" class="size-5" />
          </div>
          <h2 class="text-base font-semibold">{{ feature.title }}</h2>
          <p class="text-sm text-muted-foreground">{{ feature.description }}</p>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
