<template>
  <div>
      <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-xl">
      <DialogHeader>
        <DialogTitle>{{ isEditing ? 'Edit Agent DID' : (company ? `Assign DID to ${company.business_name}` : 'Assign New DID') }}</DialogTitle>
        <DialogDescription>
          {{ isEditing ? 'Modify agent DID and settings.' : 'Assign a DID to this company.' }}
        </DialogDescription>
      </DialogHeader>

      <div class="grid gap-4 py-4">
        
        <div class="grid gap-2">
           <Label for="name">Agent Name</Label>
           <Input id="name" v-model="formData.name" placeholder="Sales Agent 1" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="did">DID Number</Label>
                <Select v-model="formData.did_id">
                    <SelectTrigger id="did">
                    <SelectValue :placeholder="loadingDids ? 'Loading DIDs...' : 'Select DID'" />
                    </SelectTrigger>
                    <SelectContent>
                    <SelectItem v-for="did in availableDids" :key="did.id" :value="did.id.toString()">
                        {{ did.did_number }}
                    </SelectItem>
                     <SelectItem v-if="availableDids.length === 0" disabled value="none">
                        No DIDs available
                    </SelectItem>
                    </SelectContent>
                </Select>
            </div>

             <div class="grid gap-2">
                <Label for="voice">Voice</Label>
                <Select v-model="formData.admin_voice_id">
                    <SelectTrigger id="voice">
                    <SelectValue :placeholder="loadingVoices ? 'Loading...' : 'Select Voice'" />
                    </SelectTrigger>
                    <SelectContent>
                    <SelectItem v-for="voice in voices" :key="voice.id" :value="voice.id.toString()">
                        {{ voice.name }}
                    </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="grid gap-2">
           <Label for="script">Script</Label>
           <textarea 
                id="script" 
                v-model="formData.script" 
                rows="3"
                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                placeholder="Agent script..."
            ></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
             <div class="grid gap-2">
                <Label for="price">Price per min ($)</Label>
                <Input id="price" type="number" step="0.001" v-model="formData.price_per_min" />
            </div>

            <div class="grid gap-2">
                <Label for="status">Initial Status</Label>
                <Select v-model="formData.status">
                    <SelectTrigger>
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="request">Request</SelectItem>
                        <SelectItem value="training">Training</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('update:open', false)">Cancel</Button>
        <Button @click="handleSubmit" :disabled="submitting || !isValid">
          {{ submitting ? 'Saving...' : 'Save' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { agentService } from '@/services/agent'
import { didService } from '@/services/did'
import { voiceService } from '@/services/voice'
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


const props = defineProps<{
  open: boolean
  company?: any
  agent?: any
}>()

const emit = defineEmits(['update:open', 'saved'])

const availableDids = ref<any[]>([])
const voices = ref<any[]>([])
const loadingDids = ref(false)
const loadingVoices = ref(false)
const submitting = ref(false)

const formData = ref({
  did_id: '',
  admin_voice_id: '',
  name: '',
  script: '',
  price_per_min: 0.05,
  status: 'active',
  start_date: new Date().toISOString().split('T')[0]
})

const isEditing = computed(() => !!props.agent)

const isValid = computed(() => {
    return formData.value.did_id && 
           formData.value.admin_voice_id && 
           formData.value.name
})

const fetchData = async () => {
  loadingDids.value = true
  loadingVoices.value = true
  try {
    const [didsRes, voicesRes] = await Promise.all([
        didService.getDids({ status: 'available', per_page: 100 }),
        voiceService.getVoices({ status: 'active', per_page: 100 })
    ])
    availableDids.value = didsRes.data
    voices.value = voicesRes.data
  } catch (error) {
    console.error('Failed to fetch data:', error)
  } finally {
    loadingDids.value = false
    loadingVoices.value = false
  }
}

watch(() => props.open, (newVal) => {
  if (newVal) {
    fetchData()
    if (props.agent) {
        formData.value = {
            did_id: props.agent.did_id?.toString() || '',
            admin_voice_id: props.agent.admin_voice_id?.toString() || '',
            name: props.agent.name || '',
            script: props.agent.script || '',
            price_per_min: props.agent.price_per_min || 0.05,
            status: props.agent.status || 'active',
            start_date: props.agent.start_date || new Date().toISOString().split('T')[0]
        }
    } else {
        // Reset form
        formData.value = {
            did_id: '',
            admin_voice_id: '',
            name: '',
            script: '',
            price_per_min: 0.05,
            status: 'active',
            start_date: new Date().toISOString().split('T')[0]
        }
    }
  }
})

const handleSubmit = async () => {
  if (!props.company && !props.agent) return

  submitting.value = true
  try {
    if (isEditing.value && props.agent) {
        await agentService.updateAgent(props.agent.id, {
            ...formData.value,
            // company_id: props.agent.company_id // Usually not changed
        } as any)
    } else {
        await agentService.createAgent({
            company_id: props.company!.id,
            did_id: parseInt(formData.value.did_id),
            admin_voice_id: parseInt(formData.value.admin_voice_id),
            name: formData.value.name,
            script: formData.value.script || '',
            price_per_min: formData.value.price_per_min,
            start_date: formData.value.start_date,
            status: formData.value.status,
            quantity: 0
        })
    }
    emit('saved')
    emit('update:open', false)
  } catch (error) {
    console.error('Failed to save agent:', error)
    alert('Failed to save agent. Please try again.')
  } finally {
    submitting.value = false
  }
}
</script>
