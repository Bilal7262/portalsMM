<script setup lang="ts">
import { onMounted, ref } from 'vue'
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

const logs = ref<ActivityLog[]>([])
const isLoading = ref(true)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
})

onMounted(() => {
  fetchLogs()
})

async function fetchLogs(page = 1) {
  isLoading.value = true
  try {
    const response = await logService.getLogs({ page, per_page: 10 })
    logs.value = response.data
    pagination.value = {
      current_page: response.current_page,
      last_page: response.last_page,
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

    <div class="rounded-md border bg-card">
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
          <TableRow v-if="isLoading">
            <TableCell colSpan="5" class="h-24 text-center">Loading...</TableCell>
          </TableRow>
          
          <TableRow v-else-if="logs.length === 0">
             <TableCell colSpan="5" class="h-24 text-center">No logs found.</TableCell>
          </TableRow>

          <TableRow v-for="log in logs" :key="log.id">
            <TableCell>{{ log.id }}</TableCell>
            <TableCell class="font-medium">
              {{ log.admin ? log.admin.name : 'System' }}
            </TableCell>
            <TableCell>
               <span class="capitalize px-2 py-1 rounded bg-secondary text-secondary-foreground text-xs font-medium">
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
    </div>

    <div class="flex items-center justify-end space-x-2 py-4">
      <Button
        variant="outline"
        size="sm"
        :disabled="pagination.current_page <= 1 || isLoading"
        @click="fetchLogs(pagination.current_page - 1)"
      >
        Previous
      </Button>
      <div class="text-sm font-medium">
        Page {{ pagination.current_page }} of {{ pagination.last_page }}
      </div>
      <Button
        variant="outline"
        size="sm"
        :disabled="pagination.current_page >= pagination.last_page || isLoading"
        @click="fetchLogs(pagination.current_page + 1)"
      >
        Next
      </Button>
    </div>
  </div>
</template>
