<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { UserAgent, Registerer, Inviter, UserAgentOptions, RegistererState, SessionState } from 'sip.js'
import { Phone, Mic, Download, Loader2, Bot, Circle, Volume2 } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'

// Configuration
const CONFIG = {
    server: 'n8n.eggsinn.store',
    port: 8089,
    wsPath: '/ws',
    username: 'customer1',
    password: 'supersecretpass1',
    fromUri: 'sip:customer1@n8n.eggsinn.store',
    targetUri: 'sip:1000@n8n.eggsinn.store'
}

// State
const status = ref<'connecting' | 'connected' | 'disconnected'>('connecting')
const statusText = ref('Connecting...')
const selectedVoice = ref('v1')
const isCalling = ref(false)
const isRinging = ref(false)
const isRecordingAvailable = ref(false)
const audioUrl = ref('')
const downloadName = ref('')

// SIP & Audio Objects
let userAgent: UserAgent | null = null
let currentSession: Inviter | null = null
let mediaRecorder: MediaRecorder | null = null
let audioChunks: Blob[] = []
let audioContext: AudioContext | null = null
let mixedStreamDest: MediaStreamAudioDestinationNode | null = null
const remoteAudioRef = ref<HTMLAudioElement | null>(null)
const playbackAudioRef = ref<HTMLAudioElement | null>(null)

// Methods
const updateStatus = (type: 'connecting' | 'connected' | 'disconnected', text: string) => {
    status.value = type
    statusText.value = text
}

const selectVoice = (voiceId: string) => {
    selectedVoice.value = voiceId
}

const initSIP = async () => {
    try {
        const wsUrl = `wss://${CONFIG.server}:${CONFIG.port}${CONFIG.wsPath}`
        
        const uri = UserAgent.makeURI(CONFIG.fromUri)
        if (!uri) throw new Error("Failed to create URI")

        const userAgentOptions: UserAgentOptions = {
            uri: uri,
            transportOptions: { server: wsUrl },
            authorizationUsername: CONFIG.username,
            authorizationPassword: CONFIG.password,
            userAgentString: 'DemoClient/1.0',
            sessionDescriptionHandlerFactoryOptions: {
                constraints: { audio: true, video: false }
            },
            delegate: {
                onConnect: () => console.log('WebSocket Connected'),
                onDisconnect: (error) => {
                    updateStatus('disconnected', 'Disconnected')
                    console.error('WebSocket Disconnected:', error)
                }
            }
        }

        userAgent = new UserAgent(userAgentOptions)

        await userAgent.start()
        
        const registerer = new Registerer(userAgent)
        
        registerer.stateChange.addListener((state) => {
            if (state === RegistererState.Registered) {
                updateStatus('connected', 'Ready to Call')
            }
        })

        await registerer.register()

    } catch (err) {
        console.error('SIP Init Error:', err)
        updateStatus('disconnected', 'Connection Failed')
    }
}

const startDemo = async () => {
    if (!userAgent) return

    isRinging.value = true
    isRecordingAvailable.value = false
    
    const targetURI = UserAgent.makeURI(CONFIG.targetUri)
    if(!targetURI) return

    const options = {
        extraHeaders: [`X-Voice-ID: ${selectedVoice.value}`],
        sessionDescriptionHandlerOptions: {
            constraints: { audio: true, video: false }
        }
    }

    const inviter = new Inviter(userAgent, targetURI, options)
    currentSession = inviter

    inviter.stateChange.addListener((state) => {
        if (state === SessionState.Established) {
            isRinging.value = false
            isCalling.value = true
            updateStatus('connected', 'In Call')
            setupRecording(inviter)
        } else if (state === SessionState.Terminated) {
            endSessionUI()
        }
    })

    inviter.invite()
            .catch(error => {
                console.error("Invite failed", error)
                endSessionUI()
            })
}

