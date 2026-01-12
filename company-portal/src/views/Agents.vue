<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import api from '@/lib/axios'
import CustomPagination from '@/components/CustomPagination.vue'
import { 
  Bot, 
  Search,
  Plus,
  Edit,
  Trash2
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Label } from '@/components/ui/label'

const agents = ref<any[]>([])
const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref('ALL')

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = ref(10)
const totalAgents = ref(0)

// Modal State
const isModalOpen = ref(false)
const isEditMode = ref(false)
const submitting = ref(false)
const voices = ref<any[]>([])

const form = reactive({
    id: null as number | null,
    name: '',
    admin_voice_id: '', 
    script: '',
    quantity: 1
})

const fetchAgents = async () => {
    loading.value = true
    try {
        const params: any = {
            page: currentPage.value,
            per_page: perPage.value,
            search: searchQuery.value
        }
        if (statusFilter.value !== 'ALL') {
            params.status = statusFilter.value
        }

        const response = await api.get('/company/agents', { params })
        agents.value = response.data.data
        
        currentPage.value = response.data.current_page
        lastPage.value = response.data.last_page
        perPage.value = response.data.per_page
        totalAgents.value = response.data.total

    } catch (e) {
        console.error("Failed to fetch agents", e)
    } finally {
        loading.value = false
    }
}

const fetchVoices = async () => {
    try {
        const response = await api.get('/company/agents/available-voices')
        voices.value = response.data
    } catch(e) {
        console.error("Failed to fetch voices")
    }
}

const openCreateModal = () => {
    isEditMode.value = false
    form.id = null
    form.name = ''
    form.admin_voice_id = ''
    form.script = ''
    form.quantity = 1
    fetchVoices()
    isModalOpen.value = true
}

const openEditModal = (agent: any) => {
    isEditMode.value = true
    form.id = agent.id
    form.name = agent.name
    form.admin_voice_id = agent.admin_voice_id?.toString()
    form.script = agent.script
    form.quantity = agent.quantity || 1
    fetchVoices()
    isModalOpen.value = true
}

const submitForm = async () => {
    submitting.value = true
    try {
        if (isEditMode.value && form.id) {
            await api.put(`/company/agents/${form.id}`, {
                name: form.name,
                script: form.script,
                admin_voice_id: form.admin_voice_id 
            })
        } else {
            await api.post('/company/agents', {
                name: form.name,
                admin_voice_id: form.admin_voice_id,
                script: form.script,
                quantity: form.quantity
            })
        }
        isModalOpen.value = false
        fetchAgents()
    } catch (e) {
        console.error("Failed to save agent", e)
        alert("Failed to save agent")
    } finally {
        submitting.value = false
    }
}

const deleteAgent = async (agent: any) => {
    if(!confirm(`Delete agent ${agent.name}?`)) return
    try {
        await api.delete(`/company/agents/${agent.id}`)
        fetchAgents()
    } catch(e) {
        console.error("Failed to delete", e)
    }
}

onMounted(() => {
    fetchAgents()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-3xl font-bold tracking-tight text-foreground">My Agents</h2>
        <p class="text-muted-foreground mt-1">Manage your AI agents and scripts</p>
      </div>
      <Button @click="openCreateModal">
        <Plus class="w-4 h-4 mr-2" />
        Request Agent
      </Button>
    </div>

    <!-- Filters -->
    <div class="bg-card border border-border rounded-xl p-4 flex flex-col md:flex-row gap-4 items-center justify-between">
         <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto text-sm">
             <div class="relative w-full sm:w-64">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="searchQuery" 
                    placeholder="Search agents..." 
                    class="pl-9 h-9"
                    @input="fetchAgents" 
                />
            </div>
             <Select v-model="statusFilter" @update:modelValue="fetchAgents">
                <SelectTrigger class="w-full sm:w-56 h-9">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="ALL">All Statuses</SelectItem>
                    <SelectItem value="active">Active</SelectItem>
                    <SelectItem value="request">Requested</SelectItem>
                    <SelectItem value="training">Training</SelectItem>
                    <SelectItem value="inactive">Inactive</SelectItem>
                </SelectContent>
            </Select>
        </div>
    </div>

    <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-muted/50 border-b border-border">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase">Name</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase">Voice</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase">DID</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-muted-foreground uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <tr v-if="loading">
                    <td colspan="5" class="p-4 text-center">Loading...</td>
                </tr>
                <tr v-for="agent in agents" :key="agent.id" class="hover:bg-muted/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap font-medium">{{ agent.name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-muted-foreground">{{ agent.admin_voice?.name || 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-muted-foreground">{{ agent.did?.did_number || 'Pending' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                         <Badge :variant="agent.status === 'active' ? 'default' : 'secondary'">
                            {{ agent.status }}
                        </Badge>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                        <Button variant="ghost" size="sm" @click="openEditModal(agent)">
                            <Edit class="w-4 h-4" />
                        </Button>
                        <Button variant="ghost" size="sm" @click="deleteAgent(agent)">
                            <Trash2 class="w-4 h-4 text-destructive" />
                        </Button>
                    </td>
                </tr>
                 <tr v-if="!loading && agents.length === 0">
                    <td colspan="5" class="p-8 text-center text-muted-foreground">No agents found.</td>
                </tr>
            </tbody>
        </table>
         <div class="border-t border-border p-4">
            <CustomPagination
                :modelValue="currentPage"
                :totalPages="lastPage"
                :pageSize="perPage"
                @update:modelValue="(val) => { currentPage = val; fetchAgents() }"
                @update:pageSize="(val) => { perPage = val; currentPage = 1; fetchAgents() }"
            />
        </div>
    </div>
  </div>

  <Dialog v-model:open="isModalOpen">
      <DialogContent>
          <DialogHeader>
              <DialogTitle>{{ isEditMode ? 'Edit Agent' : 'Request New Agent' }}</DialogTitle>
              <DialogDescription>Configure agent details and script.</DialogDescription>
          </DialogHeader>
          <div class="grid gap-4 py-4">
              <div class="grid gap-2">
                  <Label>Name</Label>
                  <Input v-model="form.name" placeholder="e.g. Sales Bot 1" />
              </div>
               <div class="grid gap-2">
                  <Label>Voice</Label>
                   <Select v-model="form.admin_voice_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Select a voice" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="v in voices" :key="v.id" :value="v.id.toString()">
                                {{ v.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
              </div>
              <div class="grid gap-2">
                  <Label>Script</Label>
                  <textarea 
                    v-model="form.script" 
                    class="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    placeholder="Enter agent script..."
                  ></textarea>
              </div>
               <div v-if="!isEditMode" class="grid gap-2">
                  <Label>Quantity</Label>
                  <Input v-model="form.quantity" type="number" min="1" />
              </div>
          </div>
          <DialogFooter>
              <Button variant="outline" @click="isModalOpen = false">Cancel</Button>
              <Button @click="submitForm" :disabled="submitting">
                  {{ submitting ? 'Saving...' : 'Save' }}
              </Button>
          </DialogFooter>
      </DialogContent>
  </Dialog>
</template>
