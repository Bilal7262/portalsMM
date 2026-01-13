<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { agentService, type CompanyAgent, type AgentFilters } from '@/services/agent'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Search, Filter, AlertCircle, Edit, Trash2, CheckCircle, GraduationCap } from 'lucide-vue-next'
import CustomPagination from '@/components/CustomPagination.vue'
import AssignDidToAgentModal from '@/components/modals/AssignDidToAgentModal.vue'

const agents = ref<CompanyAgent[]>([])
const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref<string>('all')

const filters = computed<AgentFilters>(() => ({
  search: searchQuery.value,
  status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
  page: 1,
  per_page: 10
}))

const pagination = ref({
  currentPage: 1,
  totalPages: 1,
  total: 0,
  perPage: 10
})

const showAssignModal = ref(false)
const selectedAgent = ref<CompanyAgent | null>(null)


const fetchAgents = async () => {
  loading.value = true
  try {
    const response = await agentService.getAgents({
      ...filters.value,
      page: pagination.value.currentPage,
      per_page: pagination.value.perPage
    })
    agents.value = response.data
    pagination.value.currentPage = response.current_page
    pagination.value.totalPages = response.last_page
    pagination.value.total = response.total
  } catch (error) {
    console.error('Failed to fetch agents:', error)
  } finally {
    loading.value = false
  }
}

const handlePageChange = (page: number) => {
  pagination.value.currentPage = page
  fetchAgents()
}

const handlePerPageChange = (size: number) => {
  pagination.value.perPage = size
  pagination.value.currentPage = 1
  fetchAgents()
}

const editAgent = (agent: CompanyAgent) => {
    selectedAgent.value = agent
    showAssignModal.value = true
}


const deleteAgent = async (id: number) => {
    if(!confirm("Are you sure you want to delete this agent?")) return
    try {
        await agentService.deleteAgent(id)
        fetchAgents()
    } catch(e) {
        console.error("Failed to delete", e)
    }
}

const approveAgent = async (agent: CompanyAgent, status: 'training' | 'active') => {
    if(!confirm(`Approve ${agent.name} for ${status}?`)) return
    try {
        await agentService.approveAgent(agent.id, status)
        fetchAgents()
    } catch(e) {
        console.error("Failed to approve", e)
    }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'active': return 'bg-green-500/10 text-green-500 border-green-200'
    case 'inactive': return 'bg-gray-500/10 text-gray-500 border-gray-200'
    case 'request': return 'bg-blue-500/10 text-blue-500 border-blue-200'
    case 'training': return 'bg-yellow-500/10 text-yellow-500 border-yellow-200'
    default: return 'bg-gray-500/10 text-gray-500'
  }
}

onMounted(() => {
  fetchAgents()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold tracking-tight text-foreground">Company Agents</h2>
        <p class="text-muted-foreground mt-1">Manage AI agents assigned to companies</p>
      </div>
    </div>


    <!-- Filters -->
    <div class="bg-card border border-border rounded-xl p-4 flex flex-col md:flex-row gap-4 items-center justify-between shadow-sm">
      <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
        <div class="relative w-full sm:w-64">
          <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input 
            v-model="searchQuery" 
            placeholder="Search agents or companies..." 
            class="pl-9 bg-background/50"
            @input="fetchAgents"
          />
        </div>
        <Select v-model="statusFilter" @update:modelValue="fetchAgents">
          <SelectTrigger class="w-full sm:w-40">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="inactive">Inactive</SelectItem>
            <SelectItem value="request">Request</SelectItem>
            <SelectItem value="training">Training</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
      <Table>
        <TableHeader class="bg-muted/50">
          <TableRow>
            <TableHead>Agent Name</TableHead>
            <TableHead>Company</TableHead>
            <TableHead>Voice</TableHead>
            <TableHead>DID</TableHead>
            <TableHead>Status</TableHead>
            <TableHead class="text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="loading">
            <TableCell colspan="6" class="h-24 text-center">
              <div class="flex items-center justify-center gap-2 text-muted-foreground">
                <div class="w-4 h-4 border-2 border-primary border-r-transparent rounded-full animate-spin"></div>
                Loading agents...
              </div>
            </TableCell>
          </TableRow>
          
          <TableRow v-else-if="agents.length === 0">
            <TableCell colspan="6" class="h-48 text-center">
              <div class="flex flex-col items-center justify-center text-muted-foreground gap-2">
                <AlertCircle class="w-8 h-8 opacity-50" />
                <p>No agents found matching your criteria</p>
              </div>
            </TableCell>
          </TableRow>

          <TableRow v-for="agent in agents" :key="agent.id" class="group hover:bg-muted/50 transition-colors">
            <TableCell class="font-medium">
               <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                    <span class="text-xs font-bold">{{ agent.name.charAt(0) }}</span>
                  </div>
                  {{ agent.name }}
               </div>
            </TableCell>
            <TableCell>
                <div class="flex flex-col">
                    <span class="font-medium">{{ agent.company?.business_name }}</span>
                    <span class="text-xs text-muted-foreground">{{ agent.company?.owner_name }}</span>
                </div>
            </TableCell>
            <TableCell>
                <Badge variant="outline">{{ agent.admin_voice?.name || 'N/A' }}</Badge>
            </TableCell>
             <TableCell>
                <span v-if="agent.did" class="font-mono text-xs bg-muted px-2 py-1 rounded">{{ agent.did.did_number }}</span>
                <span v-else class="text-muted-foreground text-xs italic">Pending Assignment</span>
            </TableCell>
            <TableCell>
              <Badge variant="secondary" :class="getStatusColor(agent.status)">
                {{ agent.status }}
              </Badge>
            </TableCell>
            <TableCell class="text-right">
              <div v-if="agent.status === 'request'" class="flex items-center justify-end gap-2">
                <Button variant="ghost" size="icon" @click="approveAgent(agent, 'training')" title="Approve for Training">
                  <GraduationCap class="w-4 h-4 text-yellow-600" />
                </Button>
                <Button variant="ghost" size="icon" @click="approveAgent(agent, 'active')" title="Approve Active">
                  <CheckCircle class="w-4 h-4 text-green-600" />
                </Button>
              </div>
              <div v-else class="flex items-center justify-end gap-2">
                <Button variant="ghost" size="icon" @click="editAgent(agent)">
                  <Edit class="w-4 h-4 text-muted-foreground hover:text-foreground" />
                </Button>
                 <Button variant="ghost" size="icon" @click="deleteAgent(agent.id)">
                  <Trash2 class="w-4 h-4 text-destructive/70 hover:text-destructive" />
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
      
      <div class="border-t border-border p-4">
        <CustomPagination
            :modelValue="pagination.currentPage"
            :totalPages="pagination.totalPages"
            :pageSize="pagination.perPage"
            @update:modelValue="handlePageChange"
            @update:pageSize="handlePerPageChange"
        />
      </div>
    </div>
    
    <AssignDidToAgentModal 
        :open="showAssignModal" 
        :agent="selectedAgent"
        @update:open="showAssignModal = $event"
        @saved="fetchAgents"
    />
  </div>
</template>
