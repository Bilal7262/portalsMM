<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { invoiceService } from '@/services/invoice'
import { ArrowLeft, Play, Eye } from 'lucide-vue-next'
import Pagination from '@/components/ui/Pagination.vue'

const route = useRoute()
const router = useRouter()

const itemId = ref(Number(route.params.id))
const item = ref<any>(null)
const calls = ref<any[]>([])
const loading = ref(false)
const meta = ref({
  current_page: 1,
  last_page: 1,
  from: 1,
  to: 1,
  total: 0
})

const filters = ref({
  search: '',
  disposition: '',
  ai_rating: ''
})

let searchTimeout: NodeJS.Timeout

const fetchCalls = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      ...filters.value
    }
    const response = await invoiceService.getItemCalls(itemId.value, params)
    item.value = response.item
    calls.value = response.calls.data
    meta.value = {
      current_page: response.calls.current_page,
      last_page: response.calls.last_page,
      from: response.calls.from,
      to: response.calls.to,
      total: response.calls.total
    }
  } catch (error) {
    console.error('Failed to fetch calls:', error)
  } finally {
    loading.value = false
  }
}

const handleFilterChange = () => {
  fetchCalls(1)
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchCalls(1)
  }, 500)
}

const goBack = () => {
  router.back()
}

const formatDuration = (seconds: number) => {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

onMounted(() => {
  fetchCalls()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button @click="goBack" class="text-gray-600 hover:text-gray-900">
          <ArrowLeft class="w-6 h-6" />
        </button>
        <div>
          <h2 class="text-3xl font-bold tracking-tight text-foreground">Call Details</h2>
          <p class="text-muted-foreground mt-1" v-if="item">
            {{ item.agent?.name }} | {{ item.agent?.company?.business_name }}
          </p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 shadow sm:rounded-lg grid grid-cols-1 gap-4 sm:grid-cols-4">
      <div>
        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
        <div class="mt-1">
          <input
            type="text"
            id="search"
            v-model="filters.search"
            @input="handleSearch"
            placeholder="Phone, Feedback..."
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
      </div>

      <div>
        <label for="disposition" class="block text-sm font-medium text-gray-700">Disposition</label>
        <div class="mt-1">
          <select
            id="disposition"
            v-model="filters.disposition"
            @change="handleFilterChange"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">All Dispositions</option>
            <option value="SALE">SALE</option>
            <option value="DNC">DNC</option>
            <option value="NO ANSWER">NO ANSWER</option>
            <option value="BUSY">BUSY</option>
            <option value="FAILED">FAILED</option>
          </select>
        </div>
      </div>

      <div>
        <label for="rating" class="block text-sm font-medium text-gray-700">AI Rating</label>
        <div class="mt-1">
          <select
            id="rating"
            v-model="filters.ai_rating"
            @change="handleFilterChange"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">All Ratings</option>
            <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
          </select>
        </div>
      </div>
    </div>

    <div class="bg-white shadow sm:rounded-lg overflow-hidden relative">
      <div v-if="loading" class="absolute inset-0 bg-white/50 flex items-center justify-center z-10">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>

      <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
          <tr>
            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">DID</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Customer Phone</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Duration</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Disposition</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">AI Rating</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Company Rating</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Audio</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="call in calls" :key="call.id">
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
              {{ item?.agent?.did?.phone_number || 'N/A' }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ call.user_phone || 'N/A' }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ formatDuration(call.duration) }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <span :class="[
                call.disposition === 'SALE' ? 'bg-green-100 text-green-800' :
                call.disposition === 'DNC' ? 'bg-red-100 text-red-800' :
                'bg-gray-100 text-gray-800',
                'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium'
              ]">
                {{ call.disposition || 'N/A' }}
              </span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <div v-if="call.ai_rating">
                <span class="font-semibold">{{ call.ai_rating }}/10</span>
                <p class="text-xs text-gray-400 mt-1 max-w-xs truncate" :title="call.ai_feedback">
                  {{ call.ai_feedback }}
                </p>
              </div>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <div v-if="call.company_rating">
                <span class="font-semibold">{{ call.company_rating }}/10</span>
                <p class="text-xs text-gray-400 mt-1 max-w-xs truncate" :title="call.company_feedback">
                  {{ call.company_feedback }}
                </p>
              </div>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <a 
                v-if="call.call_audio_url" 
                :href="call.call_audio_url" 
                target="_blank"
                class="text-indigo-600 hover:text-indigo-900"
                title="Play Audio"
              >
                <Play class="w-5 h-5" />
              </a>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <button 
                @click="router.push(`/calls/${call.id}/messages`)"
                class="text-green-600 hover:text-green-900"
                title="View Conversation"
              >
                <Eye class="w-5 h-5" />
              </button>
            </td>
          </tr>
          <tr v-if="calls.length === 0 && !loading">
            <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">
              No calls found for this invoice item.
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <Pagination 
        v-if="meta.total > 0"
        :meta="meta"
        @page-change="fetchCalls"
      />
    </div>
  </div>
</template>
