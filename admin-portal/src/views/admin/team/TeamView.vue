<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { adminService, type Admin } from '@/services/admin'
import { apiService } from '@/services/api'
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

const admins = ref<Admin[]>([])
const roles = ref<any[]>([])
const isLoading = ref(true)
const isDialogOpen = ref(false)
const isSubmitting = ref(false)

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

async function fetchAdmins() {
  isLoading.value = true
  try {
    const response = await adminService.getAdmins()
    admins.value = response as any
  } catch (error) {
    console.error('Failed to fetch admins', error)
  } finally {
    isLoading.value = false
  }
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
    await fetchAdmins()
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
    await fetchAdmins()
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

    <div class="rounded-md border bg-card">
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
          <TableRow v-if="isLoading">
            <TableCell colSpan="6" class="h-24 text-center">Loading...</TableCell>
          </TableRow>
          
          <TableRow v-else-if="admins.length === 0">
             <TableCell colSpan="6" class="h-24 text-center">No admins found.</TableCell>
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
              <Button variant="outline" size="sm" @click="openEditDialog(admin)">Edit</Button>
              <Button variant="destructive" size="sm" @click="handleDelete(admin.id)">Delete</Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

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
