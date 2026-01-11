<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Phone } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function handleLogin(e: Event) {
  e.preventDefault()
  loading.value = true
  error.value = ''
  
  try {
    await authStore.login({
        email: email.value,
        password: password.value
    })
    router.push('/dashboard')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Login failed. Please check credentials.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-background flex items-center justify-center p-4">
    <!-- Glow Effect -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-primary/10 rounded-full blur-[100px]"></div>
    </div>

    <Card class="w-full max-w-md bg-card/50 backdrop-blur-lg border-primary/20 relative z-10 shadow-[0_0_50px_rgba(0,0,0,0.5)]">
      <CardHeader class="space-y-2 text-center">
        <div class="mx-auto w-12 h-12 rounded-xl bg-primary flex items-center justify-center mb-4 glow-yellow">
          <Phone class="w-6 h-6 text-primary-foreground" />
        </div>
        <CardTitle class="text-2xl font-bold tracking-tight text-foreground">Welcome Back</CardTitle>
        <p class="text-sm text-muted-foreground">Sign in to your Nano Banana account</p>
      </CardHeader>

      <div v-if="error" class="px-6 mb-2">
        <div class="bg-destructive/15 text-destructive text-sm p-3 rounded-md text-center">
            {{ error }}
        </div>
      </div>
      
      <CardContent>
        <form @submit="handleLogin" class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-foreground">Email</label>
            <Input 
              v-model="email" 
              type="email" 
              placeholder="name@company.com" 
              required
              class="bg-background/50 border-input focus:border-primary/50 placeholder:text-muted-foreground/50"
            />
          </div>
          
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="text-sm font-medium leading-none text-foreground">Password</label>
              <a href="#" class="text-xs text-primary hover:text-primary/80 transition-colors">Forgot password?</a>
            </div>
            <Input 
              v-model="password" 
              type="password" 
              required
              class="bg-background/50 border-input focus:border-primary/50"
            />
          </div>

          <Button type="submit" class="w-full font-bold shadow-[0_0_20px_hsl(var(--primary)/0.2)] hover:shadow-[0_0_30px_hsl(var(--primary)/0.4)] transition-all duration-300" :disabled="loading">
            {{ loading ? 'Signing in...' : 'Sign In' }}
          </Button>

          <div class="text-center text-sm text-muted-foreground">
            Don't have an account? 
            <router-link to="/register" class="text-primary hover:underline font-medium">Sign up</router-link>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
