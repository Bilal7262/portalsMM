<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { invoiceService, type Invoice } from '@/services/invoice'
import Button from '@/components/ui/button/Button.vue'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import Label from '@/components/ui/label/Label.vue'

const invoices = ref<Invoice[]>([])
const isLoading = ref(true)

const isUpdateDialogOpen = ref(false)
const selectedInvoice = ref<Invoice | null>(null)
const newStatus = ref('')

onMounted(fetchInvoices)

async function fetchInvoices() {
  isLoading.value = true
  try {
    const response = await invoiceService.getInvoices()
    invoices.value = (response as any).data || response
  } catch (error) {
    console.error('Failed to fetch invoices', error)
  } finally {
    isLoading.value = false
  }
}

function openUpdateDialog(invoice: Invoice) {
  selectedInvoice.value = invoice
  newStatus.value = invoice.status
  isUpdateDialogOpen.value = true
}

async function handleUpdateStatus() {
  if (!selectedInvoice.value) return
  try {
    await invoiceService.updateInvoiceStatus(selectedInvoice.value.id, newStatus.value)
    isUpdateDialogOpen.value = false
    await fetchInvoices()
  } catch (error) {
    console.error('Update failed', error)
  }
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString()
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold tracking-tight">Invoices</h1>
    </div>

    <!-- Data Table -->
    <div class="rounded-md border bg-card">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>ID</TableHead>
            <TableHead>Company</TableHead>
            <TableHead>DID</TableHead>
            <TableHead>Period</TableHead>
            <TableHead>Minutes</TableHead>
            <TableHead>Amount</TableHead>
            <TableHead>Status</TableHead>
            <TableHead class="text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="isLoading">
            <TableCell colSpan="8" class="h-24 text-center">Loading...</TableCell>
          </TableRow>
          <TableRow v-else-if="invoices.length === 0">
             <TableCell colSpan="8" class="h-24 text-center">No Invoices found.</TableCell>
          </TableRow>

          <TableRow v-for="inv in invoices" :key="inv.id">
            <TableCell>{{ inv.id }}</TableCell>
            <TableCell>
              {{ inv.company_did?.company.name || '-' }}
            </TableCell>
            <TableCell>
              {{ inv.company_did?.did.did_number || '-' }}
            </TableCell>
            <TableCell class="text-xs">
              {{ formatDate(inv.effective_from) }} - {{ formatDate(inv.effective_to) }}
            </TableCell>
            <TableCell>{{ inv.total_minutes_consumption.toFixed(2) }}</TableCell>
            <TableCell class="font-bold">${{ inv.billed_amount.toFixed(2) }}</TableCell>
            <TableCell>
              <span 
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                :class="{
                  'bg-green-100 text-green-800': inv.status === 'paid',
                  'bg-yellow-100 text-yellow-800': inv.status === 'draft',
                  'bg-red-100 text-red-800': inv.status === 'overdue' || inv.status === 'cancelled',
                }"
              >
                {{ inv.status }}
              </span>
            </TableCell>
            <TableCell class="text-right">
              <Button variant="outline" size="sm" @click="openUpdateDialog(inv)">
                Update Status
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Update Status Dialog -->
    <Dialog :open="isUpdateDialogOpen" @update:open="isUpdateDialogOpen = $event">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Update Invoice Status</DialogTitle>
          <DialogDescription>
            Change status for Invoice #{{ selectedInvoice?.id }}
          </DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label>Status</Label>
             <select 
               v-model="newStatus"
               class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
             >
               <option value="draft">Draft</option>
               <option value="paid">Paid</option>
               <option value="overdue">Overdue</option>
               <option value="cancelled">Cancelled</option>
             </select>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="isUpdateDialogOpen = false">Cancel</Button>
          <Button @click="handleUpdateStatus">Update</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
