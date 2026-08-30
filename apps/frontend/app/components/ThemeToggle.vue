<script setup lang="ts">
import { MoonIcon, SunIcon } from '@lucide/vue'

const colorMode = useColorMode()

const isDark = computed(() => colorMode.value === 'dark')

function toggle() {
  colorMode.preference = isDark.value ? 'light' : 'dark'
}
</script>

<template>
  <button
    type="button"
    aria-label="Toggle theme"
    :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    class="inline-flex size-8 items-center justify-center rounded-lg text-foreground/70 transition-colors hover:bg-muted hover:text-foreground"
    @click="toggle"
  >
    <SunIcon v-if="isDark" class="size-4" />
    <MoonIcon v-else class="size-4" />
  </button>
</template>
