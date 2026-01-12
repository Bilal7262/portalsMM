<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '@/lib/axios'
import { 
  Search, 
  Filter, 
  Activity,
  User,
  Phone,
  Calendar
} from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

const logs = ref<any[]>([])
const loading = ref(false)
const searchQuery = ref('')

const fetchLogs = async () => {
    loading.value = true
    try {
        const response = await api.get('/company/activity-logs')
        // The API returns paginated data inside 'data' property
        const logsData = response.data.data || []
        
        logs.value = logsData.map((log: any) => ({
            id: log.id,
            type: log.activity_type,
            details: log.activity_details,
            // The relationship is named 'user' in the backend response now
            user: log.user?.name || log.company_user?.name || 'System',
            timestamp: new Date(log.created_at).toLocaleString()
        }))
    } catch (e) {
        console.error("Failed to fetch activity logs", e)
    } finally {
        loading.value = false
    }
}

const filteredLogs = computed(() => {
    if (!searchQuery.value) return logs.value
    const query = searchQuery.value.toLowerCase()
    return logs.value.filter(log => 
        log.details.toLowerCase().includes(query) ||
        log.user.toLowerCase().includes(query) ||
        log.type.toLowerCase().includes(query)
    )
})

const getActivityIcon = (type: string) => {
    if (type.includes('USER')) return User
    if (type.includes('CALL')) return Phone
    return Activity
}

const getActivityColor = (type: string) => {
    if (type.includes('CREATED')) return 'bg-green-500/15 text-green-600'
    if (type.includes('UPDATED')) return 'bg-blue-500/15 text-blue-600'
    if (type.includes('DELETED')) return 'bg-red-500/15 text-red-600'
    return 'bg-gray-500/15 text-gray-600'
}

onMounted(() => {
    fetchLogs()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-3xl font-bold tracking-tight text-foreground">Activity Logs</h2>
        <p class="text-muted-foreground mt-1">Monitor all activities in your company account</p>
      </div>
      <Button variant="outline">
        <Filter class="w-4 h-4 mr-2" />
        Filter
      </Button>
    </div>

    <!-- Search -->
    <div class="relative max-w-md">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
      <Input 
        v-model="searchQuery"
        placeholder="Search activities..." 
        class="pl-9 bg-card border-border"
      />
    </div>

    <!-- Activity Timeline -->
    <div v-if="loading" class="text-center py-10">Loading activity logs...</div>
    
    <div v-else class="space-y-4">
      <div 
        v-for="log in filteredLogs" 
        :key="log.id"
        class="bg-card border border-border rounded-xl p-4 hover:shadow-md transition-all"
      >
        <div class="flex items-start gap-4">
          <!-- Icon -->
          <div :class="['w-10 h-10 rounded-full flex items-center justify-center', getActivityColor(log.type)]">
            <component :is="getActivityIcon(log.type)" class="w-5 h-5" />
          </div>
          
          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <Badge variant="outline" class="text-xs font-mono">{{ log.type }}</Badge>
                  <span class="text-sm text-muted-foreground">by {{ log.user }}</span>
                </div>
                <p class="text-sm text-foreground">{{ log.details }}</p>
              </div>
              <div class="flex items-center gap-1 text-xs text-muted-foreground whitespace-nowrap">
                <Calendar class="w-3 h-3" />
                {{ log.timestamp }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="filteredLogs.length === 0" class="text-center p-8 text-muted-foreground bg-card border border-border rounded-xl">
        <Activity class="w-12 h-12 mx-auto mb-3 opacity-50" />
        <p>No activity logs found.</p>
      </div>
    </div>
  </div>
</template>
