<script setup lang="ts">
import { onMounted, ref, reactive, watch } from 'vue'
import { callService, type Call } from '@/services/call'
import { companyService, type Company } from '@/services/company'
import Pagination from '@/components/ui/Pagination.vue'
import Input from '@/components/ui/input/Input.vue'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

const calls = ref<Call[]>([])
const companies = ref<Company[]>([])
const isLoading = ref(true)
const search = ref('')
const selectedCompany = ref('')
const meta = ref({
  current_page: 1,
  last_page: 1,
  from: 1,
  to: 1,
  total: 0
})

onMounted(async () => {
  await fetchCompanies()
  await fetchCalls()
})

async function fetchCompanies() {
  try {
    const response = await companyService.getCompanies({ per_page: 100 })
    companies.value = response.data
  } catch (error) {
    console.error('Failed to fetch companies', error)
  }
}

async function fetchCalls(page = 1) {
  isLoading.value = true
  try {
    const params: any = { page }
    if (search.value) params.search = search.value
    if (selectedCompany.value) params.company_id = selectedCompany.value

    const response: any = await callService.getCalls(params)
    calls.value = response.data
    meta.value = {
      current_page: response.current_page,
      last_page: response.last_page,
      from: response.from,
      to: response.to,
      total: response.total
    }
  } catch (error) {
    console.error('Failed to fetch calls', error)
  } finally {
    isLoading.value = false
  }
}

// Watchers
watch([search, selectedCompany], () => {
  fetchCalls(1)
})

function formatDuration(seconds: number) {
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m}m ${s}s`
}

function formatDate(date: string) {
    return new Date(date).toLocaleString()
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900">Call Reports</h1>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 bg-white p-4 shadow sm:rounded-lg">
      <div class="flex-1">
        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
        <Input 
          v-model="search" 
          id="search"
          placeholder="Search phone or disposition..." 
          class="mt-1" 
        />
      </div>
      
      <div class="w-full sm:w-64">
        <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
        <select 
          v-model="selectedCompany"
          id="company"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm h-10 px-3"
        >
          <option value="">All Companies</option>
          <option v-for="c in companies" :key="c.id" :value="c.id">
            {{ c.business_name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white shadow sm:rounded-lg overflow-hidden relative">
      <div v-if="isLoading" class="absolute inset-0 bg-white/50 flex items-center justify-center z-10">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>

      <Table>
        <TableHeader class="bg-gray-50">
          <TableRow>
            <TableHead class="font-semibold text-gray-900">ID</TableHead>
            <TableHead class="font-semibold text-gray-900">Date</TableHead>
            <TableHead class="font-semibold text-gray-900">Caller</TableHead>
            <TableHead class="font-semibold text-gray-900">Company</TableHead>
            <TableHead class="font-semibold text-gray-900">DID</TableHead>
            <TableHead class="font-semibold text-gray-900">Duration</TableHead>
            <TableHead class="font-semibold text-gray-900">Disposition</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="calls.length === 0 && !isLoading">
             <TableCell colSpan="7" class="h-24 text-center text-gray-500">No calls found.</TableCell>
          </TableRow>

          <TableRow v-for="call in calls" :key="call.id" class="hover:bg-gray-50">
            <TableCell class="text-gray-500 font-mono text-xs">#{{ call.id }}</TableCell>
            <TableCell class="text-gray-600 whitespace-nowrap">{{ formatDate(call.created_at) }}</TableCell>
            <TableCell class="font-medium text-gray-900">{{ call.user_phone }}</TableCell>
            <TableCell class="text-gray-600">
                {{ call.invoice?.company_did?.company?.business_name || '-' }}
            </TableCell>
            <TableCell class="text-gray-600 whitespace-nowrap">
                {{ call.invoice?.company_did?.did?.did_number || '-' }}
            </TableCell>
            <TableCell class="text-gray-600">{{ formatDuration(call.duration) }}</TableCell>
            <TableCell>
              <span :class="[
                  call.disposition === 'SALE' ? 'bg-green-100 text-green-800' :
                  call.disposition === 'DNC' ? 'bg-red-100 text-red-800' :
                  'bg-gray-100 text-gray-800',
                  'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold'
                ]">
                {{ call.disposition }}
              </span>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <!-- Pagination -->
      <Pagination 
        v-if="meta.total > 0"
        :meta="meta"
        @page-change="fetchCalls"
      />
    </div>
  </div>
</template>
