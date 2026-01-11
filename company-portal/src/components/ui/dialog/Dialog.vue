<script setup lang="ts">
import { DialogRoot } from 'radix-vue'

defineProps<{
  open?: boolean
}>()

defineEmits<{
  'update:open': [value: boolean]
}>()
</script>

<template>
  <DialogRoot :open="open" @update:open="$emit('update:open', $event)">
    <slot />
  </DialogRoot>
</template>
