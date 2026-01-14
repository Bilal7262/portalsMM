<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/lib/axios'
import { 
  Bot, 
  Play,
  Pause,
  ChevronRight,
  ChevronLeft,
  Check,
  User,
  FileText,
  Hash,
  Volume2
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Label } from '@/components/ui/label'

const router = useRouter()
const submitting = ref(false)
const voices = ref<any[]>([])

// Stepper State
const currentStep = ref(1)

// Audio Preview State
const isPlaying = ref(false)
const activeVoiceId = ref<string | null>(null)
const audioRef = ref<HTMLAudioElement | null>(null)

const form = reactive({
    name: '',
    admin_voice_id: '', 
    script: '',
    quantity: 1
})

const toggleVoicePreview = (voiceUrl: string, voiceId: string) => {
    if (!audioRef.value) return

    if (activeVoiceId.value === voiceId && isPlaying.value) {
        audioRef.value.pause()
        isPlaying.value = false
        activeVoiceId.value = null
    } else {
        audioRef.value.src = voiceUrl
        audioRef.value.play()
        isPlaying.value = true
        activeVoiceId.value = voiceId
    }
}

const resetAudio = () => {
    if (audioRef.value) {
        audioRef.value.pause()
        audioRef.value.currentTime = 0
    }
    isPlaying.value = false
    activeVoiceId.value = null
}

const nextStep = () => {
    if (currentStep.value < 3) {
        currentStep.value++
        resetAudio()
    }
}

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--
        resetAudio()
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

const submitForm = async () => {
    submitting.value = true
    try {
        await api.post('/company/agents', {
            name: form.name,
            admin_voice_id: form.admin_voice_id,
            script: form.script,
            quantity: form.quantity
        })
        router.push('/agents')
    } catch (e) {
        console.error("Failed to save agent", e)
        alert("Failed to save agent")
    } finally {
        submitting.value = false
    }
}

onMounted(() => {
    fetchVoices()
})
</script>

