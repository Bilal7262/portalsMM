<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold tracking-tight">Company DID Assignments</h2>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 bg-white p-4 shadow sm:rounded-lg">
      <div class="flex-1">
        <label for="search" class="block text-sm font-medium text-gray-700">Search DID</label>
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
          @change="fetchAssignments(1)"
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
            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">DID Number</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Company</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Price/Min</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Start Date</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="assignment in assignments" :key="assignment.id">
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
              {{ assignment.did?.did_number }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ assignment.company?.business_name }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              ${{ assignment.price_per_min }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ formatDate(assignment.start_date) }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <span :class="[
                assignment.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800',
                'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ring-gray-500/10'
              ]">
                {{ assignment.status }}
              </span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <button 
                @click="releaseAssignment(assignment)"
                class="text-red-600 hover:text-red-900 font-semibold"
              >
                Release
              </button>
            </td>
          </tr>
          <tr v-if="assignments.length === 0 && !loading">
            <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
              No assignments found matching your criteria.
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <Pagination 
        v-if="meta.total > 0"
        :meta="meta"
        @page-change="fetchAssignments"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { didService } from '@/services/did'
import Pagination from '@/components/ui/Pagination.vue'

const assignments = ref<any[]>([])
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

const fetchAssignments = async (page = 1) => {
    loading.value = true
    try {
        const response = await didService.getCompanyDids({
            page,
            search: filters.search,
            status: filters.status
        })
        assignments.value = response.data
        meta.value = {
          current_page: response.current_page,
          last_page: response.last_page,
          from: response.from,
          to: response.to,
          total: response.total
        }
    } catch (error) {
        console.error('Failed to fetch assignments:', error)
    } finally {
        loading.value = false
    }
}

// Simple debounce
let timeout: any
const handleSearch = () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        fetchAssignments(1)
    }, 500)
}

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString()
}

const releaseAssignment = async (assignment: any) => {
    if (!confirm(`Release DID ${assignment.did?.did_number} from ${assignment.company?.business_name}?`)) {
        return
    }
    
    try {
        await didService.releaseDid(assignment.id)
        fetchAssignments(meta.value.current_page)
    } catch (error) {
        console.error('Failed to release assignment:', error)
    }
}

onMounted(() => {
    fetchAssignments()
})
</script>

