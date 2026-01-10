<script setup lang="ts">
import { Users, Phone, DollarSign, Activity } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-bold tracking-tight text-foreground">Dashboard</h1>
      <button class="bg-primary text-primary-foreground hover:bg-primary/90 px-4 py-2 rounded-md font-medium transition-colors shadow-[0_0_15px_hsl(var(--primary)/0.3)]">
        Add Funds
      </button>
    </div>

    <!-- Stats Grid -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium text-muted-foreground">Total Balance</CardTitle>
          <DollarSign class="h-4 w-4 text-primary" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold text-glow-yellow">$450.00</div>
          <p class="text-xs text-muted-foreground">+20.1% from last month</p>
        </CardContent>
      </Card>

      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium text-muted-foreground">Active Sessions</CardTitle>
          <Activity class="h-4 w-4 text-primary" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">24</div>
          <p class="text-xs text-muted-foreground">+12 since last hour</p>
        </CardContent>
      </Card>
      
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium text-muted-foreground">Active DIDs</CardTitle>
          <Phone class="h-4 w-4 text-primary" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">12</div>
          <p class="text-xs text-muted-foreground">4 unassigned</p>
        </CardContent>
      </Card>

      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium text-muted-foreground">Total Agents</CardTitle>
          <Users class="h-4 w-4 text-primary" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">7</div>
          <p class="text-xs text-muted-foreground">+2 new this week</p>
        </CardContent>
      </Card>
    </div>

    <!-- Main Visualizer Area (Placeholder for Waveform) -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
      <div class="col-span-4 rounded-xl border border-border bg-card p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-lg text-foreground">Live Voice Activity</h3>
        </div>
        <div class="h-[300px] w-full bg-background/50 rounded-lg flex items-center justify-center border border-border/50 relative overflow-hidden group">
          <!-- Fake Waveform Lines -->
          <div class="flex items-center gap-1 h-full">
            <div class="w-1 bg-primary/20 h-12 rounded-full animate-[pulse-glow_1s_ease-in-out_infinite] group-hover:h-32 transition-all duration-500"></div>
            <div class="w-1 bg-primary/40 h-16 rounded-full animate-[pulse-glow_1.2s_ease-in-out_infinite] group-hover:h-48 transition-all duration-500"></div>
            <div class="w-1 bg-primary/60 h-24 rounded-full animate-[pulse-glow_0.8s_ease-in-out_infinite] group-hover:h-64 transition-all duration-500"></div>
            <div class="w-1 bg-primary h-32 rounded-full animate-[pulse-glow_1.5s_ease-in-out_infinite] group-hover:h-full transition-all duration-500 shadow-[0_0_20px_hsl(var(--primary)/0.5)]"></div>
            <div class="w-1 bg-primary/60 h-24 rounded-full animate-[pulse-glow_0.9s_ease-in-out_infinite] group-hover:h-64 transition-all duration-500"></div>
            <div class="w-1 bg-primary/40 h-16 rounded-full animate-[pulse-glow_1.3s_ease-in-out_infinite] group-hover:h-48 transition-all duration-500"></div>
            <div class="w-1 bg-primary/20 h-12 rounded-full animate-[pulse-glow_1.1s_ease-in-out_infinite] group-hover:h-32 transition-all duration-500"></div>
          </div>
          <div class="absolute bottom-4 text-xs text-muted-foreground flex items-center gap-2">
             <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
             Monitoring Agent Alpha
          </div>
        </div>
      </div>
      
      <div class="col-span-3 rounded-xl border border-border bg-card p-6 shadow-sm">
        <h3 class="font-semibold text-lg text-foreground mb-4">Quick Actions</h3>
        <div class="space-y-4">
           <button class="w-full flex items-center justify-between p-4 rounded-lg border border-border bg-background/50 hover:border-primary/50 hover:bg-primary/5 transition-all group">
             <span class="text-sm font-medium">Create New Agent</span>
             <Users class="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
           </button>
           <button class="w-full flex items-center justify-between p-4 rounded-lg border border-border bg-background/50 hover:border-primary/50 hover:bg-primary/5 transition-all group">
             <span class="text-sm font-medium">Purchase DID</span>
             <Phone class="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
           </button>
            <button class="w-full flex items-center justify-between p-4 rounded-lg border border-border bg-background/50 hover:border-primary/50 hover:bg-primary/5 transition-all group">
             <span class="text-sm font-medium">View Payment History</span>
             <DollarSign class="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
           </button>
        </div>
      </div>
    </div>
  </div>
</template>
