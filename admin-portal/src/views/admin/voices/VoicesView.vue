<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold tracking-tight">Admin Voices</h2>
      <button 
        @click="openAddModal"
        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
      >
        <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
        Add Voice
      </button>
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
            placeholder="Search by name..."
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            @input="handleSearch"
          />
        </div>
      </div>
      <div class="w-full sm:w-48">
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select
          v-model="filters.status"
          id="status"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @change="fetchVoices(1)"
        >
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
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
            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Name</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Transcript Preview</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="voice in voices" :key="voice.id">
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ voice.name }}</td>
            <td class="px-3 py-4 text-sm text-gray-500 max-w-xs truncate">{{ voice.transcript }}</td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
               <span :class="[
                  voice.status === 'active' ? 'bg-green-100 text-green-800 border-green-200' : 
                  'bg-gray-100 text-gray-800 border-gray-200',
                  'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium border'
                ]">
                {{ voice.status }}
              </span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 space-x-3">
              <button 
                @click="openEditModal(voice)"
                class="text-blue-600 hover:text-blue-900 font-semibold"
              >
                Edit
              </button>
              <button 
                @click="deleteVoice(voice)"
                class="text-red-600 hover:text-red-900 font-semibold"
              >
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="voices.length === 0 && !loading">
            <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
              No voices found.
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination (Simple implementation for now or reuse component) -->
       <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
        <div class="flex flex-1 justify-between sm:hidden">
          <button @click="fetchVoices(meta.current_page - 1)" :disabled="meta.current_page === 1" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</button>
          <button @click="fetchVoices(meta.current_page + 1)" :disabled="meta.current_page === meta.last_page" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</button>
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
                @click="fetchVoices(p)"
                :class="[p === meta.current_page ? 'bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0', 'relative inline-flex items-center px-4 py-2 text-sm font-semibold']"
              >
                {{ p }}
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <VoiceModal 
      :open="isModalOpen"
      :voice="editingVoice"
      @close="isModalOpen = false"
      @saved="fetchVoices(meta.current_page)"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Plus } from 'lucide-vue-next'
const PlusIcon = Plus
import { voiceService, type AdminVoice } from '@/services/voice'
import VoiceModal from './VoiceModal.vue'

const voices = ref<AdminVoice[]>([])
const loading = ref(false)
const meta = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
})

const filters = reactive({
  search: '',
  status: ''
})

const isModalOpen = ref(false)
const editingVoice = ref<AdminVoice | null>(null)

const fetchVoices = async (page = 1) => {
    loading.value = true
    try {
        const response = await voiceService.getVoices({
            page,
            search: filters.search,
            status: filters.status
        })
        voices.value = response.data
        meta.value = {
          current_page: response.current_page,
          last_page: response.last_page,
          from: response.from,
          to: response.to,
          total: response.total
        }
    } catch (error) {
        console.error('Failed to fetch voices:', error)
    } finally {
        loading.value = false
    }
}

let timeout: any
const handleSearch = () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        fetchVoices(1)
    }, 500)
}

const openAddModal = () => {
  editingVoice.value = null
  isModalOpen.value = true
}

const openEditModal = (voice: AdminVoice) => {
  editingVoice.value = voice
  isModalOpen.value = true
}

const deleteVoice = async (voice: AdminVoice) => {
  if (!confirm(`Are you sure you want to delete ${voice.name}?`)) return
  
  try {
    await voiceService.deleteVoice(voice.id)
    fetchVoices(meta.value.current_page)
  } catch (error) {
    console.error('Failed to delete voice:', error)
  }
}

onMounted(() => {
    fetchVoices()
})
</script>
