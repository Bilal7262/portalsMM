<script setup lang="ts">
import { SelectItem, SelectItemText } from 'radix-vue'

defineProps<{
  value: string
}>()
</script>

<template>
  <SelectItem :value="value" class="relative flex w-full cursor-pointer select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground">
    <SelectItemText>
      <slot />
    </SelectItemText>
  </SelectItem>
</template>
