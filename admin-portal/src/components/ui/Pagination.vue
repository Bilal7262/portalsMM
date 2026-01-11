<template>
  <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 mt-4">
    <div class="flex flex-1 justify-between sm:hidden">
      <button
        @click="$emit('page-change', meta.current_page - 1)"
        :disabled="meta.current_page === 1"
        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
      >
        Previous
      </button>
      <button
        @click="$emit('page-change', meta.current_page + 1)"
        :disabled="meta.current_page === meta.last_page"
        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
      >
        Next
      </button>
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div>
        <p class="text-sm text-gray-700">
          Showing
          <span class="font-medium">{{ meta.from || 0 }}</span>
          to
          <span class="font-medium">{{ meta.to || 0 }}</span>
          of
          <span class="font-medium">{{ meta.total }}</span>
          results
        </p>
      </div>
      <div>
        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
          <button
            @click="$emit('page-change', meta.current_page - 1)"
            :disabled="meta.current_page === 1"
            class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50"
          >
            <span class="sr-only">Previous</span>
            <ChevronLeft class="h-5 w-5" aria-hidden="true" />
          </button>
          
          <template v-for="page in displayedPages" :key="page">
            <span
              v-if="page === '...'"
              class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0"
            >
              ...
            </span>
            <button
              v-else
              @click="$emit('page-change', page)"
              :class="[
                page === meta.current_page
                  ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                  : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
                'relative inline-flex items-center px-4 py-2 text-sm font-semibold'
              ]"
            >
              {{ page }}
            </button>
          </template>

          <button
            @click="$emit('page-change', meta.current_page + 1)"
            :disabled="meta.current_page === meta.last_page"
            class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50"
          >
            <span class="sr-only">Next</span>
            <ChevronRight class="h-5 w-5" aria-hidden="true" />
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps<{
  meta: {
    current_page: number
    last_page: number
    from?: number
    to?: number
    total: number
  }
}>()

defineEmits(['page-change'])

const displayedPages = computed(() => {
  const current = props.meta.current_page
  const last = props.meta.last_page
  const delta = 2
  const range = []
  const rangeWithDots = []
  let l

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
      range.push(i)
    }
  }

  for (const i of range) {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1)
      } else if (i - l !== 1) {
        rangeWithDots.push('...')
      }
    }
    rangeWithDots.push(i)
    l = i
  }

  return rangeWithDots
})
</script>
