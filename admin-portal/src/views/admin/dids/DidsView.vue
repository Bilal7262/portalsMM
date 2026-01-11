<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold tracking-tight">DID Management</h2>
      <button 
        @click="openAddModal"
        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
      >
        <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
        Add New DID
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
            placeholder="Search by DID number..."
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
          @change="fetchDids(1)"
        >
          <option value="">All Statuses</option>
          <option value="available">Available</option>
          <option value="assigned">Assigned</option>
          <option value="maintenance">Maintenance</option>
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
            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Number</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Assigned To</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="did in dids" :key="did.id">
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ did.did_number }}</td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <span :class="[
                did.status === 'available' ? 'bg-green-100 text-green-800' : 
                did.status === 'assigned' ? 'bg-blue-100 text-blue-800' :
                'bg-gray-100 text-gray-800',
                'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium'
              ]">
                {{ did.status }}
              </span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <span v-if="did.assigned_company">{{ did.assigned_company }}</span>
              <span v-else class="text-gray-400 italic">Unassigned</span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-right space-x-3">
              <button 
                @click="openEditModal(did)"
                class="text-blue-600 hover:text-blue-900 font-semibold"
              >
                Edit
              </button>
              <button 
                @click="deleteDid(did)"
                class="text-red-600 hover:text-red-900 font-semibold"
              >
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="dids.length === 0 && !loading">
            <td colspan="3" class="px-6 py-10 text-center text-sm text-gray-500">
              No DIDs found matching your criteria.
            </td>
          </tr>
        </tbody>
      </table>

      <Pagination 
        v-if="meta.total > 0"
        :meta="meta"
        @page-change="fetchDids"
      />
    </div>

    <!-- DID Modal -->
    <DidModal 
      :open="isModalOpen"
      :did="editingDid"
      @close="isModalOpen = false"
      @saved="fetchDids(meta.current_page)"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Plus } from 'lucide-vue-next'
const PlusIcon = Plus
import { didService } from '@/services/did'
import Pagination from '@/components/ui/Pagination.vue'
import DidModal from '@/components/modals/DidModal.vue'

const dids = ref<any[]>([])
const loading = ref(false)
const meta = ref({
  current_page: 1,
  last_page: 1,
  from: 1,
  to: 1,
  total: 0
})

const filters = reactive({
  search: '',
  status: ''
})

const isModalOpen = ref(false)
const editingDid = ref<any | null>(null)

const fetchDids = async (page = 1) => {
    loading.value = true
    try {
        const response = await didService.getDids({
            page,
            search: filters.search,
            status: filters.status
        })
        dids.value = response.data.map((did: any) => ({
            ...did,
            assigned_company: did.company_dids?.[0]?.company?.business_name || null
        }))
        meta.value = {
          current_page: response.current_page,
          last_page: response.last_page,
          from: response.from,
          to: response.to,
          total: response.total
        }
    } catch (error) {
        console.error('Failed to fetch DIDs:', error)
    } finally {
        loading.value = false
    }
}

// Simple debounce
let timeout: any
const handleSearch = () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        fetchDids(1)
    }, 500)
}

const openAddModal = () => {
  editingDid.value = null
  isModalOpen.value = true
}

const openEditModal = (did: any) => {
  editingDid.value = did
  isModalOpen.value = true
}

const deleteDid = async (did: any) => {
  if (!confirm(`Are you sure you want to delete ${did.did_number}?`)) return
  
  try {
    await didService.deleteDid(did.id)
    fetchDids(meta.value.current_page)
  } catch (error) {
    console.error('Failed to delete DID:', error)
    alert('Failed to delete DID.')
  }
}

onMounted(() => {
    fetchDids()
})
</script>

