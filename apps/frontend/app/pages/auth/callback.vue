<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { Loader2Icon } from '@lucide/vue'
import { toast } from 'vue-sonner'

const authStore = useAuthStore()

onMounted(async () => {
  const params = new URLSearchParams(window.location.hash.slice(1))
  const token = params.get('token')

  if (!token) {
    await navigateTo('/')
    return
  }

  await authStore.setAuth(token)

  if (params.get('new') === '1') {
    toast.success('Welcome to VESTIGE!', {
      description: 'Your account is registered. An administrator will approve your access shortly — come back soon!',
      duration: 6000,
    })
    await navigateTo('/', { replace: true })
    return
  }

  await navigateTo('/archive', { replace: true })
})
</script>

<template>
  <div class="flex min-h-[70vh] items-center justify-center">
    <div class="flex flex-col items-center gap-4 text-center">
      <Loader2Icon class="size-8 animate-spin text-muted-foreground" />
      <div>
        <p class="text-sm font-medium">Signing you in…</p>
        <p class="text-xs text-muted-foreground">Preparing your family archive.</p>
      </div>
    </div>
  </div>
</template>
