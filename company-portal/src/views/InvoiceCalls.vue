<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/lib/axios'
import { 
  ArrowLeft,
  FileText,
  List,
  Phone,
  Clock,
  Calendar,
  Play,
  Pause,
  Star,
  MessageSquare,
  Volume2
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

const route = useRoute()
const router = useRouter()
const invoiceId = route.params.id

const invoice = ref<any>(null)
const calls = ref<any[]>([])
const loading = ref(true)

// Audio Player State
const activeAudioId = ref<string | number | null>(null)
const isPlaying = ref(false)
const audioRef = ref<HTMLAudioElement | null>(null)

// Feedback State
const showFeedbackDialog = ref(false)
const selectedCall = ref<any>(null)
const feedbackRating = ref(10)
const feedbackReview = ref('')
const submittingFeedback = ref(false)

const fetchInvoiceDetails = async () => {
    loading.value = true
    try {
        const response = await api.get(`/company/invoices/${invoiceId}`)
        invoice.value = {
            id: response.data.id,
            displayId: `INV-${response.data.id}`,
            period: response.data.effective_from ? new Date(response.data.effective_from).toLocaleDateString('en-US', { month: 'short', year: 'numeric' }) : 'N/A',
            date: response.data.created_at ? new Date(response.data.created_at).toLocaleDateString() : 'N/A',
            minutes: response.data.total_minutes_consumption || 0,
            amount: response.data.billed_amount ? parseFloat(response.data.billed_amount) : 0,
            status: response.data.status || 'Unknown'
        }
        calls.value = response.data.calls || []
    } catch (e) {
        console.error("Failed to fetch invoice details", e)
    } finally {
        loading.value = false
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
        audioRef.value.src = call.call_audio_url || 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3'
        
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
        // Update local state
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

const getStatusColor = (status: string) => {
  switch (status?.toLowerCase()) {
    case 'paid': return 'bg-green-500/15 text-green-600 border-green-200'
    case 'pending': return 'bg-yellow-500/15 text-yellow-600 border-yellow-200'
    case 'overdue': return 'bg-destructive/15 text-destructive border-destructive/20'
    default: return 'bg-secondary text-secondary-foreground'
  }
}

const formatDuration = (seconds: number) => {
    const m = Math.floor(seconds / 60)
    const s = seconds % 60
    return `${m}:${s.toString().padStart(2, '0')}`
}

onMounted(() => {
    fetchInvoiceDetails()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header with Back Button -->
    <div class="flex items-center gap-4">
      <Button variant="ghost" size="icon" @click="router.back()">
        <ArrowLeft class="w-5 h-5" />
      </Button>
      <div>
        <h2 class="text-3xl font-bold tracking-tight text-foreground flex items-center gap-2">
            Invoice Details: {{ loading ? '...' : invoice?.displayId }}
        </h2>
        <p class="text-muted-foreground mt-1">Review call history for this billing period</p>
      </div>
    </div>

    <div v-if="loading" class="text-center py-20">
        <div class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
        <p class="mt-4 text-muted-foreground font-medium text-lg">Loading invoice logs...</p>
    </div>

    <div v-else-if="invoice" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
        <!-- Quick Info Summary -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-card p-6 rounded-2xl border border-border/50 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                    <Calendar class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground uppercase font-semibold">Billing Period</p>
                    <p class="text-lg font-bold">{{ invoice.period }}</p>
                </div>
            </div>

            <div class="bg-card p-6 rounded-2xl border border-border/50 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-green-500/10 flex items-center justify-center text-green-500">
                    <Clock class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground uppercase font-semibold">Total Minutes</p>
                    <p class="text-lg font-mono font-bold">{{ invoice.minutes }}m</p>
                </div>
            </div>

            <div class="bg-card p-6 rounded-2xl border border-border/50 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-orange-500/10 flex items-center justify-center text-orange-500">
                    <span class="text-xl font-bold font-mono">$</span>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground uppercase font-semibold">Total Amount</p>
                    <p class="text-lg font-bold">${{ invoice.amount.toFixed(2) }}</p>
                </div>
            </div>

            <div class="bg-card p-6 rounded-2xl border border-border/50 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500">
                    <FileText class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground uppercase font-semibold">Status</p>
                    <Badge :class="getStatusColor(invoice.status)">
                        {{ invoice.status }}
                    </Badge>
                </div>
            </div>
        </div>

        <!-- Calls Table -->
        <div class="bg-card border border-border rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b border-border flex items-center justify-between bg-muted/30">
                <h3 class="text-xl font-semibold flex items-center gap-2">
                    <List class="w-5 h-5 text-primary" />
                    Call History
                </h3>
            </div>
            
            <div v-if="calls.length === 0" class="text-center py-20 text-muted-foreground bg-muted/10">
                <Phone class="w-12 h-12 mx-auto mb-4 opacity-20" />
                <p class="text-lg">No calls recorded for this invoice period.</p>
            </div>
            
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="px-6 py-4 text-left font-semibold text-muted-foreground uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-4 text-left font-semibold text-muted-foreground uppercase tracking-wider">User Phone</th>
                            <th class="px-6 py-4 text-center font-semibold text-muted-foreground uppercase tracking-wider">Duration</th>
                            <th class="px-6 py-4 text-left font-semibold text-muted-foreground uppercase tracking-wider">Disposition</th>
                            <th class="px-6 py-4 text-right font-semibold text-muted-foreground uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="call in calls" :key="call.id" class="hover:bg-muted/30 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap text-foreground font-medium">
                                {{ new Date(call.created_at).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-primary/5 flex items-center justify-center text-primary">
                                    <Phone class="w-3 h-3" />
                                </div>
                                {{ call.user_phone }}
                                <Badge v-if="call.company_rating" variant="outline" class="ml-2 gap-1 bg-yellow-500/5 text-yellow-600 border-yellow-200">
                                    <Star class="w-2.5 h-2.5 fill-current" />
                                    {{ call.company_rating }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <Badge variant="secondary" class="font-mono">
                                    {{ formatDuration(call.duration) }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Badge variant="outline" class="text-xs">{{ call.disposition }}</Badge>
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
            <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Rating: {{ feedbackRating }} / 10</p>
            <div class="flex justify-center gap-1">
              <button 
                v-for="i in 10" 
                :key="i"
                @click="!selectedCall?.company_rating && (feedbackRating = i)"
                class="transition-all duration-200"
                :class="[
                    i <= feedbackRating ? 'scale-110 mb-1' : 'opacity-40 grayscale',
                    selectedCall?.company_rating ? 'cursor-default' : 'cursor-pointer'
                ]"
              >
                <div 
                  class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs"
                  :class="i <= feedbackRating ? 'bg-yellow-500 text-white shadow-lg shadow-yellow-500/20' : 'bg-muted text-muted-foreground'"
                >
                  {{ i }}
                </div>
              </button>
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
  </div>
</template>
