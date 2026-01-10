<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { reportService, type DashboardStats } from '@/services/report'
import Button from '@/components/ui/button/Button.vue'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

const authStore = useAuthStore()
const stats = ref<DashboardStats | null>(null)
const isLoading = ref(true)

onMounted(async () => {
  try {
    stats.value = await reportService.getDashboardStats()
  } catch (error) {
    console.error('Failed to load dashboard stats', error)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen bg-background">
    <!-- Navbar / Header (Simulated for now) -->
    <header class="border-b">
      <div class="container flex h-16 items-center px-4 justify-between">
        <h2 class="text-lg font-semibold">Admin Portal</h2>
        <div class="flex items-center gap-4">
          <span class="text-sm text-muted-foreground">
            {{ authStore.user?.name }} ({{ authStore.user?.email }})
          </span>
          <Button variant="outline" size="sm" @click="authStore.logout()">Logout</Button>
        </div>
      </div>
    </header>

    <main class="container p-8 space-y-8">
      <div class="flex items-center justify-between space-y-2">
        <h2 class="text-3xl font-bold tracking-tight">Dashboard</h2>
      </div>

      <div v-if="isLoading" class="text-center py-10">Loading statistics...</div>

      <div v-else-if="stats" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              class="h-4 w-4 text-muted-foreground"
            >
              <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
            </svg>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">${{ stats.total_revenue.toFixed(2) }}</div>
            <p class="text-xs text-muted-foreground">
             Total billed from paid invoices
            </p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Companies</CardTitle>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              class="h-4 w-4 text-muted-foreground"
            >
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">+{{ stats.total_companies }}</div>
            <p class="text-xs text-muted-foreground">
              {{ stats.active_companies }} Active
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">DIDs</CardTitle>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              class="h-4 w-4 text-muted-foreground"
            >
              <rect width="20" height="14" x="2" y="5" rx="2" />
              <path d="M2 10h20" />
            </svg>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total_dids }}</div>
            <p class="text-xs text-muted-foreground">
              {{ stats.assigned_dids }} Assigned / {{ stats.available_dids }} Available
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Calls</CardTitle>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              class="h-4 w-4 text-muted-foreground"
            >
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
            </svg>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">+{{ stats.total_calls }}</div>
            <p class="text-xs text-muted-foreground">
              All time system calls
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Recent Calls Section -->
      <div v-if="stats && stats.recent_calls.length > 0" class="grid gap-4">
        <Card>
          <CardHeader>
            <CardTitle>Recent Calls</CardTitle>
            <CardDescription>
              Latest 5 calls across the system.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Caller</TableHead>
                  <TableHead>Duration</TableHead>
                  <TableHead>Status/Disposition</TableHead>
                  <TableHead class="text-right">Date</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="call in stats.recent_calls" :key="call.id">
                  <TableCell class="font-medium">{{ call.user_phone }}</TableCell>
                  <TableCell>{{ call.duration }}s</TableCell>
                  <TableCell>{{ call.disposition }}</TableCell>
                  <TableCell class="text-right">{{ new Date(call.created_at).toLocaleString() }}</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </main>
  </div>
</template>