const endDemo = () => {
    if (currentSession) {
        switch(currentSession.state) {
            case SessionState.Initial:
            case SessionState.Establishing:
                currentSession.cancel()
                break
            case SessionState.Established:
                currentSession.bye()
                break
        }
        currentSession = null
    }
    endSessionUI()
}

const endSessionUI = () => {
    isCalling.value = false
    isRinging.value = false
    updateStatus('connected', 'Call Ended')
    stopRecording()
}

// Recording Logic
const setupRecording = (session: Inviter) => {
    try {
        const AudioContextClass = window.AudioContext || (window as any).webkitAudioContext
        audioContext = new AudioContextClass()
        mixedStreamDest = audioContext.createMediaStreamDestination()

        // 1. Remote Stream
        const pc = session.sessionDescriptionHandler?.peerConnection
        const remoteStream = new MediaStream()
        
        if (pc) {
            pc.getReceivers().forEach(receiver => {
                if (receiver.track && receiver.track.kind === 'audio') {
                    remoteStream.addTrack(receiver.track)
                }
            })
        }

        if (remoteAudioRef.value) {
            remoteAudioRef.value.srcObject = remoteStream
        }

        // 2. Local Stream
        const localStream = session.sessionDescriptionHandler?.peerConnection?.getLocalStreams()[0]

        // 3. Connect to Mixer
        if (remoteStream.getAudioTracks().length > 0) {
            const remoteSource = audioContext.createMediaStreamSource(remoteStream)
            remoteSource.connect(mixedStreamDest)
        }

        if (localStream && localStream.getAudioTracks().length > 0) {
            const localSource = audioContext.createMediaStreamSource(localStream)
            localSource.connect(mixedStreamDest)
        }

        // 4. Start MediaRecorder
        mediaRecorder = new MediaRecorder(mixedStreamDest.stream)
        audioChunks = []

        mediaRecorder.ondataavailable = (e) => {
            if (e.data.size > 0) {
                audioChunks.push(e.data)
            }
        }

        mediaRecorder.onstop = () => {
            const blob = new Blob(audioChunks, { type: 'audio/webm' })
            const url = URL.createObjectURL(blob)
            
            audioUrl.value = url
            downloadName.value = `demo-call-${new Date().getTime()}.webm`
            isRecordingAvailable.value = true
            
            if(playbackAudioRef.value) {
                playbackAudioRef.value.src = url
            }
        }

        mediaRecorder.start()

    } catch (err) {
        console.error('Recording Setup Error:', err)
    }
}

const stopRecording = () => {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop()
    }
    if (audioContext) {
        audioContext.close()
        audioContext = null
    }
}

onMounted(() => {
    initSIP()
})

