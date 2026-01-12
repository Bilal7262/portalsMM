<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/lib/axios'
import CustomPagination from '@/components/CustomPagination.vue'
import { 
  ArrowLeft,
  List,
  Phone,
  Clock,
  Calendar,
  Play,
  Pause,
  Star,
  MessageSquare,
  Volume2,
  Search,
  ArrowUpDown,
  ArrowUp,
  ArrowDown,
  ChevronLeft,
  ChevronRight,
  Info
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const router = useRouter()

const stats = ref<any>({
    period: 'Current Month',
    minutes: 0,
    amount: 0
})
const calls = ref<any[]>([])
const loading = ref(true)

// Filters State
const searchQuery = ref('')
const disposition = ref('ALL')
const sortBy = ref('created_at')
const sortOrder = ref('desc')

// Pagination State
const currentPage = ref(1)
const totalPages = ref(1)
const totalCalls = ref(0)
const perPage = ref(10)

// Audio Player State
const activeAudioId = ref<string | number | null>(null)
const isPlaying = ref(false)
const audioRef = ref<HTMLAudioElement | null>(null)

// Feedback State
const showFeedbackDialog = ref(false)
const selectedCall = ref<any>(null)
const feedbackRating = ref(5)
const feedbackReview = ref('')
const submittingFeedback = ref(false)

// AI Feedback State
const showAiFeedbackDialog = ref(false)
const aiFeedbackContent = ref('')
const aiFeedbackRating = ref(0)

const fetchCurrentMonthCalls = async () => {
    loading.value = true
    try {
        const params: any = {
            page: currentPage.value,
            per_page: perPage.value,
            sort_by: sortBy.value,
            sort_order: sortOrder.value
        }
        
        if (searchQuery.value) params.search_query = searchQuery.value
        if (disposition.value && disposition.value !== 'ALL') params.disposition = disposition.value

        const response = await api.get('/company/calls/current-month', { params })
        
        // Response stats
        stats.value = {
            period: response.data.period,
            minutes: response.data.minutes || 0,
            amount: response.data.amount || 0
        }
        
        // Response pagination data
        if (response.data.calls) {
            calls.value = response.data.calls.data
            currentPage.value = response.data.calls.current_page
            totalPages.value = response.data.calls.last_page
            totalCalls.value = response.data.calls.total
        } else {
            calls.value = []
        }
        
    } catch (e) {
        console.error("Failed to fetch current month calls", e)
    } finally {
        loading.value = false
    }
}

// Watch filters to trigger refetch
watch([searchQuery, disposition, sortBy, sortOrder, perPage], () => {
    currentPage.value = 1 // Reset to page 1 on filter change
    fetchCurrentMonthCalls()
})

// Watch pagination uses custom component v-model, so we just watch currentPage
watch(currentPage, () => {
    fetchCurrentMonthCalls()
})

const handleSort = (field: string) => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortBy.value = field
        sortOrder.value = 'desc'
    }
}

const togglePlay = async (call: any) => {
    if (!audioRef.value) return

    if (activeAudioId.value === call.id) {
        if (isPlaying.value) {
            audioRef.value.pause()
        } else {
            try {
                await audioRef.value.play()
            } catch (err) {
                console.error("Playback failed", err)
                isPlaying.value = false
            }
        }
    } else {
        // Switch to new audio
        isPlaying.value = false
        activeAudioId.value = call.id
        audioRef.value.src = call.call_audio_url || 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3' // Fallback for demo
        
        try {
            await audioRef.value.play()
        } catch (err) {
            console.error("New playback failed", err)
            isPlaying.value = false
            activeAudioId.value = null
        }
    }
}

const openFeedbackModal = (call: any) => {
    selectedCall.value = call
    feedbackRating.value = call.company_rating || 10
    feedbackReview.value = call.company_feedback || ''
    showFeedbackDialog.value = true
}

const submitFeedback = async () => {
    if (!selectedCall.value) return
    submittingFeedback.value = true
    try {
        await api.post(`/company/calls/${selectedCall.value.id}/feedback`, {
            company_rating: feedbackRating.value,
            company_feedback: feedbackReview.value
        })
        // Update local state is not enough due to pagination, but fine for display update
        const callIndex = calls.value.findIndex(c => c.id === selectedCall.value.id)
        if (callIndex !== -1) {
            calls.value[callIndex].company_rating = feedbackRating.value
            calls.value[callIndex].company_feedback = feedbackReview.value
        }
        showFeedbackDialog.value = false
    } catch (e) {
        console.error("Failed to submit feedback", e)
        alert("Failed to submit feedback. Please try again.")
    } finally {
        submittingFeedback.value = false
    }
}

