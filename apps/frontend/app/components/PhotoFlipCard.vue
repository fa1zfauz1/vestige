<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import {
  ArrowLeftRightIcon,
  RotateCwIcon,
  RotateCcwIcon,
  PlusIcon,
  MinusIcon,
  MaximizeIcon,
} from '@lucide/vue'

const props = defineProps<{
  frontUrl: string
  backUrl?: string | null
  busy?: boolean
}>()

const emit = defineEmits<{
  (e: 'rotate', direction: 'left' | 'right', side: 'front' | 'back'): void
}>()

const stageEl = ref<HTMLElement | null>(null)
const imgEl = ref<HTMLImageElement | null>(null)

const facing = ref<'front' | 'back'>('front')
const scale = ref(1)
const tx = ref(0)
const ty = ref(0)
const dragging = ref(false)
const settling = ref(false)

const activeSide = computed<'front' | 'back'>(() => facing.value)
const imageUrl = computed(() =>
  facing.value === 'front' ? props.frontUrl : (props.backUrl ?? props.frontUrl),
)

let dragState = { active: false, startX: 0, startY: 0, startTx: 0, startTy: 0 }

function clamp(v: number, min: number, max: number) {
  return Math.min(max, Math.max(min, v))
}

function resetView() {
  settling.value = false
  scale.value = 1
  tx.value = 0
  ty.value = 0
}

function getContentBox() {
  const stage = stageEl.value
  const img = imgEl.value
  if (!stage || !img || !img.offsetWidth) return null
  return {
    stageW: stage.clientWidth,
    stageH: stage.clientHeight,
    w: img.offsetWidth * scale.value,
    h: img.offsetHeight * scale.value,
  }
}

function panBounds() {
  const box = getContentBox()
  if (!box) return { minX: 0, maxX: 0, minY: 0, maxY: 0 }
  const ex = Math.max(0, (box.w - box.stageW) / 2)
  const ey = Math.max(0, (box.h - box.stageH) / 2)
  return { minX: -ex, maxX: ex, minY: -ey, maxY: ey }
}

function elasticValue(value: number, min: number, max: number, strength = 0.4) {
  if (value < min) return min - (min - value) * strength
  if (value > max) return max + (value - max) * strength
  return value
}

function settleView() {
  const b = panBounds()
  tx.value = clamp(tx.value, b.minX, b.maxX)
  ty.value = clamp(ty.value, b.minY, b.maxY)
}

function zoomBy(factor: number, clientX?: number, clientY?: number) {
  const next = clamp(scale.value * factor, 1, 6)
  if (next === scale.value) return

  const stage = stageEl.value
  const rect = stage?.getBoundingClientRect()

  if (clientX !== undefined && clientY !== undefined && rect) {
    const px = clientX - rect.left - rect.width / 2
    const py = clientY - rect.top - rect.height / 2
    const k = next / scale.value
    tx.value = px - (px - tx.value) * k
    ty.value = py - (py - ty.value) * k
  }

  scale.value = next
  if (next === 1) {
    tx.value = 0
    ty.value = 0
  } else {
    const b = panBounds()
    tx.value = clamp(tx.value, b.minX, b.maxX)
    ty.value = clamp(ty.value, b.minY, b.maxY)
  }
}

function zoomCenter(factor: number) {
  const rect = stageEl.value?.getBoundingClientRect()
  if (!rect) return
  zoomBy(factor, rect.left + rect.width / 2, rect.top + rect.height / 2)
}

function onWheel(e: WheelEvent) {
  const factor = e.deltaY < 0 ? 1.2 : 1 / 1.2
  zoomBy(factor, e.clientX, e.clientY)
}

function onPointerDown(e: PointerEvent) {
  if (e.button !== 0) return
  settling.value = false
  dragging.value = true
  dragState = { active: true, startX: e.clientX, startY: e.clientY, startTx: tx.value, startTy: ty.value }
  ;(e.currentTarget as HTMLElement | null)?.setPointerCapture?.(e.pointerId)
}

function onPointerMove(e: PointerEvent) {
  if (!dragState.active) return
  const b = panBounds()
  tx.value = elasticValue(dragState.startTx + (e.clientX - dragState.startX), b.minX, b.maxX)
  ty.value = elasticValue(dragState.startTy + (e.clientY - dragState.startY), b.minY, b.maxY)
}

function onPointerUp(e: PointerEvent) {
  dragState.active = false
  dragging.value = false
  settling.value = true
  requestAnimationFrame(() => settleView())
  window.setTimeout(() => { settling.value = false }, 420)
  ;(e.currentTarget as HTMLElement | null)?.releasePointerCapture?.(e.pointerId)
}

