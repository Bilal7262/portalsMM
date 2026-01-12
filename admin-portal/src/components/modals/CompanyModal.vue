<template>
  <div>
  <Dialog :open="open" @update:open="$emit('close')">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle>{{ company ? 'Edit Company' : 'Add New Company' }}</DialogTitle>
        <DialogDescription>
          {{ company ? 'Update the details of the company below.' : 'Fill in the details to create a new company.' }}
        </DialogDescription>
      </DialogHeader>

      <div class="grid gap-4 py-4">
        <div class="grid gap-2">
          <Label for="business_name">Business Name</Label>
          <Input id="business_name" v-model="formData.business_name" placeholder="Acme Corp" />
        </div>

        <div class="grid gap-2">
          <Label for="email">Admin Email</Label>
          <Input id="email" type="email" v-model="formData.email" placeholder="admin@acme.com" />
        </div>

        <div class="grid gap-2" v-if="!company">
          <Label for="password">Password</Label>
          <Input id="password" type="password" v-model="formData.password" placeholder="Min 8 characters" />
        </div>

        <div class="grid gap-2">
          <Label for="phone">Phone Number</Label>
          <Input id="phone" v-model="formData.phone" placeholder="+1234567890" />
        </div>

        <div class="grid gap-2">
          <Label for="status">Status</Label>
          <Select v-model="formData.status" id="status">
            <SelectTrigger>
              <SelectValue placeholder="Select status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="active">Active</SelectItem>
              <SelectItem value="inactive">Inactive</SelectItem>
              <SelectItem value="pending">Pending</SelectItem>
              <SelectItem value="suspended">Suspended</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('close')">Cancel</Button>
        <Button @click="handleSubmit" :disabled="submitting">
          {{ submitting ? 'Saving...' : 'Save Company' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { companyService } from '@/services/company'
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

const emit = defineEmits(['close', 'saved'])

const submitting = ref(false)

const formData = ref({
  business_name: '',
  email: '',
  password: '',
  phone: '',
  status: 'active'
})

const resetForm = () => {
  formData.value = {
    business_name: '',
    email: '',
    password: '',
    phone: '',
    status: 'pending'
  }
}

watch(() => props.company, (newVal) => {
  if (newVal) {
    formData.value = {
      business_name: newVal.business_name,
      email: newVal.email,
      password: '', // Don't show password on edit
      phone: newVal.phone || '',
      status: newVal.status
    }
  } else {
    resetForm()
  }
}, { immediate: true })

const handleSubmit = async () => {
  submitting.value = true
  try {
    if (props.company) {
      await companyService.updateCompany(props.company.id, {
        business_name: formData.value.business_name,
        email: formData.value.email,
        phone: formData.value.phone,
        status: formData.value.status
      })
    } else {
      await companyService.createCompany(formData.value)
    }
    emit('saved')
    emit('close')
  } catch (error: any) {
    console.error('Failed to save company:', error)
    const msg = error.response?.data?.message || 'Failed to save company. Please check input.'
    alert(msg)
  } finally {
    submitting.value = false
  }
}
</script>
