<script setup lang="ts">
import { DropdownMenuItem } from 'radix-vue'
</script>

<template>
  <DropdownMenuItem
    class="relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
  >
    <slot />
  </DropdownMenuItem>
</template>
