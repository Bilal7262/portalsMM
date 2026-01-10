<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { companyService, type Company, type CompanyFormData } from '@/services/company'
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
  DialogTrigger,
} from '@/components/ui/dialog'

const companies = ref<Company[]>([])
const isLoading = ref(true)
const isDialogOpen = ref(false)
const isSubmitting = ref(false)

// Form State
const formData = ref<CompanyFormData>({
  name: '',
  email: '',
  phone: '',
  status: 'active'
})
const editingId = ref<number | null>(null)

onMounted(fetchCompanies)

async function fetchCompanies() {
  isLoading.value = true
  try {
    const response = await companyService.getCompanies()
    // Handle both wrapped resource response and direct array
    if (response && (response as any).data) {
        companies.value = (response as any).data
    } else {
        companies.value = response as any
    }
  } catch (error) {
    console.error('Failed to fetch companies', error)
  } finally {
    isLoading.value = false
  }
}

function openAddDialog() {
  editingId.value = null
  formData.value = { name: '', email: '', phone: '', status: 'active' }
  isDialogOpen.value = true
}

function openEditDialog(company: Company) {
  editingId.value = company.id
  formData.value = {
    name: company.name,
    email: company.email,
    phone: company.phone || '',
    status: company.status
  }
  isDialogOpen.value = true
}

async function handleSubmit() {
  isSubmitting.value = true
  try {
    if (editingId.value) {
      await companyService.updateCompany(editingId.value, formData.value)
    } else {
      await companyService.createCompany(formData.value)
    }
    isDialogOpen.value = false
    await fetchCompanies()
  } catch (error) {
    console.error('Submit failed', error)
    // Could show toast error here
  } finally {
    isSubmitting.value = false
  }
}

async function handleDelete(id: number) {
  if (!confirm('Are you sure you want to delete this company?')) return
  try {
    await companyService.deleteCompany(id)
    await fetchCompanies()
  } catch (error) {
    console.error('Delete failed', error)
  }
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold tracking-tight">Companies</h1>
      <Button @click="openAddDialog">Add Company</Button>
    </div>

    <!-- Data Table -->
    <div class="rounded-md border bg-card">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>ID</TableHead>
            <TableHead>Name</TableHead>
            <TableHead>Email</TableHead>
            <TableHead>Phone</TableHead>
            <TableHead>Status</TableHead>
            <TableHead class="text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="isLoading">
            <TableCell colSpan="6" class="h-24 text-center">
              Loading...
            </TableCell>
          </TableRow>
          
          <TableRow v-else-if="companies.length === 0">
             <TableCell colSpan="6" class="h-24 text-center">
              No companies found.
            </TableCell>
          </TableRow>

          <TableRow v-for="company in companies" :key="company.id">
            <TableCell>{{ company.id }}</TableCell>
            <TableCell class="font-medium">{{ company.name }}</TableCell>
            <TableCell>{{ company.email }}</TableCell>
            <TableCell>{{ company.phone || '-' }}</TableCell>
            <TableCell>
              <span 
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                :class="{
                  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': company.status === 'active',
                  'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': company.status === 'suspended' || company.status === 'inactive',
                }"
              >
                {{ company.status }}
              </span>
            </TableCell>
            <TableCell class="text-right space-x-2">
              <Button variant="outline" size="sm" @click="openEditDialog(company)">Edit</Button>
              <Button variant="destructive" size="sm" @click="handleDelete(company.id)">Delete</Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Add/Edit Dialog -->
    <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{{ editingId ? 'Edit Company' : 'Add New Company' }}</DialogTitle>
          <DialogDescription>
            {{ editingId ? 'Update company details.' : 'Create a new company profile.' }}
          </DialogDescription>
        </DialogHeader>
        
        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label htmlFor="name">Company Name</Label>
            <Input id="name" v-model="formData.name" placeholder="Acme Corp" />
          </div>
          <div class="grid gap-2">
            <Label htmlFor="email">Email</Label>
            <Input id="email" type="email" v-model="formData.email" placeholder="admin@acme.com" />
          </div>
          <div class="grid gap-2">
            <Label htmlFor="phone">Phone</Label>
            <Input id="phone" v-model="formData.phone" placeholder="+1234567890" />
          </div>
          <div class="grid gap-2">
            <Label htmlFor="status">Status</Label>
             <!-- Simple select for now, ideally use Shadcn Select -->
             <select 
               id="status" 
               v-model="formData.status"
               class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
             >
               <option value="active">Active</option>
               <option value="inactive">Inactive</option>
               <option value="suspended">Suspended</option>
             </select>
          </div>
        </div>

        <DialogFooter>
           <Button variant="outline" @click="isDialogOpen = false">Cancel</Button>
           <Button @click="handleSubmit" :disabled="isSubmitting">
             {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
           </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
