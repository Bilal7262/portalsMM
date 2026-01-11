<script setup lang="ts">
import { SelectContent as RadixSelectContent, SelectPortal, SelectViewport } from 'radix-vue'
</script>

<template>
  <SelectPortal>
    <RadixSelectContent class="relative z-50 min-w-[8rem] overflow-hidden rounded-md border border-border bg-card text-card-foreground shadow-md">
      <SelectViewport class="p-1">
        <slot />
      </SelectViewport>
    </RadixSelectContent>
  </SelectPortal>
</template>
