<script setup lang="ts">
import Sidebar from './Sidebar.vue'
import Header from './Header.vue'
</script>

<template>
  <div class="flex h-screen bg-background text-foreground overflow-hidden">
    <!-- Sidebar -->
    <Sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Header -->
      <Header />

      <!-- Main Content -->
      <main class="flex-1 overflow-y-auto p-6 bg-background/50">
        <div class="container mx-auto max-w-7xl animate-in fade-in duration-500">
          <slot></slot>
        </div>
      </main>
    </div>
  </div>
</template>
