<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/lib/axios'
import CustomPagination from '@/components/CustomPagination.vue'
import { 
  Download, 
  Search,
  FileText,
  Eye,
  ArrowUp,
  ArrowDown,
  ArrowUpDown
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const router = useRouter()
const invoices = ref<any[]>([])
const loading = ref(false)

// Filters State
const searchQuery = ref('')
const statusFilter = ref('ALL')

// Sorting State
const sortBy = ref('created_at')
const sortOrder = ref('desc')

// Pagination State
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = ref(10)
const totalInvoices = ref(0) // Although not strictly used by CustomPagination explicitly if not passed, it's good to track.

const fetchInvoices = async () => {
    loading.value = true
    try {
        const params: any = {
            page: currentPage.value,
            per_page: perPage.value,
            search_query: searchQuery.value,
            sort_by: sortBy.value,
            sort_order: sortOrder.value
        }
        
        if (statusFilter.value !== 'ALL') {
            params.status = statusFilter.value
        }

        const response = await api.get('/company/invoices', { params })
        
        const responseData = response.data
        const dataArray = responseData.data || []
        
        invoices.value = dataArray.map((inv: any) => ({
             id: inv.id,
             displayId: `INV-${inv.id}`,
             period: inv.effective_from ? new Date(inv.effective_from).toLocaleDateString('en-US', { month: 'short', year: 'numeric' }) : 'N/A',
             date: inv.created_at ? new Date(inv.created_at).toLocaleDateString() : 'N/A',
             dueDate: inv.effective_to ? new Date(inv.effective_to).toLocaleDateString() : 'N/A',
             minutes: inv.total_minutes_consumption || 0,
             amount: inv.billed_amount ? parseFloat(inv.billed_amount) : 0,
             status: inv.status || 'Unknown'
        }))

        // Update pagination meta
        currentPage.value = responseData.current_page || 1
        lastPage.value = responseData.last_page || 1
        perPage.value = responseData.per_page || 10
        totalInvoices.value = responseData.total || 0

    } catch (e) {
        console.error("Failed to fetch invoices", e)
    } finally {
        loading.value = false
    }
}

const handleSort = (field: string) => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortBy.value = field
        sortOrder.value = 'desc' // Default to desc for new field
    }
    fetchInvoices()
}

const handlePageChange = (page: number) => {
    if (page >= 1 && page <= lastPage.value) {
        currentPage.value = page
        fetchInvoices()
    }
}

const handlePerPageChange = (newPerPage: number) => {
    perPage.value = newPerPage
    currentPage.value = 1 // Reset to first page
    fetchInvoices()
}

const viewDetails = (invoice: any) => {
    router.push(`/invoices/${invoice.id}/calls`)
}

const getStatusColor = (status: string) => {
  switch (status.toLowerCase()) {
    case 'paid': return 'bg-green-500/15 text-green-600 border-green-200'
    case 'pending': return 'bg-yellow-500/15 text-yellow-600 border-yellow-200'
    case 'overdue': return 'bg-destructive/15 text-destructive border-destructive/20'
    default: return 'bg-secondary text-secondary-foreground'
  }
}

