<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '@/lib/axios'
import { 
  Plus, 
  Search, 
  MoreVertical, 
  Mail, 
  Shield, 
  User as UserIcon,
  Filter
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"

const users = ref<any[]>([])
const loading = ref(false)
const searchQuery = ref('')

const fetchUsers = async () => {
    loading.value = true
    try {
        const response = await api.get('/company/users')
        users.value = response.data.map((u: any) => ({
            id: u.id,
            name: u.name,
            email: u.email,
            role: u.roles?.[0]?.name.replace('company-', '') || 'user',
            roleDisplay: u.roles?.[0]?.display_name || 'User',
            status: u.status,
            avatar: null,
            joinedDate: new Date(u.created_at).toLocaleDateString()
        }))
    } catch (e) {
        console.error("Failed to fetch users", e)
    } finally {
        loading.value = false
    }
}

const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value
  const q = searchQuery.value.toLowerCase()
  return users.value.filter(u => 
    u.name.toLowerCase().includes(q) || 
    u.email.toLowerCase().includes(q) || 
    u.roleDisplay.toLowerCase().includes(q)
  )
})

const showCreateDialog = ref(false)
const showEditDialog = ref(false)
const newUser = ref({ name: '', email: '', role: 'user', password: 'password' })
const editingUser = ref<any>(null)

const handleCreateUser = async () => {
    try {
        await api.post('/company/users', newUser.value)
        showCreateDialog.value = false
        newUser.value = { name: '', email: '', role: 'user', password: 'password' }
        fetchUsers()
    } catch (e) {
        console.error("Failed to create user", e)
        alert("Failed to create user. Email might be taken.")
    }
}

const openEditDialog = (user: any) => {
    editingUser.value = { ...user }
    showEditDialog.value = true
}

const handleUpdateUser = async () => {
    try {
        await api.put(`/company/users/${editingUser.value.id}`, {
            name: editingUser.value.name,
            email: editingUser.value.email,
            role: editingUser.value.role,
            status: editingUser.value.status
        })
        showEditDialog.value = false
        fetchUsers()
    } catch (e) {
        console.error("Failed to update user", e)
        alert("Failed to update user.")
    }
}

const handleDeleteUser = async (id: number) => {
    if (!confirm('Are you sure you want to remove this member?')) return
    try {
        await api.delete(`/company/users/${id}`)
        fetchUsers()
    } catch (e: any) {
        console.error("Failed to delete user", e)
        alert(e.response?.data?.message || "Failed to delete user.")
    }
}

onMounted(() => {
    fetchUsers()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-3xl font-bold tracking-tight text-foreground">Team Members</h2>
        <p class="text-muted-foreground mt-1">Manage your team and their permissions</p>
      </div>
      <Button size="lg" class="shadow-lg shadow-primary/20" @click="showCreateDialog = true">
        <Plus class="w-5 h-5 mr-2" />
        Add Member
      </Button>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 items-center bg-card p-4 rounded-xl border border-border shadow-sm">
      <div class="relative w-full sm:w-96">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
        <Input 
          v-model="searchQuery"
          placeholder="Search by name, email or role..." 
          class="pl-9 bg-background/50 border-input"
        />
      </div>
    </div>

    <!-- Users Grid -->
    <div v-if="loading" class="text-center py-10">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      <p class="mt-2 text-muted-foreground">Loading users...</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="user in filteredUsers" 
        :key="user.id"
        class="group relative bg-card hover:bg-card/80 border border-border rounded-xl p-6 transition-all duration-300 hover:shadow-lg hover:-translate-y-1"
      >
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-4">
            <div class="relative">
              <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-lg font-bold text-primary">
                 {{ user.name.charAt(0) }}
              </div>
              <div 
                class="absolute -bottom-1 -right-1 w-4 h-4 border-2 border-card rounded-full"
                :class="user.status === 'active' ? 'bg-green-500' : 'bg-gray-400'"
              ></div>
            </div>
            <div>
              <h3 class="font-semibold text-foreground group-hover:text-primary transition-colors">{{ user.name }}</h3>
              <p class="text-sm text-muted-foreground flex items-center gap-1.5">
                <Shield class="w-3 h-3" />
                {{ user.roleDisplay }}
              </p>
            </div>
          </div>
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button variant="ghost" size="icon" class="opacity-0 group-hover:opacity-100 transition-opacity -mr-2">
                <MoreVertical class="w-4 h-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem @click="openEditDialog(user)">Edit Details</DropdownMenuItem>
              <DropdownMenuItem @click="handleDeleteUser(user.id)" class="text-destructive">Remove User</DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>

        <div class="space-y-3 pt-2 border-t border-border/50">
          <div class="flex items-center text-sm text-muted-foreground">
            <Mail class="w-4 h-4 mr-3 opacity-70" />
            <span class="truncate">{{ user.email }}</span>
          </div>
          <div class="flex items-center text-sm text-muted-foreground">
            <UserIcon class="w-4 h-4 mr-3 opacity-70" />
            Joined {{ user.joinedDate }}
          </div>
        </div>
      </div>
    </div>
    
    <!-- Create Dialog -->
     <Dialog v-model:open="showCreateDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add New Member</DialogTitle>
          <DialogDescription>Create a new user for your company.</DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <label class="text-sm font-medium">Name</label>
            <Input v-model="newUser.name" placeholder="John Doe" />
          </div>
          <div class="grid gap-2">
            <label class="text-sm font-medium">Email</label>
            <Input v-model="newUser.email" placeholder="john@example.com" />
          </div>
          <div class="grid gap-2">
            <label class="text-sm font-medium">Password</label>
            <Input v-model="newUser.password" type="password" placeholder="Min 8 characters" />
          </div>
          <div class="grid gap-2">
            <label class="text-sm font-medium">Role</label>
            <Select v-model="newUser.role">
              <SelectTrigger>
                <SelectValue placeholder="Select role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="manager">Manager</SelectItem>
                <SelectItem value="user">User</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="showCreateDialog = false">Cancel</Button>
          <Button @click="handleCreateUser">Create User</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Edit Dialog -->
    <Dialog v-model:open="showEditDialog">
      <DialogContent v-if="editingUser">
        <DialogHeader>
          <DialogTitle>Edit Member</DialogTitle>
          <DialogDescription>Update user details and permissions.</DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <label class="text-sm font-medium">Name</label>
            <Input v-model="editingUser.name" />
          </div>
          <div class="grid gap-2">
            <label class="text-sm font-medium">Email</label>
            <Input v-model="editingUser.email" />
          </div>
          <div class="grid gap-2">
            <label class="text-sm font-medium">Role</label>
            <Select v-model="editingUser.role">
              <SelectTrigger>
                <SelectValue placeholder="Select role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="manager">Manager</SelectItem>
                <SelectItem value="user">User</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="grid gap-2">
            <label class="text-sm font-medium">Status</label>
            <Select v-model="editingUser.status">
              <SelectTrigger>
                <SelectValue placeholder="Select status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="inactive">Inactive</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="showEditDialog = false">Cancel</Button>
          <Button @click="handleUpdateUser">Save Changes</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

  </div>
</template>
