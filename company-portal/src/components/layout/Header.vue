<script setup lang="ts">
import { Bell, Search } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
</script>

<template>
  <header class="h-16 border-b border-border bg-card/50 backdrop-blur-sm px-6 flex items-center justify-between sticky top-0 z-10">
    
    <!-- Search Bar -->
    <div class="flex items-center max-w-md w-full">
      <div class="relative w-full">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground z-10" />
        <Input 
          type="text" 
          placeholder="Search..." 
          class="pl-10 bg-muted/50 border-transparent focus-visible:ring-primary/50"
        />
      </div>
    </div>

    <!-- Right Actions -->
    <div class="flex items-center gap-4">
      <button class="relative p-2 rounded-full hover:bg-muted transition-colors text-muted-foreground hover:text-foreground">
        <Bell class="w-5 h-5" />
        <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-primary glow-yellow"></span>
      </button>
      
      <div class="flex items-center gap-3 pl-4 border-l border-border">
        <div class="flex flex-col items-end">
          <span class="text-xs font-semibold text-foreground">NextGen Tech</span>
          <span class="text-[10px] text-muted-foreground">Premium Plan</span>
        </div>
        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-accent border border-primary/20"></div>
      </div>
    </div>
  </header>
</template>
