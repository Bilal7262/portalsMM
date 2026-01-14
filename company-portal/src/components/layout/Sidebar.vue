<script setup lang="ts">
import { useRouter, useRoute } from 'vue-router'
import { 
  LayoutDashboard, 
  Phone, 
  Users, 
  Settings, 
  LogOut,
  FileText,
  Activity,
  Bot
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const route = useRoute()
const router = useRouter()

const navigation = [
  { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
  { name: 'My Agents', path: '/agents', icon: Bot },
  { name: 'Team Members', path: '/users', icon: Users },
  { name: 'Invoices', path: '/invoices', icon: FileText },
  { name: 'Call History', path: '/call-history', icon: Phone },
  { name: 'Activity Logs', path: '/activity-logs', icon: Activity },
  { name: 'Settings', path: '/settings', icon: Settings },
]

function isActive(path: string) {
  return route.path.startsWith(path)
}

function logout() {
  // TODO: Implement logout logic
  router.push('/login')
}
</script>

<template>
  <aside class="w-64 bg-card border-r border-border flex flex-col h-full shrink-0 transition-all duration-300">
    <!-- Logo Area -->
    <div class="h-16 flex items-center px-6 border-b border-border">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center glow-yellow">
          <Phone class="w-5 h-5 text-primary-foreground" />
        </div>
        <span class="font-bold text-lg tracking-tight text-foreground">
          Speak<span class="text-primary">Sync</span>
        </span>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-1">
      <router-link
        v-for="item in navigation"
        :key="item.name"
        :to="item.path"
        class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-all duration-200 group"
        :class="[
          isActive(item.path)
            ? 'bg-primary/10 text-primary border-r-2 border-primary'
            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
        ]"
      >
        <component 
          :is="item.icon" 
          class="w-5 h-5 transition-colors"
          :class="isActive(item.path) ? 'text-primary drop-shadow-[0_0_8px_hsl(var(--primary)/0.5)]' : 'group-hover:text-foreground'"
        />
        {{ item.name }}
      </router-link>
    </nav>

    <!-- User Profile & Logout -->
    <div class="p-4 border-t border-border">
      <Button 
        variant="ghost"
        class="w-full justify-start gap-3 px-3 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
        @click="logout"
      >
        <LogOut class="w-5 h-5" />
        Sign Out
      </Button>
    </div>
  </aside>
</template>
