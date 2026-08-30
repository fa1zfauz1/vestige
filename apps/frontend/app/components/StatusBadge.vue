<script setup lang="ts">
import { computed } from 'vue'
import { CircleCheckIcon, ClockIcon, BanIcon, CircleUserRoundIcon } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'

const props = defineProps<{
  status: string
}>()

const config = computed(() => {
  switch (props.status) {
    case 'approved':
      return { label: 'Approved', variant: 'secondary' as const, icon: CircleCheckIcon }
    case 'pending':
      return { label: 'Pending', variant: 'outline' as const, icon: ClockIcon }
    case 'suspended':
      return { label: 'Suspended', variant: 'destructive' as const, icon: BanIcon }
    default:
      return { label: props.status, variant: 'ghost' as const, icon: CircleUserRoundIcon }
  }
})
</script>

<template>
  <Badge :variant="config.variant" class="capitalize">
    <component :is="config.icon" class="size-3" />
    {{ config.label }}
  </Badge>
</template>
