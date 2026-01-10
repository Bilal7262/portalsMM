<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Phone } from 'lucide-vue-next'

const router = useRouter()
const name = ref('')
const email = ref('')
const password = ref('')
const company = ref('')
const loading = ref(false)

async function handleRegister(e: Event) {
  e.preventDefault()
  loading.value = true
  
  // Simulate API call
  setTimeout(() => {
    loading.value = false
    router.push('/dashboard')
  }, 1000)
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
        <CardTitle class="text-2xl font-bold tracking-tight text-foreground">Get Started</CardTitle>
        <p class="text-sm text-muted-foreground">Create your Nano Banana account</p>
      </CardHeader>
      
      <CardContent>
        <form @submit="handleRegister" class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium leading-none text-foreground">Company Name</label>
            <Input 
              v-model="company" 
              type="text" 
              placeholder="Acme Corp" 
              required
              class="bg-background/50 border-input focus:border-primary/50"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium leading-none text-foreground">Full Name</label>
            <Input 
              v-model="name" 
              type="text" 
              placeholder="John Doe" 
              required
              class="bg-background/50 border-input focus:border-primary/50"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium leading-none text-foreground">Email</label>
            <Input 
              v-model="email" 
              type="email" 
              placeholder="name@company.com" 
              required
              class="bg-background/50 border-input focus:border-primary/50"
            />
          </div>
          
          <div class="space-y-2">
            <label class="text-sm font-medium leading-none text-foreground">Password</label>
            <Input 
              v-model="password" 
              type="password" 
              required
              class="bg-background/50 border-input focus:border-primary/50"
            />
            <p class="text-[10px] text-muted-foreground">Must be at least 8 characters long</p>
          </div>

          <Button type="submit" class="w-full font-bold shadow-[0_0_20px_hsl(var(--primary)/0.2)] hover:shadow-[0_0_30px_hsl(var(--primary)/0.4)] transition-all duration-300" :disabled="loading">
            {{ loading ? 'Creating Account...' : 'Create Account' }}
          </Button>

          <div class="text-center text-sm text-muted-foreground">
            Already have an account? 
            <router-link to="/login" class="text-primary hover:underline font-medium">Sign in</router-link>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
