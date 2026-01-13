<script setup lang="ts">
import { onMounted, ref, reactive } from 'vue'
import { adminService, type Admin } from '@/services/admin'
import { apiService } from '@/services/api'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import Pagination from '@/components/ui/Pagination.vue'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Edit, Trash2 } from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

const admins = ref<Admin[]>([])
const roles = ref<any[]>([])
const isLoading = ref(true)
const isDialogOpen = ref(false)
const isSubmitting = ref(false)

const meta = ref({
  current_page: 1,
  last_page: 1,
  from: 1,
  to: 1,
  total: 0
})

const filters = reactive({
  search: '',
  status: ''
})

// Form State
const formData = ref({
  name: '',
  email: '',
  password: '',
  role_id: '',
  status: 'active'
})
const editingId = ref<number | null>(null)

onMounted(() => {
  fetchAdmins()
  fetchRoles()
})

async function fetchAdmins(page = 1) {
  isLoading.value = true
  try {
    const response = await adminService.getAdmins({
        page,
        search: filters.search,
        status: filters.status
    })
    admins.value = response.data
    meta.value = {
      current_page: response.current_page,
      last_page: response.last_page,
      from: response.from,
      to: response.to,
      total: response.total
    }
  } catch (error) {
    console.error('Failed to fetch admins', error)
  } finally {
    isLoading.value = false
  }
}

// Simple debounce
let timeout: any
const handleSearch = () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        fetchAdmins(1)
    }, 500)
}

async function fetchRoles() {
  try {
    const response = await apiService.get('/admin/admins/roles')
    roles.value = response as any
  } catch (error) {
    console.error('Failed to fetch roles', error)
  }
}

function openAddDialog() {
  editingId.value = null
  formData.value = { name: '', email: '', password: '', role_id: '', status: 'active' }
  isDialogOpen.value = true
}

function openEditDialog(admin: Admin) {
  editingId.value = admin.id
  formData.value = {
    name: admin.name,
    email: admin.email,
    password: '',
    role_id: admin.roles && admin.roles.length > 0 ? admin.roles[0]?.id.toString() || '' : '',
    status: admin.status
  }
  isDialogOpen.value = true
}

async function handleSubmit() {
  isSubmitting.value = true
  try {
    if (editingId.value) {
      await adminService.updateAdmin(editingId.value, formData.value)
    } else {
      await adminService.createAdmin(formData.value)
    }
    isDialogOpen.value = false
    await fetchAdmins(meta.value.current_page)
  } catch (error) {
    console.error('Submit failed', error)
  } finally {
    isSubmitting.value = false
  }
}

async function handleDelete(id: number) {
  if (!confirm('Are you sure you want to delete this admin?')) return
  try {
    await adminService.deleteAdmin(id)
    await fetchAdmins(meta.value.current_page)
  } catch (error) {
    console.error('Delete failed', error)
  }
}
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold tracking-tight">Team Management</h1>
      <Button @click="openAddDialog">Add Team Member</Button>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 bg-white p-4 shadow sm:rounded-lg mb-6 border-0">
      <div class="flex-1">
        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
        <input
          v-model="filters.search"
          type="text"
          id="search"
          placeholder="Search by name or email..."
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm h-10 px-3"
          @input="handleSearch"
        />
      </div>
      <div class="w-full sm:w-48">
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select
          v-model="filters.status"
          id="status"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm h-10 px-3"
          @change="fetchAdmins(1)"
        >
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
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
            <TableHead>Name</TableHead>
            <TableHead>Email</TableHead>
            <TableHead>Role</TableHead>
            <TableHead>Status</TableHead>
            <TableHead class="text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="admins.length === 0 && !isLoading">
             <TableCell colSpan="6" class="h-24 text-center">No admins found matching your criteria.</TableCell>
          </TableRow>

          <TableRow v-for="admin in admins" :key="admin.id">
            <TableCell>{{ admin.id }}</TableCell>
            <TableCell class="font-medium">{{ admin.name }}</TableCell>
            <TableCell>{{ admin.email }}</TableCell>
            <TableCell>
              {{ admin.roles && admin.roles.length > 0 ? admin.roles[0]?.name.replace('admin-', '') || '-' : '-' }}
            </TableCell>
            <TableCell>
              <span 
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="{
                  'bg-green-100 text-green-800': admin.status === 'active',
                  'bg-red-100 text-red-800': admin.status === 'inactive',
                }"
              >
                {{ admin.status }}
              </span>
            </TableCell>
            <TableCell class="text-right space-x-2">
              <Button variant="ghost" size="icon" @click="openEditDialog(admin)" title="Edit">
                <Edit class="w-4 h-4 text-muted-foreground hover:text-foreground" />
              </Button>
              <Button variant="ghost" size="icon" @click="handleDelete(admin.id)" title="Delete">
                <Trash2 class="w-4 h-4 text-destructive/70 hover:text-destructive" />
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <Pagination 
        v-if="meta.total > 0"
        :meta="meta"
        @page-change="fetchAdmins"
      />
    </div>

    <!-- Dialog remains the same -->
    <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{{ editingId ? 'Edit Team Member' : 'Add Team Member' }}</DialogTitle>
          <DialogDescription>
            {{ editingId ? 'Update admin details.' : 'Create a new admin user.' }}
          </DialogDescription>
        </DialogHeader>
        
        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label htmlFor="name">Full Name</Label>
            <Input id="name" v-model="formData.name" placeholder="John Doe" />
          </div>
          <div class="grid gap-2">
            <Label htmlFor="email">Email</Label>
            <Input id="email" type="email" v-model="formData.email" placeholder="john@admin.com" />
          </div>
          <div class="grid gap-2" v-if="!editingId">
            <Label htmlFor="password">Password</Label>
            <Input id="password" type="password" v-model="formData.password" placeholder="Min 8 characters" />
          </div>
          <div class="grid gap-2" v-else>
            <Label htmlFor="password">Password (Leave blank to keep current)</Label>
            <Input id="password" type="password" v-model="formData.password" placeholder="Optional" />
          </div>
          <div class="grid gap-2">
            <Label htmlFor="role">Role</Label>
             <select 
               id="role" 
               v-model="formData.role_id"
               class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
             >
               <option value="" disabled>Select a role</option>
               <option v-for="role in roles" :key="role.id" :value="role.id.toString()">
                 {{ role.name.replace('admin-', '') }}
               </option>
             </select>
          </div>
          <div class="grid gap-2">
            <Label htmlFor="status">Status</Label>
             <select 
               id="status" 
               v-model="formData.status"
               class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
             >
               <option value="active">Active</option>
               <option value="inactive">Inactive</option>
             </select>
          </div>
        </div>

        <DialogFooter>
           <Button variant="outline" @click="isDialogOpen = false">Cancel</Button>
           <Button @click="handleSubmit" :disabled="isSubmitting">
             {{ isSubmitting ? 'Saving...' : 'Save Member' }}
           </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

