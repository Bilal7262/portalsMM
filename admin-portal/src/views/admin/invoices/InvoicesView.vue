<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold tracking-tight">Invoices</h2>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 bg-white p-4 shadow sm:rounded-lg">
      <div class="w-full sm:w-48">
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select
          v-model="filters.status"
          id="status"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @change="fetchInvoices(1)"
        >
          <option value="">All Statuses</option>
          <option value="draft">Draft</option>
          <option value="generated">Generated</option>
          <option value="sent">Sent</option>
          <option value="paid">Paid</option>
          <option value="overdue">Overdue</option>
          <option value="cancelled">Cancelled</option>
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
            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">ID</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Company</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Amount</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="invoice in invoices" :key="invoice.id">
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">#{{ invoice.id }}</td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ invoice.company_name }}</td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${{ invoice.billed_amount }}</td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
               <span :class="[
                  invoice.status === 'paid' ? 'bg-green-100 text-green-800' : 
                  invoice.status === 'draft' ? 'bg-gray-100 text-gray-800' :
                  invoice.status === 'overdue' ? 'bg-red-100 text-red-800' :
                  'bg-blue-100 text-blue-800',
                  'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium'
                ]">
                {{ invoice.status }}
              </span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <button 
                v-if="invoice.status === 'draft'"
                @click="finalizeInvoice(invoice)"
                class="text-indigo-600 hover:text-indigo-900 font-semibold"
              >
                Finalize
              </button>
            </td>
          </tr>
          <tr v-if="invoices.length === 0 && !loading">
            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
              No invoices found matching your criteria.
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <Pagination 
        v-if="meta.total > 0"
        :meta="meta"
        @page-change="fetchInvoices"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { invoiceService } from '@/services/invoice'
import Pagination from '@/components/ui/Pagination.vue'

const invoices = ref<any[]>([])
const loading = ref(false)
const meta = ref({
  current_page: 1,
  last_page: 1,
  from: 1,
  to: 1,
  total: 0
})

const filters = reactive({
  status: ''
})

const fetchInvoices = async (page = 1) => {
    loading.value = true
    try {
        const response = await invoiceService.getInvoices({
            page,
            status: filters.status
        })
        invoices.value = response.data.map((inv: any) => ({
            ...inv,
            company_name: inv.company_did?.company?.business_name || 'N/A'
        }))
        meta.value = {
          current_page: response.current_page,
          last_page: response.last_page,
          from: response.from,
          to: response.to,
          total: response.total
        }
    } catch (error) {
        console.error('Failed to fetch invoices:', error)
    } finally {
        loading.value = false
    }
}

const finalizeInvoice = async (invoice: any) => {
    try {
        await invoiceService.updateInvoiceStatus(invoice.id, 'generated')
        invoice.status = 'generated'
    } catch (error) {
        console.error('Failed to finalize invoice:', error)
    }
}

onMounted(() => {
    fetchInvoices()
})
</script>

