<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import ButtonComponent from '@/components/ui/button/Button.vue' // Fallback if index not ready
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const isLoading = ref(false)
const errorMessage = ref('')

async function handleLogin() {
  if (!email.value || !password.value) {
    errorMessage.value = 'Please enter both email and password'
    return
  }

  isLoading.value = true
  errorMessage.value = ''

  const success = await authStore.login({
    email: email.value,
    password: password.value,
  }, 'admin') // Specify 'admin' portal

  isLoading.value = false

  if (success) {
    router.push('/dashboard')
  } else {
    errorMessage.value = authStore.error || 'Login failed. Please check your credentials.'
  }
}
</script>

<template>
  <div class="flex h-screen w-full items-center justify-center bg-gray-50 dark:bg-zinc-900">
    <Card class="w-full max-w-sm">
      <CardHeader>
        <CardTitle class="text-2xl">Admin Login</CardTitle>
        <CardDescription>
          Enter your email below to login to your account.
        </CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4">
        <div class="grid gap-2">
          <Label htmlFor="email">Email</Label>
          <Input id="email" type="email" placeholder="m@example.com" v-model="email" required />
        </div>
        <div class="grid gap-2">
          <Label htmlFor="password">Password</Label>
          <Input id="password" type="password" v-model="password" required />
        </div>
        <div v-if="errorMessage" class="text-sm text-red-500 font-medium">
          {{ errorMessage }}
        </div>
      </CardContent>
      <CardFooter>
        <ButtonComponent class="w-full" @click="handleLogin" :disabled="isLoading">
          <span v-if="isLoading">Signing in...</span>
          <span v-else>Sign in</span>
        </ButtonComponent>
      </CardFooter>
    </Card>
  </div>
</template>
