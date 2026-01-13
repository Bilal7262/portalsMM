<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { voiceService } from '@/services/voice'
import { ArrowLeft } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const voiceId = ref(Number(route.params.id))
const voiceName = ref('')
const caches = ref<any[]>([])
const loading = ref(false)
const meta = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
})

const filters = ref({
  search: '',
  hit: '',
  company_agent_id: ''
})

const companyAgents = ref<any[]>([])

const fetchCaches = async (page = 1) => {
  loading.value = true
  try {
    const response = await voiceService.getVoiceCaches(voiceId.value, { 
      page, 
      per_page: 20,
      search: filters.value.search,
      hit: filters.value.hit,
      company_agent_id: filters.value.company_agent_id ? Number(filters.value.company_agent_id) : undefined
    })
    caches.value = response.data
    meta.value = {
      current_page: response.current_page,
      last_page: response.last_page,
      from: response.from,
      to: response.to,
      total: response.total
    }
    if (response.data.length > 0 && response.data[0].voice) {
      voiceName.value = response.data[0].voice.name
    }

    // Extract unique company agents for filter
    const uniqueAgents = new Map()
    response.data.forEach((cache: any) => {
      if (cache.company_agent) {
        uniqueAgents.set(cache.company_agent.id, cache.company_agent)
      }
    })
    if (companyAgents.value.length === 0) {
      companyAgents.value = Array.from(uniqueAgents.values())
    }
  } catch (error) {
    console.error('Failed to fetch caches:', error)
  } finally {
    loading.value = false
  }
}

let timeout: any
const handleSearch = () => {
  clearTimeout(timeout)
  timeout = setTimeout(() => {
    fetchCaches(1)
  }, 500)
}

const goBack = () => {
  router.push('/voices')
}

onMounted(() => {
  fetchCaches()
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
          <h2 class="text-3xl font-bold tracking-tight text-foreground">Voice Cache Details</h2>
          <p class="text-muted-foreground mt-1">{{ voiceName || 'Loading...' }}</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 bg-white p-4 shadow sm:rounded-lg">
      <div class="flex-1">
        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
        <div class="mt-1">
          <input
            v-model="filters.search"
            type="text"
            id="search"
            placeholder="Search by agent, company, cache key, or message..."
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            @input="handleSearch"
          />
        </div>
      </div>
      <div class="w-full sm:w-48">
        <label for="company_agent" class="block text-sm font-medium text-gray-700">Company Agent</label>
        <select
          v-model="filters.company_agent_id"
          id="company_agent"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @change="fetchCaches(1)"
        >
          <option value="">All Agents</option>
          <option v-for="agent in companyAgents" :key="agent.id" :value="agent.id">
            {{ agent.name }}
          </option>
        </select>
      </div>
      <div class="w-full sm:w-48">
        <label for="hit" class="block text-sm font-medium text-gray-700">Hit Status</label>
        <select
          v-model="filters.hit"
          id="hit"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @change="fetchCaches(1)"
        >
          <option value="">All</option>
          <option value="1">Hit</option>
          <option value="0">Not Hit</option>
        </select>
      </div>
    </div>

    <div class="bg-white shadow sm:rounded-lg overflow-hidden relative">
      <div v-if="loading" class="absolute inset-0 bg-white/50 flex items-center justify-center z-10">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>

      <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
          <tr>
            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Company Agent</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Company</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Cache Key</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Hit</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Hit Count</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Message</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Created</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="cache in caches" :key="cache.id">
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
              {{ cache.company_agent?.name || 'N/A' }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ cache.company_agent?.company?.business_name || 'N/A' }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 font-mono">
              {{ cache.cache_key }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <span :class="[
                cache.hit ? 'bg-green-100 text-green-800 border-green-200' : 'bg-gray-100 text-gray-800 border-gray-200',
                'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium border'
              ]">
                {{ cache.hit ? 'Yes' : 'No' }}
              </span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 font-semibold">
              {{ cache.hit_count }}
            </td>
            <td class="px-3 py-4 text-sm text-gray-500 max-w-md">
              <div class="truncate" :title="cache.message">{{ cache.message || '-' }}</div>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ new Date(cache.created_at).toLocaleDateString() }}
            </td>
          </tr>
          <tr v-if="caches.length === 0 && !loading">
            <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
              No cache entries found for this voice.
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
        <div class="flex flex-1 justify-between sm:hidden">
          <button @click="fetchCaches(meta.current_page - 1)" :disabled="meta.current_page === 1" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</button>
          <button @click="fetchCaches(meta.current_page + 1)" :disabled="meta.current_page === meta.last_page" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</button>
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing <span class="font-medium">{{ meta.from }}</span> to <span class="font-medium">{{ meta.to }}</span> of <span class="font-medium">{{ meta.total }}</span> results
            </p>
          </div>
          <div>
            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
               <button 
                v-for="p in meta.last_page" 
                :key="p"
                @click="fetchCaches(p)"
                :class="[p === meta.current_page ? 'bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0', 'relative inline-flex items-center px-4 py-2 text-sm font-semibold']"
              >
                {{ p }}
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
