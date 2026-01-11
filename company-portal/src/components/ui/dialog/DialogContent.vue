<script setup lang="ts">
import { DialogContent, DialogPortal, DialogOverlay } from 'radix-vue'
</script>

<template>
  <DialogPortal>
    <DialogOverlay class="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm" />
    <DialogContent class="fixed left-[50%] top-[50%] z-50 grid w-full max-w-lg translate-x-[-50%] translate-y-[-50%] gap-4 border border-border bg-card p-6 shadow-lg duration-200 sm:rounded-lg">
      <slot />
    </DialogContent>
  </DialogPortal>
</template>
