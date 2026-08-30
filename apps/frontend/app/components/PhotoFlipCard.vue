<script setup lang="ts">
import { ref, watch } from 'vue'
import { ArrowLeftRightIcon, RotateCwIcon, RotateCcwIcon } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

const props = defineProps<{
  frontUrl: string
  backUrl?: string | null
  busy?: boolean
}>()

const emit = defineEmits<{
  (e: 'rotate', direction: 'left' | 'right', side: 'front' | 'back'): void
}>()

const flipped = ref(false)

const activeSide = computed<'front' | 'back'>(() => (flipped.value ? 'back' : 'front'))

watch(() => props.frontUrl, () => { flipped.value = false })
</script>

<template>
  <div class="flex flex-col items-center gap-3">
    <div
      class="group relative aspect-[4/3] w-full max-w-full overflow-hidden rounded-xl bg-muted [perspective:1600px]"
      @mouseleave="flipped = false"
    >
      <div
        class="relative h-full w-full transition-transform duration-500 [transform-style:preserve-3d]"
        :class="flipped ? '[transform:rotateY(180deg)]' : ''"
      >
        <!-- Front -->
        <div class="absolute inset-0 [backface-visibility:hidden]">
          <img :src="frontUrl" :alt="'Photo front'" class="h-full w-full object-contain" draggable="false">
        </div>

        <!-- Back (optional) -->
        <div v-if="backUrl" class="absolute inset-0 [transform:rotateY(180deg)] [backface-visibility:hidden]">
          <img :src="backUrl" :alt="'Photo back'" class="h-full w-full object-contain" draggable="false">
        </div>
        <div v-else class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-muted p-6 text-center [transform:rotateY(180deg)] [backface-visibility:hidden]">
          <p class="text-sm font-medium text-muted-foreground">No back image captured</p>
          <p class="text-xs text-muted-foreground">This photo has no handwritten back saved yet.</p>
        </div>
      </div>

      <!-- Rotate controls (dark fade from bottom, shown on hover) -->
      <div
        class="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-center gap-2 bg-gradient-to-t from-black/80 via-black/40 to-transparent px-3 pb-2.5 pt-8 opacity-100 transition-opacity duration-200 sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100"
        aria-hidden="false"
      >
        <button
          type="button"
          class="pointer-events-auto flex size-9 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur-sm transition-colors hover:bg-white/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 disabled:pointer-events-none disabled:opacity-50"
          :disabled="busy"
          title="Rotate left"
          aria-label="Rotate current side left"
          @click.stop="emit('rotate', 'left', activeSide)"
        >
          <RotateCcwIcon class="size-4" />
        </button>
        <button
          type="button"
          class="pointer-events-auto flex size-9 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur-sm transition-colors hover:bg-white/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 disabled:pointer-events-none disabled:opacity-50"
          :disabled="busy"
          title="Rotate right"
          aria-label="Rotate current side right"
          @click.stop="emit('rotate', 'right', activeSide)"
        >
          <RotateCwIcon class="size-4" />
        </button>
      </div>
    </div>

    <div v-if="backUrl" class="flex items-center gap-2">
      <Badge v-if="!flipped" variant="secondary">Front</Badge>
      <Badge v-else variant="secondary">Back</Badge>
      <Button variant="outline" size="sm" @click="flipped = !flipped">
        <ArrowLeftRightIcon class="size-3.5" />
        {{ flipped ? 'Show front' : 'Flip to back' }}
      </Button>
    </div>
  </div>
</template>