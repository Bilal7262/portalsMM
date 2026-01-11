<template>
  <Dialog :open="open" @update:open="$emit('close')">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>Assign DID to {{ company?.business_name }}</DialogTitle>
        <DialogDescription>
          Select an available DID number and set the pricing details.
        </DialogDescription>
      </DialogHeader>

      <div class="grid gap-4 py-4">
        <div class="grid gap-2">
          <Label for="did">Available DID</Label>
          <Select v-model="formData.did_id">
            <SelectTrigger id="did">
              <SelectValue :placeholder="loadingDids ? 'Loading DIDs...' : 'Select a DID'" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="did in availableDids" :key="did.id" :value="did.id.toString()">
                {{ did.did_number }}
              </SelectItem>
              <SelectItem v-if="availableDids.length === 0 && !loadingDids" disabled value="none">
                No DIDs available
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="grid gap-2">
          <Label for="price">Price per minute ($)</Label>
          <Input id="price" type="number" step="0.001" v-model="formData.price_per_min" placeholder="0.05" />
        </div>

        <div class="grid gap-2">
          <Label for="start_date">Start Date</Label>
          <Input id="start_date" type="date" v-model="formData.start_date" />
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('close')">Cancel</Button>
        <Button @click="handleSubmit" :disabled="submitting || !formData.did_id">
          {{ submitting ? 'Assigning...' : 'Assign DID' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { didService } from '@/services/did'
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
  company: any | null
}>()

const emit = defineEmits(['close', 'assigned'])

const availableDids = ref<any[]>([])
const loadingDids = ref(false)
const submitting = ref(false)

const formData = ref({
  did_id: '',
  price_per_min: 0.05,
  start_date: new Date().toISOString().split('T')[0]
})

const fetchAvailableDids = async () => {
  loadingDids.value = true
  try {
    const response = await didService.getDids({ status: 'available', per_page: 100 })
    availableDids.value = response.data
  } catch (error) {
    console.error('Failed to fetch available DIDs:', error)
  } finally {
    loadingDids.value = false
  }
}

watch(() => props.open, (newVal) => {
  if (newVal) {
    fetchAvailableDids()
    formData.value.did_id = ''
  }
})

const handleSubmit = async () => {
  if (!props.company) return

  submitting.value = true
  try {
    await didService.assignDid({
      company_id: props.company.id as number,
      did_id: parseInt(formData.value.did_id),
      price_per_min: formData.value.price_per_min,
      start_date: formData.value.start_date as string
    })
    emit('assigned')
    emit('close')
  } catch (error) {
    console.error('Failed to assign DID:', error)
    alert('Failed to assign DID. Please try again.')
  } finally {
    submitting.value = false
  }
}
</script>
