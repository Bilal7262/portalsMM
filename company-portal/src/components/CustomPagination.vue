<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-vue-next'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const props = defineProps<{
  modelValue: number
  totalPages: number
  loading?: boolean
  pageSize?: number
}>()

const emit = defineEmits(['update:modelValue', 'update:pageSize'])

const visiblePages = computed(() => {
  const delta = 2
  const range = []
  const rangeWithDots = []
  const l = props.modelValue
  const t = props.totalPages

  for (let i = 1; i <= t; i++) {
    if (i === 1 || i === t || (i >= l - delta && i <= l + delta)) {
      range.push(i)
    }
  }

  let prev
  for (const i of range) {
    if (prev) {
      if (i - prev === 2) {
        rangeWithDots.push(prev + 1)
      } else if (i - prev !== 1) {
        rangeWithDots.push('...')
      }
    }
    rangeWithDots.push(i)
    prev = i
  }

  return rangeWithDots
})

const onPageChange = (p: number | string) => {
  if (typeof p === 'number' && p !== props.modelValue && p >= 1 && p <= props.totalPages && !props.loading) {
    emit('update:modelValue', p)
  }
}

const onPageSizeChange = (val: string) => {
    emit('update:pageSize', parseInt(val))
    // Reset to page 1 when size changes? usually good practice, but let parent handle logic if needed. 
    // Usually parent watcher will just fetch.
}
</script>

<template>
  <div v-if="totalPages >= 1 || pageSize" class="flex flex-col sm:flex-row items-center justify-between px-2 py-4 gap-4">
    <!-- Page Size Selector -->
    <div v-if="pageSize" class="flex items-center gap-2 text-sm text-muted-foreground order-2 sm:order-1">
        <span>Rows per page</span>
        <Select :modelValue="pageSize.toString()" @update:modelValue="onPageSizeChange">
            <SelectTrigger class="h-8 w-[70px]">
                <SelectValue :placeholder="pageSize.toString()" />
            </SelectTrigger>
            <SelectContent side="top">
                <SelectItem value="5">5</SelectItem>
                <SelectItem value="10">10</SelectItem>
                <SelectItem value="25">25</SelectItem>
                <SelectItem value="50">50</SelectItem>
                <SelectItem value="100">100</SelectItem>
            </SelectContent>
        </Select>
    </div>

    <!-- Mobile Navigation -->
    <div class="flex-1 flex items-center justify-between w-full sm:hidden order-1">
        <Button 
            variant="outline" 
            size="sm" 
            @click="onPageChange(props.modelValue - 1)" 
            :disabled="props.modelValue === 1 || loading"
        >
            Previous
        </Button>
        <span class="text-sm text-muted-foreground">
            Page {{ modelValue }}
        </span>
        <Button 
            variant="outline" 
            size="sm" 
            @click="onPageChange(props.modelValue + 1)" 
            :disabled="props.modelValue === totalPages || loading"
        >
            Next
        </Button>
    </div>
    
    <!-- Desktop Navigation -->
    <div v-if="totalPages > 1" class="hidden sm:flex items-center gap-6 order-2">
      <p class="text-sm text-muted-foreground">
          Page <span class="font-medium">{{ modelValue }}</span> of <span class="font-medium">{{ totalPages }}</span>
      </p>
      
      <nav class="relative z-0 inline-flex rounded-md shadow-sm gap-1" aria-label="Pagination">
          <Button
            variant="outline"
            size="icon"
            class="h-9 w-9 p-0"
            @click="onPageChange(props.modelValue - 1)"
            :disabled="props.modelValue === 1 || loading"
          >
            <span class="sr-only">Previous</span>
            <ChevronLeft class="h-4 w-4" />
          </Button>

          <template v-for="(page, index) in visiblePages" :key="index">
            <Button
              v-if="page !== '...'"
              :variant="modelValue === page ? 'default' : 'outline'"
              size="icon"
              class="h-9 w-9 p-0"
              @click="onPageChange(page)"
              :disabled="loading"
            >
              {{ page }}
            </Button>
            <div
              v-else
              class="flex items-center justify-center h-9 w-9 border border-input rounded-md bg-background"
            >
              <MoreHorizontal class="h-4 w-4 text-muted-foreground" />
            </div>
          </template>

          <Button
            variant="outline"
            size="icon"
            class="h-9 w-9 p-0"
            @click="onPageChange(props.modelValue + 1)"
            :disabled="props.modelValue === totalPages || loading"
          >
            <span class="sr-only">Next</span>
            <ChevronRight class="h-4 w-4" />
          </Button>
      </nav>
    </div>
  </div>
</template>
