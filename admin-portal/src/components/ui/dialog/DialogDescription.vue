<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue'
import { DialogDescription as RadixDialogDescription, type DialogDescriptionProps, useForwardProps } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<DialogDescriptionProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwarded = useForwardProps(delegatedProps)
</script>

<template>
  <RadixDialogDescription
    v-bind="forwarded"
    :class="cn('text-sm text-muted-foreground', props.class)"
  >
    <slot />
  </RadixDialogDescription>
</template>
