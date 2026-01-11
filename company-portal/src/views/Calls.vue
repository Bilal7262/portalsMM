<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/lib/axios'
import { 
  Search, 
  Filter, 
  Download, 
  Phone, 
  Clock,
  MoreVertical,
  Play
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'

const calls = ref<any[]>([])
const loading = ref(false)

const fetchCalls = async () => {
    loading.value = true
    try {
        const response = await api.get('/company/calls')
        calls.value = response.data.map((c: any) => ({
             id: c.id,
             customer: c.user_phone,
             agent: "AI Agent", // Placeholder
             type: 'outbound', // Placeholder
             duration: formatDuration(c.duration),
             status: 'completed', // Placeholder
             date: new Date(c.created_at).toLocaleString(),
             rating: c.ai_rating, // or rating
             sentiment: c.disposition === 'SALE' ? 'positive' : 'neutral'
        }))
    } catch (e) {
        console.error("Failed to fetch calls", e)
    } finally {
        loading.value = false
    }
}

function formatDuration(seconds: number) {
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m}:${s.toString().padStart(2, '0')}`
}

onMounted(() => {
    fetchCalls()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-3xl font-bold tracking-tight text-foreground">Call History</h2>
        <p class="text-muted-foreground mt-1">Monitor and analyze communication logs</p>
      </div>
     <div class="flex gap-2">
        <Button variant="outline">
          <Download class="w-4 h-4 mr-2" />
          Export
        </Button>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 items-center bg-card p-4 rounded-xl border border-border shadow-sm">
        <div class="relative w-full sm:w-96">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <Input placeholder="Search calls..." class="pl-9 bg-background/50" />
        </div>
    </div>

    <!-- Calls List -->
    <div v-if="loading" class="text-center py-10">Loading calls...</div>
    <div v-else class="space-y-4">
        <div v-for="call in calls" :key="call.id" class="group bg-card border border-border rounded-xl p-4 hover:shadow-md transition-all">
             <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                        <Phone class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                             <h3 class="font-semibold text-foreground">{{ call.customer }}</h3>
                             <Badge variant="outline" class="text-xs">{{ call.type }}</Badge>
                        </div>
                        <div class="flex items-center gap-4 mt-1 text-sm text-muted-foreground">
                             <div class="flex items-center gap-1">
                                <Clock class="w-3 h-3" />
                                {{ call.duration }}
                             </div>
                             <span>{{ call.date }}</span>
                        </div>
                    </div>
                </div>
                 
                 <div class="flex items-center gap-4 w-full sm:w-auto mt-2 sm:mt-0">
                    <div class="bg-muted px-3 py-1.5 rounded-md flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full" :class="call.sentiment === 'positive' ? 'bg-green-500' : 'bg-gray-400'"></div>
                        <span class="text-sm font-medium">{{ call.sentiment }}</span>
                    </div>
                 </div>
             </div>
        </div>
        <div v-if="calls.length === 0" class="text-center p-8 text-muted-foreground">
            No calls found.
        </div>
    </div>
  </div>
</template>
