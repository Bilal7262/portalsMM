<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { callService, type Call } from '@/services/call'
import { companyService, type Company } from '@/services/company'
import Button from '@/components/ui/button/Button.vue'
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
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)

onMounted(async () => {
  await fetchCompanies()
  await fetchCalls()
})

async function fetchCompanies() {
  try {
    const response = await companyService.getCompanies()
    companies.value = (response as any).data || response
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
    currentPage.value = response.current_page
    lastPage.value = response.last_page
    total.value = response.total
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

function changePage(page: number) {
  if (page < 1 || page > lastPage.value) return
  fetchCalls(page)
}

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
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold tracking-tight">Call Reports</h1>
    </div>

    <!-- Filters -->
    <div class="flex gap-4 mb-6">
      <Input v-model="search" placeholder="Search phone or disposition..." class="max-w-xs" />
      
      <select 
        v-model="selectedCompany"
        class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
      >
        <option value="">All Companies</option>
        <option v-for="c in companies" :key="c.id" :value="c.id">
          {{ c.name }}
        </option>
      </select>
    </div>

    <!-- Data Table -->
    <div class="rounded-md border bg-card">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>ID</TableHead>
            <TableHead>Date</TableHead>
            <TableHead>Caller</TableHead>
            <TableHead>Company</TableHead>
            <TableHead>DID</TableHead>
            <TableHead>Duration</TableHead>
            <TableHead>Disposition</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="isLoading">
            <TableCell colSpan="7" class="h-24 text-center">Loading...</TableCell>
          </TableRow>
          <TableRow v-else-if="calls.length === 0">
             <TableCell colSpan="7" class="h-24 text-center">No calls found.</TableCell>
          </TableRow>

          <TableRow v-for="call in calls" :key="call.id">
            <TableCell>{{ call.id }}</TableCell>
            <TableCell>{{ formatDate(call.created_at) }}</TableCell>
            <TableCell class="font-medium">{{ call.user_phone }}</TableCell>
            <TableCell>
                {{ call.invoice?.company_did?.company?.name || '-' }}
            </TableCell>
            <TableCell>
                {{ call.invoice?.company_did?.did?.did_number || '-' }}
            </TableCell>
            <TableCell>{{ formatDuration(call.duration) }}</TableCell>
            <TableCell>
              <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                {{ call.disposition }}
              </span>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Pagination -->
    <div class="flex items-center justify-end space-x-2 py-4">
      <div class="text-sm text-muted-foreground mr-4">
        Page {{ currentPage }} of {{ lastPage }} ({{ total }} total)
      </div>
      <Button
        variant="outline"
        size="sm"
        :disabled="currentPage <= 1"
        @click="changePage(currentPage - 1)"
      >
        Previous
      </Button>
      <Button
        variant="outline"
        size="sm"
        :disabled="currentPage >= lastPage"
        @click="changePage(currentPage + 1)"
      >
        Next
      </Button>
    </div>
  </div>
</template>
