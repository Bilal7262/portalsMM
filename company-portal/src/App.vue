<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import MainLayout from './components/layout/MainLayout.vue'
import AuthLayout from './components/layout/AuthLayout.vue'

const route = useRoute()
const layout = computed(() => {
  return route.meta.layout === 'auth' ? AuthLayout : MainLayout
})
</script>

<template>
  <component :is="layout">
    <router-view></router-view>
  </component>
</template>