const downloadInvoice = async (invoiceId: string | number) => {
    try {
        const id = invoiceId.toString().replace('INV-', '');
        const response = await api.get(`/company/invoices/${id}/download`, {
            responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `invoice_${id}.csv`);
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (e) {
        console.error("Failed to download invoice", e);
        alert("Failed to download invoice.");
    }
}

onMounted(() => {
    fetchInvoices()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-3xl font-bold tracking-tight text-foreground">Invoices</h2>
        <p class="text-muted-foreground mt-1">View and manage your billing history</p>
      </div>
      <Button variant="outline">
        <Download class="w-4 h-4 mr-2" />
        Export Report
      </Button>
    </div>

    <!-- Filters Bar -->
    <div class="bg-card border border-border rounded-xl p-4 flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto text-sm">
             <div class="relative w-full sm:w-64">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="searchQuery" 
                    placeholder="Search invoice #..." 
                    class="pl-9 h-9"
                    @input="fetchInvoices" 
                /> <!-- Added @input to trigger fetch or use watcher -->
            </div>
             <Select v-model="statusFilter" @update:modelValue="fetchInvoices">
                <SelectTrigger class="w-full sm:w-56 h-9">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="ALL">All Statuses</SelectItem>
                    <SelectItem value="Paid">Paid</SelectItem>
                    <SelectItem value="Pending">Pending</SelectItem>
                    <SelectItem value="Overdue">Overdue</SelectItem>
                    <SelectItem value="Draft">Draft</SelectItem>
                    <SelectItem value="Finalized">Finalized</SelectItem>
                </SelectContent>
            </Select>
        </div>
        
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
             <span v-if="loading" class="animate-pulse">Updating...</span>
             <span v-else>{{ totalInvoices }} Invoices found</span>
             
             <Button 
                v-if="searchQuery || statusFilter !== 'ALL'"
                variant="link" 
                @click="searchQuery = ''; statusFilter = 'ALL'; fetchInvoices()"
                class="ml-2 h-auto p-0"
            >
                Clear Filters
            </Button>
        </div>
    </div>

    <!-- Invoices List -->
    <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden flex flex-col h-full">
      <div class="overflow-x-auto flex-1">
        <table class="w-full">
          <thead class="bg-muted/50 border-b border-border">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-foreground group" @click="handleSort('id')">
                  <div class="flex items-center gap-1">
                    Invoice ID
                    <component :is="sortBy === 'id' ? (sortOrder === 'asc' ? ArrowUp : ArrowDown) : ArrowUpDown" class="w-3 h-3 transition-opacity" :class="sortBy === 'id' ? 'text-primary' : 'opacity-50 group-hover:opacity-100'" />
                  </div>
              </th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-foreground group" @click="handleSort('effective_from')">
                  <div class="flex items-center gap-1">
                    Period
                    <component :is="sortBy === 'effective_from' ? (sortOrder === 'asc' ? ArrowUp : ArrowDown) : ArrowUpDown" class="w-3 h-3 transition-opacity" :class="sortBy === 'effective_from' ? 'text-primary' : 'opacity-50 group-hover:opacity-100'" />
                  </div>
              </th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-foreground group" @click="handleSort('created_at')">
                  <div class="flex items-center gap-1">
                    Date Issued
                    <component :is="sortBy === 'created_at' ? (sortOrder === 'asc' ? ArrowUp : ArrowDown) : ArrowUpDown" class="w-3 h-3 transition-opacity" :class="sortBy === 'created_at' ? 'text-primary' : 'opacity-50 group-hover:opacity-100'" />
                  </div>
              </th>
              <th class="px-6 py-4 text-center text-xs font-semibold text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-foreground group" @click="handleSort('total_minutes_consumption')">
                  <div class="flex items-center justify-center gap-1">
                    Minutes
                    <component :is="sortBy === 'total_minutes_consumption' ? (sortOrder === 'asc' ? ArrowUp : ArrowDown) : ArrowUpDown" class="w-3 h-3 transition-opacity" :class="sortBy === 'total_minutes_consumption' ? 'text-primary' : 'opacity-50 group-hover:opacity-100'" />
                  </div>
              </th>
              <th class="px-6 py-4 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-foreground group" @click="handleSort('billed_amount')">
                  <div class="flex items-center justify-end gap-1">
                    Amount
                    <component :is="sortBy === 'billed_amount' ? (sortOrder === 'asc' ? ArrowUp : ArrowDown) : ArrowUpDown" class="w-3 h-3 transition-opacity" :class="sortBy === 'billed_amount' ? 'text-primary' : 'opacity-50 group-hover:opacity-100'" />
                  </div>
              </th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Status</th>
              <th class="px-6 py-4 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr v-if="loading">
                <td colspan="7" class="p-4 text-center">Loading invoices...</td>
            </tr>
            <tr v-for="invoice in invoices" :key="invoice.id" class="hover:bg-muted/50 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <FileText class="w-4 h-4" />
                  </div>
                  <span class="font-medium text-foreground">{{ invoice.displayId }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">{{ invoice.period }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ invoice.date }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <Badge variant="secondary" class="font-mono">{{ invoice.minutes }}m</Badge>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right font-medium text-foreground">${{ invoice.amount.toFixed(2) }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="['px-2.5 py-0.5 rounded-full text-xs font-medium border', getStatusColor(invoice.status)]">
                  {{ invoice.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right flex items-center justify-end gap-2">
                <Button variant="ghost" size="sm" @click="viewDetails(invoice)" title="View Details">
                  <Eye class="w-4 h-4" />
                </Button>
                <Button variant="ghost" size="sm" @click="downloadInvoice(invoice.id)" title="Download CSV">
                  <Download class="w-4 h-4" />
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="border-t border-border p-4">
        <CustomPagination
            :modelValue="currentPage"
            :totalPages="lastPage"
            :pageSize="perPage"
            @update:modelValue="(val) => { currentPage = val; fetchInvoices() }"
            @update:pageSize="(val) => { perPage = val; currentPage = 1; fetchInvoices() }"
        />
      </div>
       <div v-if="!loading && invoices.length === 0" class="p-8 text-center text-muted-foreground">
          No invoices found.
      </div>
    </div>
  </div>
</template>
