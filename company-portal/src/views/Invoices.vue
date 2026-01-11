<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/lib/axios'
import { 
  Download, 
  Search,
  Filter,
  FileText,
  Eye
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

const router = useRouter()
const invoices = ref<any[]>([])
const loading = ref(false)

const fetchInvoices = async () => {
    loading.value = true
    try {
        const response = await api.get('/company/invoices')
        const dataArray = Array.isArray(response.data) ? response.data : (response.data.data || [])
        
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
    } catch (e) {
        console.error("Failed to fetch invoices", e)
    } finally {
        loading.value = false
    }
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

    <!-- Filters -->
    <div class="flex gap-4 items-center bg-card p-4 rounded-xl border border-border shadow-sm">
      <div class="relative flex-1 max-w-sm">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
        <Input placeholder="Search invoice #" class="pl-9 bg-background/50" />
      </div>
      <Button variant="outline">
        <Filter class="w-4 h-4 mr-2" />
        Filter
      </Button>
    </div>

    <!-- Invoices List -->
    <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-muted/50 border-b border-border">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Invoice ID</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Period</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Date Issued</th>
              <th class="px-6 py-4 text-center text-xs font-semibold text-muted-foreground uppercase tracking-wider">Minutes</th>
              <th class="px-6 py-4 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider">Amount</th>
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
       <div v-if="!loading && invoices.length === 0" class="p-8 text-center text-muted-foreground">
          No invoices found.
      </div>
    </div>
  </div>
</template>