function onDoubleClick(e: MouseEvent) {
  if (scale.value > 1.5) {
    resetView()
    return
  }
  zoomBy(2 / scale.value, e.clientX, e.clientY)
}

function toggleFlip() {
  facing.value = facing.value === 'front' ? 'back' : 'front'
  resetView()
}

watch(() => props.frontUrl, () => resetView())
watch(() => props.backUrl, () => resetView())
</script>

<template>
  <div class="flex flex-col items-center gap-3">
    <div
      ref="stageEl"
      class="group touch-none relative w-full overflow-hidden rounded-xl bg-muted/60 ring-1 ring-border select-none"
      :class="scale > 1 || dragging ? 'cursor-grabbing' : 'cursor-grab'"
      style="height: clamp(280px, 55vh, 72vh)"
      @wheel.prevent="onWheel"
      @pointerdown="onPointerDown"
      @pointermove="onPointerMove"
      @pointerup="onPointerUp"
      @pointercancel="onPointerUp"
      @dblclick="onDoubleClick"
    >
      <!-- Image -->
      <div class="absolute inset-0 flex items-center justify-center">
        <img
          ref="imgEl"
          :src="imageUrl"
          :alt="facing === 'front' ? 'Photo front' : 'Photo back'"
          class="max-h-full max-w-full object-contain will-change-transform"
          :class="settling ? 'transition-transform duration-300 [transition-timing-function:cubic-bezier(0.34,1.56,0.64,1)]' : 'transition-none'"
          :style="{ transform: `translate(${tx}px, ${ty}px) scale(${scale})` }"
          draggable="false"
        >
      </div>

      <!-- Flip toggle -->
      <div class="absolute left-2 top-2 flex items-center gap-1.5">
        <button
          v-if="backUrl"
          type="button"
          class="flex items-center gap-1.5 rounded-full bg-black/40 px-2.5 py-1 text-xs font-medium text-white backdrop-blur-sm transition-colors hover:bg-black/60"
          @pointerdown.stop
          @click="toggleFlip"
        >
          <ArrowLeftRightIcon class="size-3.5" />
          {{ facing === 'front' ? 'Front' : 'Back' }}
        </button>
      </div>

      <!-- Zoom controls -->
      <div class="absolute right-2 top-2 flex items-center gap-1 rounded-full bg-black/40 p-1 backdrop-blur-sm">
        <button
          type="button"
          class="flex size-7 items-center justify-center rounded-full text-white transition-colors hover:bg-white/20"
          title="Zoom out"
          aria-label="Zoom out"
          @pointerdown.stop
          @click="zoomCenter(1 / 1.25)"
        >
          <MinusIcon class="size-3.5" />
        </button>
        <button
          type="button"
          class="flex size-7 items-center justify-center rounded-full text-white transition-colors hover:bg-white/20"
          title="Zoom in"
          aria-label="Zoom in"
          @pointerdown.stop
          @click="zoomCenter(1.25)"
        >
          <PlusIcon class="size-3.5" />
        </button>
        <button
          v-if="scale > 1 || tx !== 0 || ty !== 0"
          type="button"
          class="flex size-7 items-center justify-center rounded-full text-white transition-colors hover:bg-white/20"
          title="Reset view"
          aria-label="Reset view"
          @pointerdown.stop
          @click="resetView"
        >
          <MaximizeIcon class="size-3.5" />
        </button>
      </div>

      <!-- Rotate controls (dark fade from bottom, on hover) -->
      <div
        class="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-center gap-2 bg-gradient-to-t from-black/80 via-black/40 to-transparent px-3 pb-2.5 pt-8 opacity-100 transition-opacity duration-200 sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100"
      >
        <button
          type="button"
          class="pointer-events-auto flex size-9 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur-sm transition-colors hover:bg-white/30 focus-visible:ring-2 focus-visible:ring-white/60 disabled:pointer-events-none disabled:opacity-50"
          :disabled="busy"
          title="Rotate left"
          aria-label="Rotate current side left"
          @pointerdown.stop
          @click.stop="emit('rotate', 'left', activeSide)"
        >
          <RotateCcwIcon class="size-4" />
        </button>
        <button
          type="button"
          class="pointer-events-auto flex size-9 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur-sm transition-colors hover:bg-white/30 focus-visible:ring-2 focus-visible:ring-white/60 disabled:pointer-events-none disabled:opacity-50"
          :disabled="busy"
          title="Rotate right"
          aria-label="Rotate current side right"
          @pointerdown.stop
          @click.stop="emit('rotate', 'right', activeSide)"
        >
          <RotateCwIcon class="size-4" />
        </button>
      </div>
    </div>
  </div>
</template>