const openAiFeedback = (call: any) => {
    aiFeedbackContent.value = call.ai_feedback || 'No detailed analysis available for this call.'
    aiFeedbackRating.value = call.ai_rating || 0
    showAiFeedbackDialog.value = true
}

const formatDuration = (seconds: number) => {
    const m = Math.floor(seconds / 60)
    const s = seconds % 60
    return `${m}:${s.toString().padStart(2, '0')}`
}

onMounted(() => {
    fetchCurrentMonthCalls()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-3xl font-bold tracking-tight text-foreground flex items-center gap-2">
            Call History
        </h2>
        <p class="text-muted-foreground mt-1">Live overview of your calls for the current billing period.</p>
      </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-card p-6 rounded-2xl border border-border/50 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                <Calendar class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs text-muted-foreground uppercase font-semibold">Current Period</p>
                <p class="text-lg font-bold">{{ stats.period }}</p>
            </div>
        </div>

        <div class="bg-card p-6 rounded-2xl border border-border/50 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-green-500/10 flex items-center justify-center text-green-500">
                <Clock class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs text-muted-foreground uppercase font-semibold">Total Minutes (Est.)</p>
                <p class="text-lg font-mono font-bold">{{ stats.minutes }}m</p>
            </div>
        </div>

        <div class="bg-card p-6 rounded-2xl border border-border/50 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-orange-500/10 flex items-center justify-center text-orange-500">
                <span class="text-xl font-bold font-mono">$</span>
            </div>
            <div>
                <p class="text-xs text-muted-foreground uppercase font-semibold">Estimated Cost</p>
                <p class="text-lg font-bold">${{ stats.amount.toFixed(2) }}</p>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-card border border-border rounded-xl p-4 flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto text-sm">
             <div class="relative w-full sm:w-64">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="searchQuery" 
                    placeholder="Search DID or User Phone..." 
                    class="pl-9 h-9"
                />
            </div>
             <Select v-model="disposition">
                <SelectTrigger class="w-full sm:w-56 h-9">
                    <SelectValue placeholder="Disposition" />
                </SelectTrigger>
                <SelectContent class="max-h-[300px]">
                    <SelectItem value="ALL">All Dispositions</SelectItem>
                    <SelectItem value="SALE">SALE - Sale / Transfer</SelectItem>
                    <SelectItem value="NI">NI - Not Interested</SelectItem>
                    <SelectItem value="DNC">DNC - Do Not Call</SelectItem>
                    <SelectItem value="CALLBK">CALLBK - Call Back</SelectItem>
                    <SelectItem value="CBHOLD">CBHOLD - Call Back Hold</SelectItem>
                    <SelectItem value="DEC">DEC - Declined Sale</SelectItem>
                    <SelectItem value="NP">NP - No Pitch</SelectItem>
                    <SelectItem value="WRONG">WRONG - Wrong Number</SelectItem>
                    <SelectItem value="NC">NC - Not Company</SelectItem>
                    <SelectItem value="SVYEXT">SVYEXT - Survey Extension</SelectItem>
                    <SelectItem value="TIME">TIME - Time Zone</SelectItem>
                    <SelectItem value="FAX">FAX - Fax Machine</SelectItem>
                </SelectContent>
            </Select>
        </div>
        
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
             <span v-if="loading" class="animate-pulse">Updating...</span>
             <span v-else>{{ totalCalls }} Calls found</span>
             
             <Button 
                v-if="searchQuery || disposition !== 'ALL'"
                variant="link" 
                @click="searchQuery = ''; disposition = 'ALL'"
                class="ml-2 h-auto p-0"
            >
                Clear Filters
            </Button>
        </div>
    </div>

    <!-- Calls Table -->
    <div class="bg-card border border-border rounded-xl shadow-md overflow-hidden">
        <div v-if="loading && calls.length === 0" class="flex flex-col items-center justify-center py-20">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
            <p class="mt-4 text-muted-foreground">Loading calls...</p>
        </div>
        
        <div v-else-if="calls.length === 0" class="flex flex-col items-center justify-center py-20 text-muted-foreground">
            <Phone class="w-12 h-12 opacity-20 mb-4" />
            <p class="text-lg">No calls found matching your criteria.</p>
        </div>
        
        <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-foreground group" @click="handleSort('created_at')">
                            <div class="flex items-center gap-1">
                                Date & Time
                                <component 
                                    :is="sortBy === 'created_at' ? (sortOrder === 'asc' ? ArrowUp : ArrowDown) : ArrowUpDown" 
                                    class="w-3 h-3" 
                                    :class="sortBy === 'created_at' ? 'text-primary' : 'opacity-50 group-hover:opacity-100'"
                                />
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left font-semibold text-muted-foreground uppercase tracking-wider">
                            From DID
                        </th>
                        <th class="px-6 py-4 text-left font-semibold text-muted-foreground uppercase tracking-wider">
                            User Phone
                        </th>
                        <th class="px-6 py-4 text-center font-semibold text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-foreground group" @click="handleSort('duration')">
                             <div class="flex items-center justify-center gap-1">
                                Duration
                                <component 
                                    :is="sortBy === 'duration' ? (sortOrder === 'asc' ? ArrowUp : ArrowDown) : ArrowUpDown" 
                                    class="w-3 h-3" 
                                    :class="sortBy === 'duration' ? 'text-primary' : 'opacity-50 group-hover:opacity-100'"
                                />
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left font-semibold text-muted-foreground uppercase tracking-wider">Disposition</th>
                        <th class="px-6 py-4 text-center font-semibold text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-foreground group" @click="handleSort('ai_rating')">
                            <div class="flex items-center justify-center gap-1">
                                AI <Star class="w-3.5 h-3.5 fill-current text-yellow-500" />
                                <component 
                                    :is="sortBy === 'ai_rating' ? (sortOrder === 'asc' ? ArrowUp : ArrowDown) : ArrowUpDown" 
                                    class="w-3 h-3" 
                                    :class="sortBy === 'ai_rating' ? 'text-primary' : 'opacity-50 group-hover:opacity-100'"
                                />
                            </div>
                        </th>
                        <th class="px-6 py-4 text-center font-semibold text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-foreground group" @click="handleSort('company_rating')">
                            <div class="flex items-center justify-center gap-1">
                                User <Star class="w-3.5 h-3.5 fill-current text-yellow-500" />
                                <component 
                                    :is="sortBy === 'company_rating' ? (sortOrder === 'asc' ? ArrowUp : ArrowDown) : ArrowUpDown" 
                                    class="w-3 h-3" 
                                    :class="sortBy === 'company_rating' ? 'text-primary' : 'opacity-50 group-hover:opacity-100'"
                                />
                            </div>
                        </th>
                        <th class="px-6 py-4 text-right font-semibold text-muted-foreground uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="call in calls" :key="call.id" class="hover:bg-muted/30 transition-colors group">
                        <td class="px-6 py-4 whitespace-nowrap text-foreground font-medium">
                            {{ new Date(call.created_at).toLocaleString() }}
                        </td>
                         <td class="px-6 py-4 whitespace-nowrap text-muted-foreground">
                            {{ call.invoice?.company_did?.did?.did_number || 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-primary/5 flex items-center justify-center text-primary">
                                <Phone class="w-3 h-3" />
                            </div>
                            {{ call.user_phone }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <Badge variant="secondary" class="font-mono">
                                {{ formatDuration(call.duration) }}
                            </Badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <Badge variant="outline" class="text-xs">{{ call.disposition }}</Badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                             <div v-if="call.ai_rating" class="flex justify-center items-center gap-2">
                                <span class="font-bold text-sm" :class="call.ai_rating >= 4 ? 'text-green-600' : (call.ai_rating <= 2 ? 'text-red-500' : 'text-yellow-600')">
                                    {{ call.ai_rating }}/5
                                </span>
                                <Button 
                                    variant="ghost" 
                                    size="icon" 
                                    class="h-6 w-6 text-muted-foreground hover:text-foreground"
                                    @click="openAiFeedback(call)"
                                    title="View AI Analysis"
                                >
                                    <Info class="w-4 h-4" />
                                </Button>
                             </div>
                             <span v-else class="text-muted-foreground">-</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <Badge v-if="call.company_rating" variant="outline" class="gap-1 bg-yellow-500/5 text-yellow-600 border-yellow-200">
                                <Star class="w-2.5 h-2.5 fill-current" />
                                {{ call.company_rating }}
                            </Badge>
                            <span v-else class="text-muted-foreground">-</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Button 
                                    variant="outline" 
                                    size="sm" 
                                    @click="togglePlay(call)"
                                    :class="activeAudioId === call.id ? 'border-primary text-primary bg-primary/5 animate-pulse' : ''"
                                >
                                    <component :is="activeAudioId === call.id && isPlaying ? Pause : Play" class="w-4 h-4 mr-2" />
                                    {{ activeAudioId === call.id && isPlaying ? 'Pause' : 'Play' }}
                                </Button>
                                <Button v-if="!call.company_rating" variant="ghost" size="sm" @click="openFeedbackModal(call)" title="Rate Call">
                                    <Star class="w-4 h-4" />
                                </Button>
                                <Button v-else variant="ghost" size="sm" @click="openFeedbackModal(call)" title="View Feedback" class="text-yellow-500 hover:text-yellow-600">
                                    <MessageSquare class="w-4 h-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div v-if="totalPages >= 1" class="border-t border-border bg-muted/20">
             <CustomPagination 
                v-model="currentPage" 
                v-model:pageSize="perPage"
                :totalPages="totalPages" 
                :loading="loading" 
            />
        </div>
    </div>

    <!-- Hidden audio element -->
    <audio 
        ref="audioRef" 
        class="hidden" 
        @play="isPlaying = true" 
        @pause="isPlaying = false" 
        @ended="activeAudioId = null; isPlaying = false"
        @error="activeAudioId = null; isPlaying = false"
    ></audio>

    <!-- Feedback Dialog -->
    <Dialog :open="showFeedbackDialog" @update:open="showFeedbackDialog = $event">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Star class="w-5 h-5 text-yellow-500 fill-yellow-500" />
            {{ selectedCall?.company_rating ? 'Feedback Details' : 'Rate This Call' }}
          </DialogTitle>
          <DialogDescription>
            {{ selectedCall?.company_rating ? 'Review your previous feedback or update it.' : `Provide a rating and brief review for call with ${selectedCall?.user_phone}` }}
          </DialogDescription>
        </DialogHeader>

        <div class="grid gap-6 py-4">
          <div class="space-y-3 text-center">
            <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Rating: {{ feedbackRating }} / 5</p>
            <div class="px-4">
                <input 
                    type="range" 
                    min="0.5" 
                    max="5.0"
                    step="0.1"
                    v-model.number="feedbackRating"
                    class="w-full h-2 bg-secondary rounded-lg appearance-none cursor-pointer accent-primary disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!!selectedCall?.company_rating"
                />
                <div class="flex justify-between text-xs text-muted-foreground mt-2 font-medium">
                    <span>0.5</span>
                    <span>5.0</span>
                </div>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium flex items-center gap-2">
                <MessageSquare class="w-4 h-4 text-primary" />
                {{ selectedCall?.company_rating ? 'Your Review' : 'Add Review' }}
            </label>
            <Input 
                v-model="feedbackReview"
                :readonly="!!selectedCall?.company_rating"
                placeholder="How was the AI performance? Any specific feedback..."
                class="h-24 py-2 align-top text-start"
            />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showFeedbackDialog = false" :disabled="submittingFeedback">
            {{ selectedCall?.company_rating ? 'Close' : 'Cancel' }}
          </Button>
          <Button v-if="!selectedCall?.company_rating" @click="submitFeedback" :disabled="submittingFeedback" class="min-w-[100px]">
            <span v-if="submittingFeedback" class="animate-spin mr-2">
                <Volume2 class="w-4 h-4" />
            </span>
            {{ submittingFeedback ? 'Saving...' : 'Submit Rating' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- AI Feedback Dialog -->
    <Dialog :open="showAiFeedbackDialog" @update:open="showAiFeedbackDialog = $event">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <div class="p-2 rounded-lg bg-primary/10 text-primary">
                <Volume2 class="w-5 h-5" />
            </div>
            AI Call Analysis
          </DialogTitle>
          <DialogDescription>
            Detailed analysis and reasoning behind the AI rating.
          </DialogDescription>
        </DialogHeader>

        <div class="py-4 space-y-4">
             <div class="flex items-center justify-between p-4 bg-muted/50 rounded-lg">
                <span class="text-sm font-medium text-muted-foreground">Assigned Rating</span>
                <span class="font-bold text-lg" :class="aiFeedbackRating >= 4 ? 'text-green-600' : (aiFeedbackRating <= 2 ? 'text-red-500' : 'text-yellow-600')">
                    {{ aiFeedbackRating }}/5
                </span>
            </div>
            
            <div class="space-y-2">
                <h4 class="text-sm font-medium text-foreground">Analysis</h4>
                <div class="text-sm text-muted-foreground leading-relaxed p-4 border border-border rounded-lg bg-card max-h-[300px] overflow-y-auto whitespace-pre-wrap">
                    {{ aiFeedbackContent }}
                </div>
            </div>
        </div>

        <DialogFooter>
          <Button @click="showAiFeedbackDialog = false">
            Close Analysis
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
