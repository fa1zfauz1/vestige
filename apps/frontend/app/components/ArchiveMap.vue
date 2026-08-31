<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import type { Map as LeafletMap, Marker as LeafletMarker, LayerGroup, TileLayer } from 'leaflet'
import 'leaflet/dist/leaflet.css'

const LIGHT_TILES = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
const ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'

export interface MapPhoto {
  id: number
  title: string
  front_image_url: string
  latitude?: number | null
  longitude?: number | null
}

const props = defineProps<{
  photos: MapPhoto[]
  height?: string
}>()

const emit = defineEmits<{
  (e: 'select', photo: MapPhoto): void
}>()

const colorMode = useColorMode()
const isDark = computed(() => colorMode.value === 'dark')

const mapEl = ref<HTMLElement | null>(null)
let leaf: typeof import('leaflet') | undefined
let map: LeafletMap | null = null
let markers: LayerGroup | null = null
let tileLayer: TileLayer | null = null

function applyTheme() {
  if (!map) return
  map.getContainer().style.backgroundColor = isDark.value ? '#17181a' : '#e5e7eb'
  map.invalidateSize()
}

function thumbnail(photo: MapPhoto) {
  return leaf!.divIcon({
    className: 'fam-thumb-marker',
    html: `<div style="width:48px;height:48px;border-radius:10px;overflow:hidden;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.45);background:#fff;display:flex;align-items:center;justify-content:center"><img src="${photo.front_image_url}" alt="" style="width:100%;height:100%;object-fit:cover;display:block" /></div>`,
    iconSize: [48, 48],
    iconAnchor: [24, 42],
    popupAnchor: [0, -38],
    tooltipAnchor: [0, 0],
  })
}

function buildMarkers() {
  if (!map || !markers) return
  const group: LayerGroup = markers
  group.clearLayers()
  const withCoords = props.photos.filter((p) => p.latitude != null && p.longitude != null)
  const bounds = withCoords.map((photo) => [photo.latitude!, photo.longitude!] as [number, number])

  withCoords.forEach((photo) => {
    const marker = leaf!.marker([photo.latitude!, photo.longitude!], { icon: thumbnail(photo) }) as LeafletMarker
    marker.bindTooltip(photo.title, { direction: 'top', offset: [0, -2] })
    marker.on('click', () => emit('select', photo))
    group.addLayer(marker)
  })

  if (bounds.length > 0) {
    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 13 })
  } else {
    map.setView([4.2105, 101.9758], 5)
  }
}

async function initMap() {
  await nextTick()
  if (!mapEl.value) return

  leaf = await import('leaflet')
  map = leaf.map(mapEl.value, { scrollWheelZoom: true }).setView([4.2105, 101.9758], 5)
  tileLayer = leaf.tileLayer(LIGHT_TILES, {
    attribution: ATTRIBUTION,
  }).addTo(map)
  markers = leaf.layerGroup().addTo(map)
  applyTheme()
  buildMarkers()
  window.setTimeout(() => map?.invalidateSize(), 150)
}

onMounted(initMap)

onUnmounted(() => {
  if (map) {
    map.remove()
    map = null
  }
})

watch(() => props.photos, () => buildMarkers(), { deep: true })
watch(() => colorMode.value, () => applyTheme())
</script>

<template>
  <div>
    <div
      ref="mapEl"
      class="w-full overflow-hidden rounded-xl border border-border"
      :style="{ height: height ?? '320px' }"
    />
    <p class="mt-1 text-xs text-muted-foreground">
      Click a photo marker to open it. OpenStreetMap © contributors.
    </p>
  </div>
</template>