<script setup lang="ts">
import { onMounted, ref, reactive } from 'vue'
import { logService, type ActivityLog } from '@/services/log'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Pagination from '@/components/ui/Pagination.vue'

const logs = ref<ActivityLog[]>([])
const isLoading = ref(true)
const meta = ref({
  current_page: 1,
  last_page: 1,
  from: 1,
  to: 1,
  total: 0
})

const filters = reactive({
  type: '',
  admin_id: ''
})

onMounted(() => {
  fetchLogs()
})

async function fetchLogs(page = 1) {
  isLoading.value = true
  try {
    const response = await logService.getLogs({ 
        page, 
        type: filters.type,
        admin_id: filters.admin_id 
    })
    logs.value = response.data
    meta.value = {
      current_page: response.current_page,
      last_page: response.last_page,
      from: response.from,
      to: response.to,
      total: response.total
    }
  } catch (error) {
    console.error('Failed to fetch logs', error)
  } finally {
    isLoading.value = false
  }
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleString()
}
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold tracking-tight">Activity Logs</h1>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 bg-white p-4 shadow sm:rounded-lg mb-6 border">
      <div class="flex-1">
        <label for="type" class="block text-sm font-medium text-gray-700">Activity Type</label>
        <div class="mt-1">
          <Input
            v-model="filters.type"
            type="text"
            id="type"
            placeholder="Search by type (e.g. login, create_did)..."
            @input="fetchLogs(1)"
          />
        </div>
      </div>
      <div class="w-full sm:w-48">
        <label for="admin_id" class="block text-sm font-medium text-gray-700">Admin ID</label>
        <Input
          v-model="filters.admin_id"
          type="text"
          id="admin_id"
          placeholder="Filter by Admin ID"
          @input="fetchLogs(1)"
        />
      </div>
    </div>

    <div class="rounded-md border bg-card relative overflow-hidden">
      <div v-if="isLoading" class="absolute inset-0 bg-white/50 flex items-center justify-center z-10">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>

      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>ID</TableHead>
            <TableHead>Admin</TableHead>
            <TableHead>Type</TableHead>
            <TableHead>Details</TableHead>
            <TableHead>Date</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="logs.length === 0 && !isLoading">
             <TableCell colSpan="5" class="h-24 text-center">No logs found matching your criteria.</TableCell>
          </TableRow>

          <TableRow v-for="log in logs" :key="log.id">
            <TableCell>{{ log.id }}</TableCell>
            <TableCell class="font-medium">
              {{ log.admin ? log.admin.name : 'System' }}
            </TableCell>
            <TableCell>
               <span class="capitalize px-2 py-1 rounded bg-secondary text-secondary-foreground text-xs font-medium border">
                 {{ log.activity_type.replace(/_/g, ' ') }}
               </span>
            </TableCell>
            <TableCell class="max-w-md truncate" :title="log.activity_details">
              {{ log.activity_details }}
            </TableCell>
            <TableCell>{{ formatDate(log.activity_date) }}</TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <Pagination 
        v-if="meta.total > 0"
        :meta="meta"
        @page-change="fetchLogs"
      />
    </div>
  </div>
</template>

