<template>
  <Dialog :open="open" @update:open="$emit('close')">
    <DialogContent class="sm:max-w-2xl">
      <DialogHeader>
        <DialogTitle>{{ voice ? 'Edit Voice' : 'Add New Voice' }}</DialogTitle>
        <DialogDescription>
          Configure the AI voice settings for agents.
        </DialogDescription>
      </DialogHeader>

      <div class="grid gap-4 py-4">
        
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input id="name" v-model="form.name" placeholder="Friendly Support Voice" />
        </div>

        <div class="grid gap-2">
            <Label for="scene_prompt">System Prompt / Scene Prompt</Label>
            <textarea 
                id="scene_prompt" 
                v-model="form.scene_prompt" 
                rows="3"
                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                placeholder="You are a helpful customer service representative..."
            ></textarea>
        </div>

        <div class="grid gap-2">
            <Label for="transcript">Transcript (for caching)</Label>
            <textarea 
                id="transcript" 
                v-model="form.transcript" 
                rows="2"
                class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            ></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="ref_audio">Reference Audio Path</Label>
                <Input id="ref_audio" v-model="form.ref_audio" placeholder="gs://bucket/path.wav" />
            </div>

            <div class="grid gap-2">
                <Label for="chunk_method">Chunk Method</Label>
                 <Select v-model="form.chunk_method">
                    <SelectTrigger>
                        <SelectValue placeholder="Select method" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="default">Default</SelectItem>
                        <SelectItem value="sentence">Sentence</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
             <div class="grid gap-2">
                <Label for="temperature">Temperature</Label>
                <Input id="temperature" type="number" step="0.1" v-model="form.temperature" />
            </div>

            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <Select v-model="form.status">
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

      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('close')">Cancel</Button>
        <Button @click="save" :disabled="processing">
          {{ processing ? 'Saving...' : 'Save' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch, reactive } from 'vue'
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
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import { voiceService, type AdminVoice } from '@/services/voice'

const props = defineProps<{
    open: boolean
    voice: AdminVoice | null
}>()

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)

const form = reactive<{
    name: string
    scene_prompt: string
    transcript: string
    ref_audio: string
    chunk_method: string
    temperature: number
    status: 'active' | 'inactive'
}>({
    name: '',
    scene_prompt: '',
    transcript: '',
    ref_audio: '',
    chunk_method: 'default',
    temperature: 0.7,
    status: 'active'
})

watch(() => props.voice, (newVal) => {
    if (newVal) {
        Object.assign(form, newVal)
    } else {
        // Reset form
        form.name = ''
        form.scene_prompt = ''
        form.transcript = ''
        form.ref_audio = ''
        form.chunk_method = 'default'
        form.temperature = 0.7
        form.status = 'active'
    }
}, { immediate: true })

const save = async () => {
    processing.value = true
    try {
        if (props.voice) {
            await voiceService.updateVoice(props.voice.id, form)
        } else {
            await voiceService.createVoice(form)
        }
        emit('saved')
        emit('close')
    } catch (error) {
        console.error('Failed to save voice', error)
        alert('Failed to save voice')
    } finally {
        processing.value = false
    }
}
</script>
