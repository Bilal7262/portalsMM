<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Play, Pause } from 'lucide-vue-next'
import { invoiceService } from '@/services/invoice'

const route = useRoute()
const router = useRouter()

const callId = ref(Number(route.params.id))
const call = ref<any>(null)
const messages = ref<any[]>([])
const loading = ref(false)

const fetchCallMessages = async () => {
  loading.value = true
  try {
    const messagesData = await invoiceService.getCallMessages(callId.value)
    messages.value = messagesData
    
    // Fetch call details
    const callData = await invoiceService.getCallDetails(callId.value)
    call.value = callData
  } catch (error) {
    console.error('Failed to fetch call messages:', error)
  } finally {
    loading.value = false
  }
}

// Audio Player Logic
const currentAudioId = ref<number | null>(null)
const isPlaying = ref(false)
const audioPlayer = new Audio()

const playAudio = (id: number, url: string) => {
  if (currentAudioId.value === id && isPlaying.value) {
    audioPlayer.pause()
    isPlaying.value = false
    return
  }

  if (currentAudioId.value !== id) {
    audioPlayer.src = url
    currentAudioId.value = id
  }

  audioPlayer.play()
  isPlaying.value = true

  audioPlayer.onended = () => {
    isPlaying.value = false
    currentAudioId.value = null
  }
}


const goBack = () => {
  router.back()
}

onMounted(() => {
  fetchCallMessages()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button @click="goBack" class="text-gray-600 hover:text-gray-900">
          <ArrowLeft class="w-6 h-6" />
        </button>
        <div>
          <h2 class="text-3xl font-bold tracking-tight text-foreground">Call Conversation</h2>
          <p class="text-muted-foreground mt-1" v-if="call">
            Session: {{ call.session_id }} | Duration: {{ Math.floor(call.duration / 60) }}:{{ (call.duration % 60).toString().padStart(2, '0') }}
          </p>
        </div>
      </div>
    </div>

    <div class="bg-white shadow sm:rounded-lg overflow-hidden relative">
      <div v-if="loading" class="absolute inset-0 bg-white/50 flex items-center justify-center z-10">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>

      <div class="p-6">
        <div v-if="messages.length === 0 && !loading" class="text-center py-12 text-gray-500">
          No messages found for this call.
        </div>
        
        <div v-else class="space-y-4 max-w-4xl mx-auto">
          <div 
            v-for="(message, index) in messages" 
            :key="index"
            :class="[
              message.type === 'bot' ? 'justify-start' : 'justify-end',
              'flex'
            ]"
          >
            <div 
              :class="[
                message.type === 'bot' 
                  ? 'bg-blue-50 border-blue-200 text-blue-900' 
                  : 'bg-green-50 border-green-200 text-green-900',
                'max-w-xl p-4 rounded-lg border shadow-sm'
              ]"
            >
              <div class="flex items-center gap-2 mb-2">
                <span 
                  :class="[
                    message.type === 'bot' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700',
                    'px-2 py-1 rounded text-xs font-semibold uppercase'
                  ]"
                >
                  {{ message.type }}
                </span>
                <span class="text-xs text-gray-500">
                  {{ new Date(message.created_at).toLocaleTimeString() }}
                </span>
              </div>
              <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ message.text }}</p>
              <div v-if="message.audio" class="mt-2 flex items-center gap-2">
                <button 
                  @click="playAudio(message.id, message.audio)"
                  class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition-colors text-xs font-medium"
                >
                  <Play v-if="currentAudioId !== message.id || !isPlaying" class="w-3 h-3 fill-current" />
                  <Pause v-else class="w-3 h-3 fill-current" />
                  {{ currentAudioId === message.id && isPlaying ? 'Playing...' : 'Play Audio' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Call Details Card -->
    <div v-if="call" class="bg-white shadow sm:rounded-lg p-6">
      <h3 class="text-lg font-semibold mb-4">Call Details</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <p class="text-sm text-gray-500">Customer Phone</p>
          <p class="font-medium">{{ call.user_phone || 'N/A' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500">Disposition</p>
          <span :class="[
            call.disposition === 'SALE' ? 'bg-green-100 text-green-800' :
            call.disposition === 'DNC' ? 'bg-red-100 text-red-800' :
            'bg-gray-100 text-gray-800',
            'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium'
          ]">
            {{ call.disposition || 'N/A' }}
          </span>
        </div>
        <div v-if="call.ai_rating">
          <p class="text-sm text-gray-500">AI Rating</p>
          <p class="font-medium">{{ call.ai_rating }}/10</p>
          <p class="text-xs text-gray-500 mt-1">{{ call.ai_feedback }}</p>
        </div>
        <div v-if="call.company_rating">
          <p class="text-sm text-gray-500">Company Rating</p>
          <p class="font-medium">{{ call.company_rating }}/10</p>
          <p class="text-xs text-gray-500 mt-1">{{ call.company_feedback }}</p>
        </div>

        <div v-if="call.call_audio_url" class="md:col-span-2">
          <p class="text-sm text-gray-500 mb-2">Full Call Recording</p>
          <audio controls class="w-full">
            <source :src="call.call_audio_url" type="audio/mpeg">
            Your browser does not support the audio element.
          </audio>
        </div>
      </div>
    </div>
  </div>
</template>