<template>
  <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <div class="mb-8">
          <h1 class="text-3xl font-bold tracking-tight text-foreground">Request New Agent</h1>
          <p class="text-muted-foreground mt-2">Configure and launch your new AI agents in a few simple steps.</p>
      </div>

      <div class="bg-card border border-border rounded-xl overflow-hidden shadow-sm">
          <!-- Stepper Indicator -->
          <div class="px-6 py-6 bg-muted/20 border-b border-border flex items-center justify-between lg:px-24">
              <div class="flex flex-col items-center gap-2 z-10 transition-all duration-300" :class="{'opacity-50': currentStep < 1}">
                  <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-colors duration-300', currentStep >= 1 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground']">1</div>
                  <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Voice</span>
              </div>
              <div :class="['flex-1 h-[2px] mx-4 transition-colors duration-300', currentStep >= 2 ? 'bg-primary' : 'bg-muted']"></div>
              <div class="flex flex-col items-center gap-2 z-10 transition-all duration-300" :class="{'opacity-50': currentStep < 2}">
                  <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-colors duration-300', currentStep >= 2 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground']">2</div>
                  <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Details</span>
              </div>
              <div :class="['flex-1 h-[2px] mx-4 transition-colors duration-300', currentStep >= 3 ? 'bg-primary' : 'bg-muted']"></div>
               <div class="flex flex-col items-center gap-2 z-10 transition-all duration-300" :class="{'opacity-50': currentStep < 3}">
                  <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-colors duration-300', currentStep >= 3 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground']">3</div>
                  <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Preview</span>
              </div>
          </div>

          <div class="p-8 min-h-[400px]">
              <!-- Step 1: Voice Selection -->
              <div v-if="currentStep === 1" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300 max-w-2xl mx-auto">
                  <div class="space-y-2 text-center mb-8">
                       <h3 class="text-xl font-semibold">Choose a Voice</h3>
                       <p class="text-sm text-muted-foreground">Select the perfect voice persona for your agent.</p>
                  </div>
                  
                  <div class="grid gap-4">
                       <Label class="text-base">Select Voice</Label>
                       <Select v-model="form.admin_voice_id">
                            <SelectTrigger class="h-12 text-base">
                                <SelectValue placeholder="Select a voice for your agent" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="v in voices" :key="v.id" :value="v.id.toString()">
                                    {{ v.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                  </div>

                  <!-- Voice Preview Card -->
                  <div v-if="form.admin_voice_id" class="mt-6 p-6 rounded-xl border border-border bg-muted/30 flex items-center justify-between transition-all duration-300 hover:shadow-md">
                      <div class="flex items-center gap-4">
                          <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                              <Volume2 v-if="!isPlaying" class="w-6 h-6" />
                              <span v-else class="relative flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-primary"></span>
                              </span>
                          </div>
                          <div>
                              <p class="font-medium text-lg">{{ voices.find(v => v.id.toString() === form.admin_voice_id)?.name }}</p>
                              <p class="text-sm text-muted-foreground">Click play to preview voice sample</p>
                          </div>
                      </div>
                      <Button 
                        size="icon" 
                        variant="outline" 
                        class="rounded-full h-12 w-12 border-2 hover:bg-primary/10 hover:text-primary hover:border-primary/50 transition-colors"
                        @click="toggleVoicePreview(voices.find(v => v.id.toString() === form.admin_voice_id)?.audio || '', form.admin_voice_id)"
                      >
                          <Pause v-if="isPlaying && activeVoiceId === form.admin_voice_id" class="w-5 h-5 fill-current" />
                          <Play v-else class="w-5 h-5 fill-current ml-0.5" />
                      </Button>
                  </div>
                   <div v-else class="h-32 border-2 border-dashed border-muted-foreground/25 rounded-xl flex items-center justify-center text-muted-foreground bg-muted/5 mt-6">
                      <div class="text-center">
                          <Bot class="w-8 h-8 mx-auto mb-2 opacity-50" />
                          <p>Please select a voice to continue</p>
                      </div>
                  </div>
              </div>

              <!-- Step 2: Agent Details -->
              <div v-else-if="currentStep === 2" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300 max-w-2xl mx-auto">
                   <div class="space-y-2 text-center mb-8">
                       <h3 class="text-xl font-semibold">Agent Details</h3>
                       <p class="text-sm text-muted-foreground">Configure the basic information for your agent.</p>
                  </div>

                   <div class="grid gap-2">
                      <Label class="text-base">Agent Name</Label>
                      <Input v-model="form.name" placeholder="e.g. Sales Bot 1" class="h-12" />
                  </div>
                   <div class="grid gap-2">
                      <Label class="text-base">Quantity</Label>
                      <Input v-model="form.quantity" type="number" min="1" max="10" class="h-12" />
                      <p class="text-xs text-muted-foreground">Create multiple agents with this configuration.</p>
                  </div>
                  <div class="grid gap-2">
                      <Label class="text-base">Training Script</Label>
                      <textarea 
                        v-model="form.script" 
                        class="flex min-h-[300px] max-h-[600px] w-full rounded-md border border-input bg-background px-4 py-3 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 resize-y font-mono"
                        placeholder="Enter the script you want your agent to follow..."
                      ></textarea>
                  </div>
              </div>

              <!-- Step 3: Preview -->
              <div v-else class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300 max-w-2xl mx-auto">
                   <div class="space-y-2 text-center mb-8">
                       <h3 class="text-xl font-semibold">Review & Confirm</h3>
                       <p class="text-sm text-muted-foreground">Please review your agent configuration before submitting.</p>
                  </div>

                  <div class="bg-card rounded-xl border border-border overflow-hidden shadow-sm">
                      <div class="p-4 grid grid-cols-[24px_1fr] gap-4 items-start border-b border-border last:border-0 hover:bg-muted/30 transition-colors">
                          <User class="w-5 h-5 text-primary mt-0.5" />
                          <div>
                              <p class="text-xs font-bold text-muted-foreground uppercase tracking-wide">Agent Name</p>
                              <p class="font-medium text-lg">{{ form.name }}</p>
                          </div>
                      </div>
                       <div class="p-4 grid grid-cols-[24px_1fr] gap-4 items-start border-b border-border last:border-0 hover:bg-muted/30 transition-colors">
                          <Bot class="w-5 h-5 text-primary mt-0.5" />
                          <div>
                              <p class="text-xs font-bold text-muted-foreground uppercase tracking-wide">Voice</p>
                              <p class="font-medium text-lg">{{ voices.find(v => v.id.toString() === form.admin_voice_id)?.name }}</p>
                          </div>
                      </div>
                       <div class="p-4 grid grid-cols-[24px_1fr] gap-4 items-start border-b border-border last:border-0 hover:bg-muted/30 transition-colors">
                          <Hash class="w-5 h-5 text-primary mt-0.5" />
                          <div>
                              <p class="text-xs font-bold text-muted-foreground uppercase tracking-wide">Quantity</p>
                              <p class="font-medium text-lg">{{ form.quantity }} Agent(s)</p>
                          </div>
                      </div>
                       <div class="p-4 grid grid-cols-[24px_1fr] gap-4 items-start hover:bg-muted/30 transition-colors">
                          <FileText class="w-5 h-5 text-primary mt-0.5" />
                          <div class="w-full">
                              <p class="text-xs font-bold text-muted-foreground uppercase tracking-wide mb-2">Script</p>
                              <div class="text-sm text-muted-foreground bg-muted/30 p-4 rounded-lg border border-border/50 max-h-[200px] overflow-y-auto whitespace-pre-wrap w-full font-mono">{{ form.script }}</div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>

          <div class="px-8 py-6 bg-muted/20 border-t border-border flex items-center justify-between">
               <div class="flex gap-3">
                   <Button v-if="currentStep > 1" variant="outline" size="lg" @click="prevStep">
                       <ChevronLeft class="w-4 h-4 mr-2" /> Back
                   </Button>
                   <Button v-else variant="ghost" size="lg" @click="router.push('/agents')">Cancel</Button>
               </div>

               <div class="flex gap-3">
                   <Button v-if="currentStep < 3" size="lg" @click="nextStep" :disabled="currentStep === 1 && !form.admin_voice_id">
                       Next <ChevronRight class="w-4 h-4 ml-2" />
                   </Button>
                   <Button v-else size="lg" @click="submitForm" :disabled="submitting">
                       <span v-if="submitting" class="animate-spin mr-2">
                           <Bot class="w-4 h-4" />
                       </span>
                       <span v-else class="flex items-center">
                           Submit Order <Check class="w-4 h-4 ml-2" />
                       </span>
                   </Button>
               </div>
          </div>
      </div>
  </div>
  
  <audio ref="audioRef" class="hidden" @ended="isPlaying = false"></audio>
</template>