onUnmounted(() => {
    if (userAgent) {
        userAgent.stop()
    }
    if (audioContext) {
        audioContext.close()
    }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div class="space-y-1">
        <h2 class="text-3xl font-bold tracking-tight">Live Demo</h2>
        <p class="text-muted-foreground">Test the AI agent capabilities in real-time.</p>
      </div>
      <div>
         <Badge 
            :variant="status === 'connected' ? 'default' : status === 'disconnected' ? 'destructive' : 'outline'"
            class="px-3 py-1"
            :class="{'bg-yellow-500 hover:bg-yellow-600 border-yellow-500': status === 'connecting'}"
         >
            <Circle class="w-2 h-2 mr-2 fill-current animate-pulse" v-if="status === 'connecting'" />
            <Circle class="w-2 h-2 mr-2 fill-current" v-else />
            {{ statusText }}
         </Badge>
      </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Voice Configuration & Status -->
      <Card>
        <CardHeader>
          <CardTitle>Configuration</CardTitle>
          <CardDescription>Select a voice persona to interact with.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-6">
           <div class="grid grid-cols-2 gap-4">
              <div 
                @click="selectVoice('v1')" 
                class="relative flex flex-col items-center justify-between rounded-md border-2 p-4 cursor-pointer transition-all duration-300"
                :class="selectedVoice === 'v1' 
                  ? 'border-primary bg-primary/10 shadow-[0_0_20px_hsl(var(--primary)/0.2)]' 
                  : 'border-muted bg-popover hover:border-primary/50 hover:bg-muted/50 hover:-translate-y-1'"
              >
                  <div class="mb-3 rounded-full bg-primary/10 p-3">
                      <Bot class="h-6 w-6 text-primary" />
                  </div>
                  <div class="text-center">
                       <span class="font-semibold">Sarah</span>
                       <span class="block text-xs text-muted-foreground">Professional</span>
                  </div>
              </div>
               <div 
                @click="selectVoice('v2')" 
                class="relative flex flex-col items-center justify-between rounded-md border-2 p-4 cursor-pointer transition-all duration-300"
                 :class="selectedVoice === 'v2' 
                  ? 'border-primary bg-primary/10 shadow-[0_0_20px_hsl(var(--primary)/0.2)]' 
                  : 'border-muted bg-popover hover:border-primary/50 hover:bg-muted/50 hover:-translate-y-1'"
              >
                  <div class="mb-3 rounded-full bg-primary/10 p-3">
                      <Bot class="h-6 w-6 text-primary" />
                  </div>
                  <div class="text-center">
                       <span class="font-semibold">Michael</span>
                       <span class="block text-xs text-muted-foreground">Calm</span>
                  </div>
              </div>
           </div>
        </CardContent>
      </Card>

      <!-- Interaction Area -->
      <Card class="flex flex-col">
        <CardHeader>
          <CardTitle>Interactive Session</CardTitle>
          <CardDescription>Start a call to chat with the AI.</CardDescription>
        </CardHeader>
        <CardContent class="flex-1 flex flex-col items-center justify-center py-8">
            <div class="mb-8 relative">
               <div v-if="isCalling" class="absolute inset-0 rounded-full bg-primary/20 animate-ping"></div>
               <div class="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center border-2 border-primary/20 relative z-10 transition-all duration-500" :class="{'bg-green-500/10 border-green-500': isCalling}">
                  <Bot class="w-12 h-12 text-primary" :class="{'text-green-500': isCalling}" />
               </div>
            </div>

            <div class="flex items-center gap-4">
                 <Button 
                    v-if="!isCalling"
                    size="lg" 
                    class="h-14 px-8 rounded-full text-lg shadow-lg"
                    :disabled="status !== 'connected' || isRinging"
                    @click="startDemo"
                 >
                    <Loader2 v-if="isRinging" class="w-5 h-5 mr-2 animate-spin" />
                    <Phone v-else class="w-5 h-5 mr-2" />
                    {{ isRinging ? 'Connecting...' : 'Start Call' }}
                 </Button>

                 <Button 
                    v-else
                    size="lg" 
                    variant="destructive"
                    class="h-14 px-8 rounded-full text-lg shadow-lg"
                    @click="endDemo"
                 >
                    <Phone class="w-5 h-5 mr-2 rotate-[135deg]" />
                    End Call
                 </Button>
            </div>
        </CardContent>
      </Card>
      
      <!-- Recording Section -->
      <Card v-if="isRecordingAvailable" class="md:col-span-2">
         <CardHeader>
           <div class="flex items-center gap-2">
             <Mic class="w-5 h-5 text-primary" />
             <CardTitle>Session Recording</CardTitle>
           </div>
         </CardHeader>
         <CardContent>
            <div class="bg-muted p-4 rounded-lg flex flex-col md:flex-row items-center gap-4">
               <audio ref="playbackAudioRef" controls class="w-full md:flex-1"></audio>
               <Button variant="outline" as-child>
                 <a :href="audioUrl" :download="downloadName">
                   <Download class="w-4 h-4 mr-2" />
                   Download
                 </a>
               </Button>
            </div>
         </CardContent>
      </Card>
    </div>

    <!-- Hidden Remote Audio -->
    <audio ref="remoteAudioRef" autoplay class="hidden"></audio>
  </div>
</template>

<style scoped>
audio {
    height: 40px;
}
</style>
