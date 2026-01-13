<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { invoiceService } from '@/services/invoice'
import { ArrowLeft, Eye } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const invoiceId = ref(Number(route.params.id))
const invoice = ref<any>(null)
const items = ref<any[]>([])
const loading = ref(false)
const filters = ref({
  search: ''
})

let searchTimeout: NodeJS.Timeout

const fetchInvoiceItems = async () => {
  loading.value = true
  try {
    const response = await invoiceService.getInvoiceItems(invoiceId.value, filters.value)
    invoice.value = response.invoice
    items.value = response.items
  } catch (error) {
    console.error('Failed to fetch invoice items:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchInvoiceItems()
  }, 500)
}

const goBack = () => {
  router.push('/invoices')
}

onMounted(() => {
  fetchInvoiceItems()
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
          <h2 class="text-3xl font-bold tracking-tight text-foreground">Invoice Items</h2>
          <p class="text-muted-foreground mt-1">{{ invoice?.invoice_number || 'Loading...' }}</p>
          <p class="text-sm text-gray-500 mt-1" v-if="invoice">
            {{ invoice.company?.business_name }} | {{ new Date(invoice.effective_from).toLocaleDateString() }} - {{ new Date(invoice.effective_to).toLocaleDateString() }}
          </p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 bg-white p-4 shadow sm:rounded-lg">
      <div class="flex-1">
        <label for="search" class="block text-sm font-medium text-gray-700">Search Agent/DID</label>
        <input
          type="text"
          id="search"
          v-model="filters.search"
          @input="handleSearch"
          placeholder="Search by name or number..."
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm h-10 px-3"
        />
      </div>
    </div>

    <div class="bg-white shadow sm:rounded-lg overflow-hidden relative">
      <div v-if="loading" class="absolute inset-0 bg-white/50 flex items-center justify-center z-10">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>

      <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
          <tr>
            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Agent/DID</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Company</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total Minutes</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total Calls</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total Sales</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Rate/Min</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Subtotal</th>
            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="item in items" :key="item.id">
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
              {{ item.agent?.name || 'N/A' }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.agent?.company?.business_name || 'N/A' }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.total_minutes }} min
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.total_calls }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20" v-if="item.total_sales > 0">
                {{ item.total_sales }}
              </span>
              <span v-else>{{ item.total_sales }}</span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              ${{ item.rate_per_min }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 font-semibold">
              ${{ item.subtotal }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <button 
                @click="router.push(`/invoice-items/${item.id}/calls`)"
                class="text-green-600 hover:text-green-900"
                title="View Calls"
              >
                <Eye class="w-5 h-5" />
              </button>
            </td>
          </tr>
          <tr v-if="items.length === 0 && !loading">
            <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
              No items found for this invoice.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
