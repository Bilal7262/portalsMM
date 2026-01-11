<template>
  <Dialog :open="open" @update:open="$emit('close')">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>{{ did ? 'Edit DID' : 'Add New DID' }}</DialogTitle>
        <DialogDescription>
          {{ did ? 'Update the DID details.' : 'Enter a new DID number to add to the system.' }}
        </DialogDescription>
      </DialogHeader>

      <div class="grid gap-4 py-4">
        <div class="grid gap-2">
          <Label for="did_number">DID Number</Label>
          <Input id="did_number" v-model="formData.did_number" placeholder="+1234567890" />
        </div>

        <div class="grid gap-2">
          <Label for="status">Status</Label>
          <Select v-model="formData.status" id="status">
            <SelectTrigger>
              <SelectValue placeholder="Select status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="available">Available</SelectItem>
              <SelectItem value="assigned" :disabled="!did">Assigned</SelectItem>
              <SelectItem value="maintenance">Maintenance</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('close')">Cancel</Button>
        <Button @click="handleSubmit" :disabled="submitting || !formData.did_number">
          {{ submitting ? 'Saving...' : 'Save DID' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { didService, type DID } from '@/services/did'
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
  did: DID | null
}>()

const emit = defineEmits(['close', 'saved'])

const submitting = ref(false)

const formData = ref({
  did_number: '',
  status: 'available' as 'available' | 'assigned' | 'maintenance'
})

watch(() => props.did, (newVal) => {
  if (newVal) {
    formData.value = {
      did_number: newVal.did_number,
      status: newVal.status as any
    }
  } else {
    formData.value = {
      did_number: '',
      status: 'available'
    }
  }
}, { immediate: true })

const handleSubmit = async () => {
  submitting.value = true
  try {
    if (props.did) {
      await didService.updateDid(props.did.id, formData.value)
    } else {
      await didService.createDid(formData.value)
    }
    emit('saved')
    emit('close')
  } catch (error: any) {
    console.error('Failed to save DID:', error)
    alert('Failed to save DID. Number might already exist.')
  } finally {
    submitting.value = false
  }
}
</script>
