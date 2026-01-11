<script setup lang="ts">
import { RouterView, RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Button from '@/components/ui/button/Button.vue'
import { cn } from '@/lib/utils'

const authStore = useAuthStore()
const route = useRoute()

const navItems = [
  { name: 'Dashboard', path: '/dashboard', icon: 'LayoutDashboard' },
  { name: 'Companies', path: '/companies', icon: 'Building2' },
  { name: 'DIDs', path: '/dids', icon: 'Phone' },
  { name: 'Company DIDs', path: '/company-dids', icon: 'Link' },
  { name: 'Invoices', path: '/invoices', icon: 'FileText' },
  { name: 'Reports', path: '/reports', icon: 'BarChart' },
  { name: 'Team', path: '/team', icon: 'Users' },
  { name: 'Activity Logs', path: '/activity-logs', icon: 'History' },
]

function isActive(path: string) {
  return route.path.startsWith(path)
}
</script>

<template>
  <div class="min-h-screen bg-background flex flex-col">
    <!-- Header -->
    <header class="border-b h-16 flex items-center px-6 justify-between bg-card text-card-foreground">
      <div class="flex items-center gap-2 font-bold text-xl">
        <span>VoIP Admin</span>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-muted-foreground hidden sm:inline-block">
          {{ authStore.user?.email }}
        </span>
        <Button variant="ghost" size="sm" @click="authStore.logout()">Logout</Button>
      </div>
    </header>

    <div class="flex flex-1">
      <!-- Sidebar -->
      <aside class="w-64 border-r bg-muted/20 hidden md:block">
        <nav class="flex flex-col gap-2 p-4">
          <RouterLink
            v-for="item in navItems"
            :key="item.path"
            :to="item.path"
            :class="cn(
              'flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground',
              isActive(item.path) ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'
            )"
          >
            <!-- Icon placeholder logic or Lucide Icon component here -->
            <span>{{ item.name }}</span>
          </RouterLink>
        </nav>
      </aside>

      <!-- Main Content -->
      <main class="flex-1 p-6 overflow-auto">
        <RouterView />
      </main>
    </div>
  </div>
</template>
