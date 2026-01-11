<script setup lang="ts">
import { SelectContent, SelectPortal, SelectViewport } from 'radix-vue'
</script>

<template>
  <SelectPortal>
    <SelectContent class="relative z-50 min-w-[8rem] overflow-hidden rounded-md border border-border bg-card text-card-foreground shadow-md">
      <SelectViewport class="p-1">
        <slot />
      </SelectViewport>
    </SelectContent>
  </SelectPortal>
</template>
