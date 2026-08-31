<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import type { Map as LeafletMap, Marker as LeafletMarker, TileLayer } from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { Loader2Icon, MapPinIcon, XIcon } from '@lucide/vue'
import { Input } from '@/components/ui/input'

const LIGHT_TILES = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
const ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'

export interface PlaceLocation {
  location?: string
  latitude: number | null
  longitude: number | null
}

interface Suggestion {
  display_name: string
  lat: number
  lon: number
}

const NOMINATIM = 'https://nominatim.openstreetmap.org'

const props = withDefaults(defineProps<{
  modelValue: PlaceLocation
  height?: string
}>(), {
  height: '180px',
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: PlaceLocation): void
}>()

const colorMode = useColorMode()
const isDark = computed(() => colorMode.value === 'dark')

const text = ref(props.modelValue?.location ?? '')
const suggestions = ref<Suggestion[]>([])
const showSuggestions = ref(false)
const searching = ref(false)
const mapEl = ref<HTMLElement | null>(null)

let map: LeafletMap | null = null
let marker: LeafletMarker | null = null
let tileLayer: TileLayer | null = null
let timer: ReturnType<typeof setTimeout> | null = null

function applyTheme() {
  if (!map) return
  map.getContainer().style.backgroundColor = isDark.value ? '#17181a' : '#e5e7eb'
  map.invalidateSize()
}

function setValue(value: PlaceLocation) {
  emit('update:modelValue', value)
}

function debouncedSearch() {
  if (timer) clearTimeout(timer)
  timer = setTimeout(async () => {
    const q = text.value.trim()
    if (q.length < 3) {
      suggestions.value = []
      showSuggestions.value = false
      return
    }
    searching.value = true
    try {
      const res = await fetch(`${NOMINATIM}/search?format=json&limit=6&q=${encodeURIComponent(q)}`)
      const data = (await res.json()) as Array<{ display_name: string; lat: string; lon: string }>
      suggestions.value = Array.isArray(data)
        ? data.map((s) => ({ display_name: s.display_name, lat: Number(s.lat), lon: Number(s.lon) }))
        : []
      showSuggestions.value = suggestions.value.length > 0
    } catch {
      suggestions.value = []
      showSuggestions.value = false
    } finally {
      searching.value = false
    }
  }, 400)
}

function onTextChange(value: string | number) {
  text.value = String(value ?? '')
  debouncedSearch()
}

function selectSuggestion(s: Suggestion) {
  text.value = s.display_name
  suggestions.value = []
  showSuggestions.value = false
  setValue({ location: s.display_name, latitude: s.lat, longitude: s.lon })
  setMarker(s.lat, s.lon)
}

function setMarker(lat: number, lng: number) {
  if (!map) return
  if (marker) {
    marker.setLatLng([lat, lng])
    return
  }
  marker = loadLeaf().marker([lat, lng], { icon: pinIcon() }).addTo(map)
}

function clearValue() {
  text.value = ''
  suggestions.value = []
  showSuggestions.value = false
  setValue({ location: '', latitude: null, longitude: null })
  if (marker) {
    marker.remove()
    marker = null
  }
}

let leaf: typeof import('leaflet') | undefined

function loadLeaf() {
  // populated lazily on the client
  return leaf!
}

async function initMap() {
  await nextTick()
  if (!mapEl.value) return

  leaf = await import('leaflet')
  // biome-ignore lint:
  map = leaf.map(mapEl.value, { scrollWheelZoom: false }).setView([4.2105, 101.9758], 5)
  tileLayer = leaf.tileLayer(LIGHT_TILES, {
    attribution: ATTRIBUTION,
  }).addTo(map)
  map.on('click', (e: any) => {
    const { lat, lng } = e.latlng
    setMarker(lat, lng)
    setValue({ location: '', latitude: lat, longitude: lng })
    reverseGeocode(lat, lng)
  })
  applyTheme()

  if (props.modelValue?.latitude != null && props.modelValue?.longitude != null) {
    setMarker(props.modelValue.latitude, props.modelValue.longitude)
  }
  window.setTimeout(() => map?.invalidateSize(), 150)
}

async function reverseGeocode(lat: number, lng: number) {
  try {
    const res = await fetch(`${NOMINATIM}/reverse?format=json&lat=${lat}&lon=${lng}`)
    const data = (await res.json()) as { display_name?: string }
    const name = data?.display_name ?? `${lat.toFixed(5)}, ${lng.toFixed(5)}`
    text.value = name
    setValue({ location: name, latitude: lat, longitude: lng })
  } catch {
    text.value = `${lat.toFixed(5)}, ${lng.toFixed(5)}`
    setValue({ location: text.value, latitude: lat, longitude: lng })
  }
}

function pinIcon() {
  const leaf = loadLeaf()
  return leaf.divIcon({
    className: 'fam-picker-pin',
    html: '<div style="width:20px;height:20px;background:#f59e0b;border:3px solid #fff;border-radius:50%;box-shadow:0 1px 4px rgba(0,0,0,.5)"></div>',
    iconSize: [20, 20],
    iconAnchor: [10, 10],
  })
}

onMounted(initMap)

onUnmounted(() => {
  if (map) {
    map.remove()
    map = null
  }
  if (timer) clearTimeout(timer)
})

watch(() => props.modelValue?.latitude, (lat, old) => {
  if (map && lat != null && lat !== old && props.modelValue?.longitude != null) {
    setMarker(lat, props.modelValue.longitude)
  }
})

watch(() => props.modelValue?.location, (loc) => {
  if (loc !== undefined && loc !== null && loc !== text.value) {
    text.value = loc
  }
})

watch(() => colorMode.value, () => applyTheme())
</script>

<template>
  <div class="space-y-2">
    <div class="relative">
      <Input
        :model-value="text"
        placeholder="Type a place or pinpoint it on the map…"
        class="pr-8"
        @update:model-value="onTextChange"
      />
      <button
        v-if="text || searching"
        type="button"
        class="absolute top-1/2 right-2 flex -translate-y-1/2 items-center justify-center rounded-full text-muted-foreground hover:text-foreground"
        :disabled="searching"
        aria-label="Clear location"
        @click="clearValue"
      >
        <Loader2Icon v-if="searching" class="size-4 animate-spin" />
        <XIcon v-else class="size-4" />
      </button>

      <div
        v-if="showSuggestions"
        class="absolute z-20 mt-1 max-h-52 w-full overflow-auto rounded-lg border border-border bg-popover p-1 text-sm shadow-md"
      >
        <button
          v-for="s in suggestions"
          :key="s.display_name"
          type="button"
          class="flex w-full items-start gap-2 rounded-md px-2 py-1.5 text-left hover:bg-muted"
          @mousedown.prevent
          @click="selectSuggestion(s)"
        >
          <MapPinIcon class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
          <span class="line-clamp-2">{{ s.display_name }}</span>
        </button>
      </div>
    </div>

    <div
      ref="mapEl"
      class="z-0 w-full overflow-hidden rounded-lg border border-border"
      :style="{ height }"
    />
    <p v-if="modelValue.latitude != null && modelValue.longitude != null" class="text-xs text-muted-foreground">
      Coordinates: {{ modelValue.latitude.toFixed(5) }}, {{ modelValue.longitude.toFixed(5) }}
    </p>
  </div>
</template>