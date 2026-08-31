<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { Toaster } from '@/components/ui/sonner'

const authStore = useAuthStore()

useHead({
  htmlAttrs: {
    lang: 'en',
  },
  titleTemplate: (title) => (title ? `${title} · VESTIGE` : 'VESTIGE'),
})

function removeTransitionClass() {
  document.documentElement.classList.remove('theme-transition')
}

onMounted(async () => {
  document.documentElement.classList.add('theme-transition')
  window.setTimeout(removeTransitionClass, 400)

  if (authStore.token) {
    await authStore.fetchUser().catch(() => {})
  }
})
</script>

<template>
  <div>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
    <Toaster position="top-center" richColors />
  </div>
</template>
