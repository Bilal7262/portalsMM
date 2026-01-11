<script setup lang="ts">
import { DropdownMenuContent, DropdownMenuPortal } from 'radix-vue'

defineProps<{
  align?: 'start' | 'center' | 'end'
}>()
</script>

<template>
  <DropdownMenuPortal>
    <DropdownMenuContent
      :align="align"
      class="z-50 min-w-[8rem] overflow-hidden rounded-md border border-border bg-card p-1 text-card-foreground shadow-md animate-in fade-in-80"
    >
      <slot />
    </DropdownMenuContent>
  </DropdownMenuPortal>
</template>
