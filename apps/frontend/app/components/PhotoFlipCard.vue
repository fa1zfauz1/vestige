<script setup lang="ts">
import { ref, watch } from 'vue'
import { ArrowLeftRightIcon } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

const props = defineProps<{
  frontUrl: string
  backUrl?: string | null
}>()

const flipped = ref(false)

watch(() => props.frontUrl, () => { flipped.value = false })
</script>

<template>
  <div class="flex flex-col items-center gap-3">
    <div
      class="relative aspect-[4/3] w-full max-w-full overflow-hidden rounded-xl bg-muted [perspective:1600px]"
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
