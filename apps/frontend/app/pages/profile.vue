<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { CameraIcon, Trash2Icon, AtSignIcon } from '@lucide/vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'

const authStore = useAuthStore()
const apiBase = useRuntimeConfig().public.apiBase

const toDateInput = (value?: string | null) => (value ? value.slice(0, 10) : '')

const name = ref(authStore.user?.name ?? '')
const nickname = ref(authStore.user?.nickname ?? '')
const birthDate = ref(toDateInput(authStore.user?.birth_date))
const avatarFile = ref<File | null>(null)
const avatarPreview = ref<string | null>(null)
const saving = ref(false)
const removingAvatar = ref(false)

watch(() => authStore.user?.birth_date, (v) => {
  if (v) birthDate.value = toDateInput(v)
})

watch(() => authStore.user?.nickname, (v) => {
  nickname.value = v ?? ''
})

const displayAvatar = computed(() => avatarPreview.value || authStore.user?.avatar_url || null)
const initials = computed(() => {
  const parts = (name.value || authStore.user?.name || '?').trim().split(/\s+/)
  return ((parts[0]?.[0] ?? '') + (parts[parts.length - 1]?.[0] ?? '')).toUpperCase()
})

function onAvatarChange(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

function clearNewAvatar() {
  avatarFile.value = null
  avatarPreview.value = null
}

async function save() {
  if (!authStore.token) return

  saving.value = true
  const data = new FormData()
  data.append('name', name.value)
  data.append('nickname', nickname.value)
  if (birthDate.value) data.append('birth_date', birthDate.value)
  if (avatarFile.value) data.append('avatar', avatarFile.value)

  try {
    const res = await fetch(`${apiBase}/profile`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: data,
    })
    if (!res.ok) throw new Error('Profile update failed')
    await authStore.fetchUser()
    avatarFile.value = null
    avatarPreview.value = null
    toast.success('Profile updated')
  } catch {
    toast.error('Could not update your profile')
  } finally {
    saving.value = false
  }
}

async function removeAvatar() {
  if (!authStore.token) return

  removingAvatar.value = true
  try {
    const res = await fetch(`${apiBase}/profile/remove-avatar`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    if (!res.ok) throw new Error('Remove failed')
    await authStore.fetchUser()
    avatarFile.value = null
    avatarPreview.value = null
    toast.success('Profile picture removed')
  } catch {
    toast.error('Could not remove the picture')
  } finally {
    removingAvatar.value = false
  }
}

onMounted(async () => {
  if (!authStore.token) {
    await navigateTo('/')
  }
})
</script>

<template>
  <div class="mx-auto w-full max-w-xl px-4 py-6 lg:px-6">
    <Card class="border-border">
      <CardHeader>
        <CardTitle class="text-xl">Your profile</CardTitle>
        <CardDescription>
          Update how your family sees you — your name, nickname and picture.
        </CardDescription>
      </CardHeader>

      <CardContent class="space-y-6">
        <div class="flex flex-col items-center gap-3 sm:flex-row sm:items-start">
          <Avatar size="lg">
            <AvatarImage v-if="displayAvatar" :src="displayAvatar" alt="Profile picture" />
            <AvatarFallback class="text-base">{{ initials }}</AvatarFallback>
          </Avatar>
          <div class="flex w-full flex-wrap items-center gap-2">
            <label class="cursor-pointer">
              <Button as-child size="sm" variant="outline" :disabled="saving" type="button">
                <span class="flex items-center gap-1.5">
                  <CameraIcon class="size-3.5" />
                  {{ avatarFile ? 'Replace picture' : 'Upload picture' }}
                </span>
              </Button>
              <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="hidden"
                @change="onAvatarChange"
              >
            </label>
            <Button
              v-if="displayAvatar"
              size="sm"
              variant="ghost"
              :disabled="removingAvatar || saving"
              @click="removeAvatar"
            >
              <Trash2Icon class="size-3.5" />
              {{ removingAvatar ? 'Removing…' : 'Remove' }}
            </Button>
          </div>
        </div>

        <Separator />

        <div class="space-y-2">
          <Label for="profile-name">Full name</Label>
          <Input id="profile-name" v-model="name" placeholder="Your full name" />
          <p class="text-xs text-muted-foreground">Defaults to the name from your Google account.</p>
        </div>

        <div class="space-y-2">
          <Label for="profile-nickname">Nickname</Label>
          <Input id="profile-nickname" v-model="nickname" placeholder="e.g. Abah, Acik, Pak Long" />
        </div>

        <div class="space-y-2">
          <Label for="profile-birth">Date of birth</Label>
          <Input id="profile-birth" v-model="birthDate" type="date" />
        </div>

        <div class="space-y-2">
          <Label for="profile-email">Email</Label>
          <div class="relative">
            <AtSignIcon class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input id="profile-email" :model-value="authStore.user?.email ?? ''" readonly class="pl-8" />
          </div>
        </div>

        <Button class="w-full" :disabled="saving" @click="save">
          {{ saving ? 'Saving…' : 'Save changes' }}
        </Button>
      </CardContent>
    </Card>
  </div>
</template>