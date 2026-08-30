<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner'

useHead({
  htmlAttrs: {
    lang: 'en',
  },
  titleTemplate: (title) => (title ? `${title} · VESTIGE` : 'VESTIGE'),
})

function removeTransitionClass() {
  document.documentElement.classList.remove('theme-transition')
}

onMounted(() => {
  document.documentElement.classList.add('theme-transition')
  window.setTimeout(removeTransitionClass, 400)
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
