<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { didService, type DID, type DidFormData } from '@/services/did'
import { companyService, type Company } from '@/services/company'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
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

const dids = ref<DID[]>([])
const companies = ref<Company[]>([])
const isLoading = ref(true)
const search = ref('')
const selectedStatus = ref('all')

// Add DID State
const isAddDialogOpen = ref(false)
const addFormData = ref({ did_number: '', status: 'available' })

// Assign DID State
const isAssignDialogOpen = ref(false)
const assignFormData = ref({ company_id: '', price_per_min: '0.01' })
const selectedDidForAssignment = ref<DID | null>(null)

onMounted(async () => {
  await fetchDids()
  await fetchCompanies()
})

async function fetchDids() {
  isLoading.value = true
  try {
    const params: any = {}
    if (search.value) params.search = search.value
    if (selectedStatus.value !== 'all') params.status = selectedStatus.value
    
    // Explicitly casting status to match expected type if strictly typed, or loose
    const response = await didService.getDids(params)
    // Handle API resource wrapper
    dids.value = (response as any).data || response
  } catch (error) {
    console.error('Failed to fetch DIDs', error)
  } finally {
    isLoading.value = false
  }
}

async function fetchCompanies() {
  try {
    const response = await companyService.getCompanies()
    companies.value = (response as any).data || response
  } catch (error) {
    console.error('Failed to fetch companies', error)
  }
}

// Watchers for filters
watch([search, selectedStatus], () => {
  fetchDids()
})

async function handleAddDid() {
  try {
    await didService.createDid(addFormData.value as DidFormData)
    isAddDialogOpen.value = false
    addFormData.value = { did_number: '', status: 'available' }
    await fetchDids()
  } catch (error) {
    console.error('Create DID failed', error)
  }
}

function openAssignDialog(did: DID) {
  selectedDidForAssignment.value = did
  assignFormData.value = { company_id: '', price_per_min: '0.01' }
  isAssignDialogOpen.value = true
}

async function handleAssignDid() {
  if (!selectedDidForAssignment.value || !assignFormData.value.company_id) return
  
  try {
    await didService.assignDid({
      did_id: selectedDidForAssignment.value.id,
      company_id: Number(assignFormData.value.company_id),
      price_per_min: Number(assignFormData.value.price_per_min)
    })
    isAssignDialogOpen.value = false
    await fetchDids()
  } catch (error) {
    console.error('Assign DID failed', error)
  }
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold tracking-tight">DID Management</h1>
      <Button @click="isAddDialogOpen = true">Add New DID</Button>
    </div>

    <!-- Filters -->
    <div class="flex gap-4 mb-6">
      <Input v-model="search" placeholder="Search number..." class="max-w-xs" />
      <select 
        v-model="selectedStatus"
        class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
      >
        <option value="all">All Status</option>
        <option value="available">Available</option>
        <option value="assigned">Assigned</option>
      </select>
    </div>

    <!-- Data Table -->
    <div class="rounded-md border bg-card">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>ID</TableHead>
            <TableHead>DID Number</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Assigned To</TableHead>
            <TableHead>Rate/Min</TableHead>
            <TableHead class="text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="isLoading">
            <TableCell colSpan="6" class="h-24 text-center">Loading...</TableCell>
          </TableRow>
          <TableRow v-else-if="dids.length === 0">
             <TableCell colSpan="6" class="h-24 text-center">No DIDs found.</TableCell>
          </TableRow>

          <TableRow v-for="did in dids" :key="did.id">
            <TableCell>{{ did.id }}</TableCell>
            <TableCell class="font-medium">{{ did.did_number }}</TableCell>
            <TableCell>
              <span 
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="{
                  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': did.status === 'available',
                  'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': did.status === 'assigned',
                }"
              >
                {{ did.status }}
              </span>
            </TableCell>
            <TableCell>
              <span v-if="did.company_did">{{ did.company_did.company.name }}</span>
              <span v-else class="text-muted-foreground">-</span>
            </TableCell>
            <TableCell>
              <span v-if="did.company_did">${{ did.company_did.price_per_min }}</span>
              <span v-else>-</span>
            </TableCell>
            <TableCell class="text-right space-x-2">
              <Button 
                v-if="did.status === 'available'" 
                variant="default" 
                size="sm" 
                @click="openAssignDialog(did)"
              >
                Assign
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Add DID Dialog -->
    <Dialog :open="isAddDialogOpen" @update:open="isAddDialogOpen = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add New DID</DialogTitle>
          <DialogDescription>Add a new number to the pool.</DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label>DID Number</Label>
            <Input v-model="addFormData.did_number" placeholder="+1234567890" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="isAddDialogOpen = false">Cancel</Button>
          <Button @click="handleAddDid">Save</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Assign DID Dialog -->
    <Dialog :open="isAssignDialogOpen" @update:open="isAssignDialogOpen = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Assign DID</DialogTitle>
          <DialogDescription>
            Assign {{ selectedDidForAssignment?.did_number }} to a company.
          </DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label>Select Company</Label>
             <select 
               v-model="assignFormData.company_id"
               class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
             >
               <option value="" disabled>Select a company</option>
               <option v-for="c in companies" :key="c.id" :value="c.id">
                 {{ c.name }}
               </option>
             </select>
          </div>
          <div class="grid gap-2">
            <Label>Price Per Minute ($)</Label>
            <Input v-model="assignFormData.price_per_min" type="number" step="0.01" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="isAssignDialogOpen = false">Cancel</Button>
          <Button @click="handleAssignDid">Assign</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
